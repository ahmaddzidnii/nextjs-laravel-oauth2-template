import axios from "axios";
import { useState } from "react";
import { getCookie } from "cookies-next";
import { useRouter } from "next/navigation";

export function useLogout() {
  const router = useRouter();
  const [isLoadingLogout, setIsLoadingLogout] = useState(false);
  const logout = ({ onError }: { onError?: (error: unknown) => void }) => {
    setIsLoadingLogout(true);
    axios
      .get("http://localhost:8000/api/auth/logout", {
        headers: {
          Authorization: `Bearer ${getCookie("access_token")}`,
        },
        withCredentials: true,
      })
      .then((data) => {
        setIsLoadingLogout(false);
        // TODO:invalidate cache user
        router.replace(process.env.NEZT_PUBLIC_DEFAULT_REDIRECT_AFTER_LOGOUT ?? "/login");
      })
      .catch((error) => {
        setIsLoadingLogout(false);
        console.error("Failed to logout:", error);
        onError?.(error);
      });
  };

  return { logout, isLoadingLogout };
}
