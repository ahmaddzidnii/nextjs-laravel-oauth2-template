import "server-only";

import * as jose from "jose";
import { cookies } from "next/headers";

interface AuthUser {
  id: string;
  username: string;
  email: string;
  avatar: string;
  role: string;
}

type AuthObject = {
  isAuthenticated: boolean;
  user: AuthUser | null;
  getAccessToken: () => Promise<string | null>;
};

const createAuthObject = (isAuthenticated: boolean, user: AuthUser | null): AuthObject => ({
  isAuthenticated,
  user,
  getAccessToken: async () => {
    const accessToken = (await cookies()).get("access_token")?.value;
    return accessToken ?? null;
  },
});

async function attemptTokenRefresh(refreshToken: string) {
  try {
    const response = await fetch("http://127.0.0.1:8000/api/auth/refresh", {
      credentials: "include",
      method: "GET",
      headers: {
        Authorization: `Bearer ${refreshToken}`,
      },
    });

    if (!response.ok) {
      throw new Error(`Refresh token failed with status: ${response.status}`);
    }

    const data = await response.json();
    return data.data.access_token;
  } catch (error) {
    console.error("Token refresh failed:", error);
    throw error;
  }
}

export async function getAuth(): Promise<AuthObject> {
  try {
    const accessToken = (await cookies()).get("access_token")?.value;
    const refreshToken = (await cookies()).get("refresh_token")?.value;

    // Jika tidak ada kedua token, return unauthenticated
    if (!accessToken && !refreshToken) {
      return createAuthObject(false, null);
    }

    // Jika ada refresh token tapi tidak ada access token, coba refresh
    if (!accessToken && refreshToken) {
      try {
        console.log("Access token missing, attempting refresh...");
        const newAccessToken = await attemptTokenRefresh(refreshToken);

        // Verify the new access token
        const { payload } = await jose.jwtVerify(
          newAccessToken,
          new TextEncoder().encode(process.env.JWT_SECRET),
          { algorithms: ["HS256"] }
        );

        return createAuthObject(true, {
          id: payload.sub as string,
          username: payload.username as string,
          email: payload.email as string,
          avatar: payload.avatar as string,
          role: payload.role as string,
        });
      } catch (refreshError) {
        console.error("Refresh attempt failed:", refreshError);
        return createAuthObject(false, null);
      }
    }

    // Normal flow with access token verification
    try {
      const { payload } = await jose.jwtVerify(
        accessToken!,
        new TextEncoder().encode(process.env.JWT_SECRET),
        { algorithms: ["HS256"] }
      );

      return createAuthObject(true, {
        id: payload.sub as string,
        username: payload.username as string,
        email: payload.email as string,
        avatar: payload.avatar as string,
        role: payload.role as string,
      });
    } catch (error: any) {
      // Handle expired token
      if (error.code === "ERR_JWT_EXPIRED" && refreshToken) {
        console.log("Access token expired, refreshing...");

        try {
          const newAccessToken = await attemptTokenRefresh(refreshToken);

          const { payload } = await jose.jwtVerify(
            newAccessToken,
            new TextEncoder().encode(process.env.JWT_SECRET),
            { algorithms: ["HS256"] }
          );

          return createAuthObject(true, {
            id: payload.sub as string,
            username: payload.username as string,
            email: payload.email as string,
            avatar: payload.avatar as string,
            role: payload.role as string,
          });
        } catch (refreshError) {
          console.error("Refresh attempt failed:", refreshError);
          return createAuthObject(false, null);
        }
      }

      // Handle other errors

      return createAuthObject(false, null);
    }
  } catch (error) {
    //  Handle other errors
    return createAuthObject(false, null);
  }
}
