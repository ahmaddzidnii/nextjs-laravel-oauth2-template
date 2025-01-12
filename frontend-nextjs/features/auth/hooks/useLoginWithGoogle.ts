import axios from "axios";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { useGoogleLogin } from "@react-oauth/google";

import { useAuth } from "@/features/auth/hooks/useAuth";

export function useLoginWithGoogle() {
  const router = useRouter();

  const [isLoadingLogin, setIsLoadingLogin] = useState(false);

  const { setToken } = useAuth();

  const login = useGoogleLogin({
    flow: "auth-code",
    onError: (error) => {
      console.error("Google login error:", error);
    },
    onSuccess: async ({ code }) => {
      try {
        setIsLoadingLogin(true);
        const response = await axios.get<{
          status_code: number;
          message: string;
          data: { access_token: string };
        }>("http://localhost:8000/api/auth/google/callback", {
          params: { code },
          withCredentials: true,
        });
        setIsLoadingLogin(false);
        setToken(response.data.data.access_token);
        router.replace(process.env.NEXT_PUBLIC_DEFAULT_REDIRECT_AFTER_LOGIN ?? "/dashboard");
      } catch (error) {
        setIsLoadingLogin(false);
        console.error("Failed to login with Google:", error);
      }
    },
  });

  return { login, isLoadingLogin };
}
