/**
 * Centralized API client for MTD Store.
 */

export class ApiError extends Error {
  constructor(public code: string, message: string, public trace_id?: string) {
    super(message);
    this.name = "ApiError";
  }
}

function getBaseUrl(): string {
  // Always use NEXT_PUBLIC_API_URL if it's set (meaning backend is external like Render)
  const envUrl = process.env.NEXT_PUBLIC_API_URL;
  if (envUrl) {
    // Remove /api/v1 suffix if present — we add it in every call
    return envUrl.replace(/\/api\/v1\/?$/, "");
  }

  // Client-side fallback: use the current domain (for monorepo on Vercel)
  if (typeof window !== "undefined") {
    return window.location.origin;
  }

  // Server-side fallback: empty string makes Next.js use its own origin
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
    throw new ApiError("NETWORK_ERROR", "Não foi possível conectar ao servidor. Verifique sua conexão.");
  }

  if (!response.ok) {
    let errorBody: { error?: { code?: string; message?: string; details?: Record<string, string[]>; trace_id?: string }; message?: string };
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
        throw new ApiError("VALIDATION_ERROR", firstMessages[0]);
      }
    }

    const message =
      errorBody.error?.message ??
      errorBody.message ??
      `Erro ${response.status}`;
      
    throw new ApiError(
      errorBody.error?.code ?? "UNKNOWN_ERROR",
      message,
      errorBody.error?.trace_id
    );
  }

  return response.json() as Promise<T>;
}
