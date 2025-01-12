"use client";

import { useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { CookieValueTypes, getCookie } from "cookies-next";
import { createContext, useEffect, useState } from "react";

import api from "@/features/auth/api/api";
import { AuthContextType, AuthUser } from "@/types";

export const AuthContext = createContext<AuthContextType | null>(null);

export const AuthContextProvider = ({ children }: { children: React.ReactNode }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const router = useRouter();

  const accessToken = getCookie("access_token");

  // Query to fetch current user with enabled condition
  const { data, isLoading, isSuccess, isError } = useQuery({
    queryKey: ["user"],
    queryFn: async () => {
      return api.get("/auth/me");
    },
    // Only run query if we have an access token
    enabled: !!accessToken,
    // Retry only once
    retry: false,
    refetchInterval: 1000 * 60 * 0.5, // Refetch every 30 seconds
  });

  useEffect(() => {
    if (!accessToken) {
      setIsAuthenticated(false);
    }

    if (isError) {
      setIsAuthenticated(false);
    }

    if (isSuccess) {
      setIsAuthenticated(true);
    }
  }, [accessToken, isSuccess, isError]);

  const user: AuthUser = {
    id: data?.data.data.user_id,
    username: data?.data.data.username,
    email: data?.data.data.email,
    avatar: data?.data.data.avatar,
    role: data?.data.data.role,
  };

  const contextValue: AuthContextType = {
    user: isAuthenticated ? user : null,
    isLoading: !!accessToken && isLoading, // Only show loading state if we have a token
    isAuthenticated,
    getAccessToken: () => (accessToken as string) ?? null,
  };

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
};
