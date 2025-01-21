import axios from "axios";
import { toast } from "sonner";
import { useState } from "react";
import { getCookie } from "cookies-next";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";

export function useLogout() {
  const router = useRouter();
  const [isLoadingLogout, setIsLoadingLogout] = useState(false);

  const queryClient = useQueryClient();

  const logout = ({ onError }: { onError?: (error: unknown) => void }) => {
    setIsLoadingLogout(true);
    axios
      .get(`${process.env.NEXT_PUBLIC_BACKEND_URL}/auth/logout`, {
        headers: {
          Authorization: `Bearer ${getCookie("access_token")}`,
        },
        withCredentials: true,
      })
      .then((data) => {
        setIsLoadingLogout(false);
        // Remove user query from cache
        queryClient.removeQueries({
          queryKey: ["user"],
        });
        router.refresh();
      })
      .catch((error) => {
        setIsLoadingLogout(false);
        console.error("Failed to logout:", error);
        toast.error("Failed to logout");
        onError?.(error);
      });
  };

  return { logout, isLoadingLogout };
}
