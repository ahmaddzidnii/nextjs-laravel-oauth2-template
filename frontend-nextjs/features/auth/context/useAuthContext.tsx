"use client";

import { createContext, useState } from "react";
import { CookieValueTypes, getCookie } from "cookies-next";
import { AuthContextType } from "@/types";

export const AuthContext = createContext<AuthContextType | null>(null);

export const AuthContextProvider = ({ children }: { children: React.ReactNode }) => {
  const accessToken = getCookie("access_token");
  const [token, setToken] = useState<CookieValueTypes | Promise<CookieValueTypes>>(accessToken);

  const contextValue: AuthContextType = {
    isAuthenticated: !!token,
    getAccessToken: () => (token as string) ?? null,
    setToken,
  };

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
};
