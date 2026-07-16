import { NextRequest, NextResponse } from "next/server";
import { revalidatePath, revalidateTag } from "next/cache";

export async function POST(request: NextRequest) {
  try {
    const { secret, type, slug } = await request.json();

    // Verify the webhook secret
    if (secret !== process.env.REVALIDATION_TOKEN && secret !== "dev-secret-token") {
      return NextResponse.json({ message: "Invalid token" }, { status: 401 });
    }

    if (type === "product") {
      if (slug) {
        revalidateTag(`product-${slug}`);
        revalidatePath(`/produtos/${slug}`);
      }
      revalidateTag("products");
      revalidatePath("/");
      
      return NextResponse.json({ revalidated: true, now: Date.now() });
    }

    return NextResponse.json({ message: "Unknown revalidation type" }, { status: 400 });
  } catch {
    return NextResponse.json({ message: "Error revalidating" }, { status: 500 });
  }
}
