import axios from "axios";
import { useState } from "react";
import { useGoogleLogin } from "@react-oauth/google";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";

export function useLoginWithGoogle() {
  const router = useRouter();

  const [isLoadingLogin, setIsLoadingLogin] = useState(false);

  const queryClient = useQueryClient();

  const login = useGoogleLogin({
    flow: "auth-code",
    onError: (error) => {
      console.error("Google login error:", error);
    },
    onSuccess: async ({ code }) => {
      try {
        setIsLoadingLogin(true);
        await axios.get<{
          status_code: number;
          message: string;
          data: { access_token: string };
        }>("http://localhost:8000/api/auth/google/callback", {
          params: { code },
          withCredentials: true,
        });
        setIsLoadingLogin(false);
        router.replace(process.env.NEXT_PUBLIC_DEFAULT_REDIRECT_AFTER_LOGIN ?? "/dashboard");
        queryClient.invalidateQueries({
          queryKey: ["user"],
        });
      } catch (error) {
        setIsLoadingLogin(false);
        console.error("Failed to login with Google:", error);
      }
    },
  });

  return { login, isLoadingLogin };
}
