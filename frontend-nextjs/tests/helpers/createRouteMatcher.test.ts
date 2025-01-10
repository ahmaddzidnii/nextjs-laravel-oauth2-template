import { NextRequest } from "next/server";

import { createRouteMatcher } from "@/helpers/createRouteMacther";

describe("createRouteMatcher", () => {
  it("matches exact patterns", () => {
    const matcher = createRouteMatcher(["/login", "/dashboard"]);

    expect(matcher({ nextUrl: { pathname: "/login" } } as unknown as NextRequest)).toBe(true);
    expect(matcher({ nextUrl: { pathname: "/dashboard" } } as unknown as NextRequest)).toBe(true);
    expect(matcher({ nextUrl: { pathname: "/profile" } } as unknown as NextRequest)).toBe(false);
  });

  it("matches patterns with wildcard", () => {
    const matcher = createRouteMatcher(["/profile(.*)", "/settings"]);

    expect(matcher({ nextUrl: { pathname: "/profile" } } as unknown as NextRequest)).toBe(true);
    expect(matcher({ nextUrl: { pathname: "/profile/edit" } } as unknown as NextRequest)).toBe(
      true
    );
    expect(matcher({ nextUrl: { pathname: "/profile/settings" } } as unknown as NextRequest)).toBe(
      true
    );
    expect(matcher({ nextUrl: { pathname: "/settings" } } as unknown as NextRequest)).toBe(true);
    expect(matcher({ nextUrl: { pathname: "/other" } } as unknown as NextRequest)).toBe(false);
  });

  it("does not match patterns that are not in the list", () => {
    const matcher = createRouteMatcher(["/about", "/contact"]);

    expect(matcher({ nextUrl: { pathname: "/home" } } as unknown as NextRequest)).toBe(false);
    expect(matcher({ nextUrl: { pathname: "/dashboard" } } as unknown as NextRequest)).toBe(false);
  });

  it("handles edge cases for empty patterns", () => {
    const matcher = createRouteMatcher([]);

    expect(matcher({ nextUrl: { pathname: "/login" } } as unknown as NextRequest)).toBe(false);
    expect(matcher({ nextUrl: { pathname: "" } } as unknown as NextRequest)).toBe(false);
  });

  it("matches complex patterns", () => {
    const matcher = createRouteMatcher(["/product(.*)", "/category(.*)"]);

    expect(matcher({ nextUrl: { pathname: "/product" } } as unknown as NextRequest)).toBe(true);
    expect(matcher({ nextUrl: { pathname: "/product/123" } } as unknown as NextRequest)).toBe(true);
    expect(
      matcher({ nextUrl: { pathname: "/category/electronics" } } as unknown as NextRequest)
    ).toBe(true);
    expect(matcher({ nextUrl: { pathname: "/random" } } as unknown as NextRequest)).toBe(false);
  });
});
