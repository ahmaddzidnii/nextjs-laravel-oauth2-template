import axios from "axios";
import { useRouter } from "next/navigation";
import { useGoogleLogin } from "@react-oauth/google";
import { getCookie } from "cookies-next";

export function useLoginWithGoogle() {
  const router = useRouter();
  const login = useGoogleLogin({
    flow: "auth-code",
    onError: (error) => {
      console.error("Google login error:", error);
    },
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

        router.replace(process.env.NEXT_PUBLIC_DEFAULT_REDIRECT_AFTER_LOGIN ?? "/dashboard");
      } catch (error) {
        console.error("Failed to login with Google:", error);
      }
    },
  });

  const logout = ({ onError }: { onError?: (error: unknown) => void }) => {
    axios
      .get("http://localhost:8000/api/auth/logout", {
        params: { access_token: getCookie("access_token") },
        withCredentials: true,
      })
      .then((data) => {
        // invalidate cache user
        router.replace(process.env.NEZT_PUBLIC_DEFAULT_REDIRECT_AFTER_LOGOUT ?? "/login");
      })
      .catch((error) => {
        console.error("Failed to logout:", error);
        onError?.(error);
      });
  };

  return { login, logout };
}
