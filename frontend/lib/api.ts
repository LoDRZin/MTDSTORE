/**
 * Centralized API client for MTD Store.
 *
 * URL resolution strategy:
 * - Client-side (browser): uses window.location.origin — always correct, never localhost
 * - Server-side (Next.js SSR/SSG): uses NEXT_PUBLIC_API_URL if set, otherwise
 *   falls back to a relative path which Next.js resolves correctly on Vercel.
 *
 * This ensures:
 *   1. No hardcoded localhost URLs leak into production builds.
 *   2. Works on both Vercel (same-domain monorepo) and custom domains.
 *   3. Differentiates network errors from API errors in the catch block.
 */

function getBaseUrl(): string {
  // Client-side: always use the current domain
  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  // Server-side: use NEXT_PUBLIC_API_URL if explicitly set (and not empty/localhost)
  const envUrl = process.env.NEXT_PUBLIC_API_URL;
  if (envUrl && envUrl.startsWith("http") && !envUrl.includes("localhost")) {
    // Remove /api/v1 suffix if present — we add it in every call
    return envUrl.replace(/\/api\/v1\/?$/, "");
  }

  // Server-side fallback: empty string makes Next.js use its own origin
  // This works correctly on Vercel where frontend and backend share a domain
  return "";
}

export function apiUrl(path: string): string {
  const base = getBaseUrl();
  // Ensure path starts with /api/v1
  const normalizedPath = path.startsWith("/api/v1")
    ? path
    : `/api/v1/${path.replace(/^\//, "")}`;
  return `${base}${normalizedPath}`;
}

interface ApiFetchOptions extends RequestInit {
  token?: string;
}

export async function apiFetch<T = unknown>(
  path: string,
  options: ApiFetchOptions = {}
): Promise<T> {
  const { token, headers: customHeaders, ...rest } = options;

  const headers: HeadersInit = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(customHeaders ?? {}),
  };

  let response: Response;
  const url = apiUrl(path);

  try {
    response = await fetch(url, { headers, ...rest });
  } catch (networkError) {
    // This is a REAL network failure (DNS, CORS preflight blocked, connection refused)
    console.error(`[apiFetch] Network error calling ${url}:`, networkError);
    throw new Error(
      `Não foi possível conectar ao servidor. Verifique sua conexão.`
    );
  }

  if (!response.ok) {
    let errorBody: { error?: { message?: string; details?: Record<string, string[]> }; message?: string };
    try {
      errorBody = await response.json();
    } catch {
      errorBody = {};
    }

    // Extract the most specific error message available
    const details = errorBody.error?.details;
    if (details) {
      const firstMessages = Object.values(details)[0];
      if (Array.isArray(firstMessages) && firstMessages.length > 0) {
        throw new Error(firstMessages[0]);
      }
    }

    const message =
      errorBody.error?.message ??
      errorBody.message ??
      `Erro ${response.status}`;
    throw new Error(message);
  }

  return response.json() as Promise<T>;
}
