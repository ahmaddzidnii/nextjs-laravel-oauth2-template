"use client";

import { useQuery } from "@tanstack/react-query";
import { CookieValueTypes, deleteCookie, getCookie } from "cookies-next";
import { createContext, useEffect, useState } from "react";

import api from "@/features/auth/api/api";
import { AuthContextType, AuthUser } from "@/types";

export const AuthContext = createContext<AuthContextType | null>(null);

export const AuthContextProvider = ({ children }: { children: React.ReactNode }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  const accessToken = getCookie("access_token");
  const [token, setToken] = useState<CookieValueTypes | Promise<CookieValueTypes>>(accessToken);

  // Query to fetch current user with enabled condition
  const { data, isLoading, isSuccess, isError, error } = useQuery({
    queryKey: ["user"],
    queryFn: async () => {
      return api.get("/users");
    },
    refetchOnWindowFocus: false,
    retry: false,
  });

  useEffect(() => {
    const handleAuthState = () => {
      if (!token) {
        setIsAuthenticated(false);
        return;
      }

      if (isError) {
        setIsAuthenticated(false);
        return;
      }

      if (isSuccess && data?.data.data) {
        setIsAuthenticated(true);
      }
    };

    handleAuthState();
  }, [token, isSuccess, isError, data?.data.data]);

  const user: AuthUser = {
    id: data?.data.data?.id,
    name: data?.data.data?.name,
    email: data?.data.data?.email,
    avatar: data?.data.data?.avatar,
    role: data?.data.data?.role,
  };

  const contextValue: AuthContextType = {
    user: isAuthenticated ? user : null,
    isLoading: !!token && isLoading,
    isAuthenticated,
    getAccessToken: () => (token as string) ?? null,
    setToken,
  };

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
};
