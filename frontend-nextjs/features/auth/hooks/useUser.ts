"use client";

import { useQuery } from "@tanstack/react-query";

import { AuthUser } from "@/types";
import { useAuth } from "./useAuth";
import api from "@/features/auth/api/api";

export const useUser = () => {
  const { isAuthenticated } = useAuth();
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["user"],
    queryFn: async () => {
      const response = await api.get("/users");
      return response.data.data;
    },
    enabled: isAuthenticated,
    refetchOnWindowFocus: false,
    retry: false,
  });

  const user: AuthUser = {
    id: data?.id as string,
    name: data?.name as string,
    email: data?.email as string,
    avatar: data?.avatar as string,
    role: data?.role as string,
  };

  return { user: data ? user : null, isLoading, isError, error };
};
