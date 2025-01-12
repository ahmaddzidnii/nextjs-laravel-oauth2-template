"use client";

import { getCookie } from "cookies-next";
import { useQuery } from "@tanstack/react-query";
import { createContext, useContext, useEffect, useState } from "react";

import { AuthUser } from "@/types";
import api from "@/features/auth/api/api";

interface AuthContextType {
  isLoading: boolean;
  user: AuthUser | null;
  isAuthenticated: boolean;
  getAccessToken: () => string | null;
}

export const AuthContext = createContext<AuthContextType | null>(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return context;
};

export const AuthContextProvider = ({ children }: { children: React.ReactNode }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const accsessToken = getCookie("access_token");

  useEffect(() => {
    if (!accsessToken) {
      setIsAuthenticated(false);
    }
  }, [accsessToken]);

  // // Query to fetch current user
  const { data, isLoading } = useQuery({
    queryKey: ["user"],
    queryFn: async () => {
      return api.get("/auth/me");
    },
    // enabled: !!accsessToken,
    enabled: false,
    // Retry only once
    retry: 1,
    refetchInterval: 1000 * 60 * 0.5, // Refetch every 30 seconds
  });

  const contextValue: AuthContextType = {
    user: data?.data
      ? {
          id: data?.data.user_id,
          username: data?.data.username,
          email: data?.data.email,
          avatar: data?.data.avatar,
          role: data?.data.role,
        }
      : null,
    isLoading,
    isAuthenticated,
    getAccessToken: () => (accsessToken as string) ?? null,
  };

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
};
