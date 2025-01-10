import { NextResponse } from "next/server";

import { authMiddleware } from "@/helpers/authMiddleware";
import { createRouteMatcher } from "./helpers/createRouteMacther";

export default authMiddleware(async (auth, req) => {
  const isPublicRoutes = createRouteMatcher(["/", "/login(.*)"]);
  const isAuthRoutes = createRouteMatcher(["/login(.*)"]);

  if (!auth.isAuthenticated && !isPublicRoutes(req)) {
    return NextResponse.redirect(new URL("/login", req.nextUrl));
  }

  if (auth.isAuthenticated && isAuthRoutes(req)) {
    return NextResponse.redirect(new URL("/dashboard", req.nextUrl));
  }

  return NextResponse.next();
});

export const config = {
  matcher: [
    // Skip Next.js internals and all static files, unless found in search params
    "/((?!_next|[^?]*\\.(?:html?|css|js(?!on)|jpe?g|webp|png|gif|svg|ttf|woff2?|ico|csv|docx?|xlsx?|zip|webmanifest)).*)",
    // Always run for API routes
    "/(api|trpc)(.*)",
  ],
};
