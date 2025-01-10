"use client";

import React, { createContext, useContext } from "react";
import { useGoogleLogin } from "@react-oauth/google";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import axios from "axios";
import api from "../api/api";
import { useRouter } from "next/navigation";

// Types
interface User {
  user_id: string;
  username: string;
  email: string;
  avatar: string;
  role: "user" | "admin";
}

interface ResponseUser {
  status_code: number;
  message: string;
  data: {
    user: User;
  };
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  loginWithGoogle: () => void;
  logout: () => void;
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
  // const queryClient = useQueryClient();
  const router = useRouter();

  // // Query to fetch current user
  // const { data: user, isLoading } = useQuery({
  //   queryKey: ["user"],
  //   queryFn: async () => {
  //     try {
  //       const response = await api.get<ResponseUser>("/auth/me");
  //       return response.data.data.user;
  //     } catch (error) {
  //       console.log(error);
  //       // If unauthorized or any error, return null
  //       return null;
  //     }
  //   },
  //   // Retry only once if failed
  //   retry: 1,
  //   refetchInterval: 1000 * 60 * 0.5, // Refetch every 30 seconds
  // });

  // Google login handler
  const loginWithGoogle = useGoogleLogin({
    flow: "auth-code",
    onSuccess: async ({ code }) => {
      try {
        const responseToken = await axios.get<{
          status_code: number;
          message: string;
          data: { access_token: string };
        }>("http://localhost:8000/api/auth/google/callback", {
          params: { code },
          withCredentials: true,
        });

        router.refresh();
        // // Store token and invalidate queries
        // localStorage.setItem("access_token", responseToken.data.data.access_token);
        // queryClient.invalidateQueries({ queryKey: ["user"] });
        // router.replace("/dashboard");
      } catch (error) {
        console.error("Failed to login with Google:", error);
      }
    },
    onError: (error) => {
      console.error("Google login error:", error);
    },
  });

  // // Logout mutation
  // // Logout mutation
  // const { mutate: logoutMutate } = useMutation({
  //   mutationFn: () => {
  //     return api.get("/auth/logout", {
  //       params: { access_token: localStorage.getItem("access_token") },
  //     });
  //   },
  //   onSuccess: () => {
  //     localStorage.removeItem("access_token");
  //     queryClient.removeQueries({ queryKey: ["user"] });
  //     router.replace("/login");
  //   },
  //   onError: (error) => {
  //     console.error("Failed to logout:", error);
  //   },
  // });

  // const contextValue: AuthContextType = {
  //   user: user || null,
  //   isLoading,
  //   isAuthenticated: !!user,
  //   loginWithGoogle,
  //   logout: () => logoutMutate(),
  // };

  const contextValue: AuthContextType = {
    user: null,
    isLoading: false,
    isAuthenticated: false,
    loginWithGoogle,
    logout: () => {},
  };

  return <AuthContext.Provider value={contextValue}>{children}</AuthContext.Provider>;
};
