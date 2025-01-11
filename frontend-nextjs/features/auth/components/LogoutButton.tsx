"use client";

import { useLoginWithGoogle } from "@/features/auth/hooks/useLoginWithGoogle";

export function LogoutButton() {
  const { logout, isLoadingLogout } = useLoginWithGoogle();

  const handleLogout = () => {
    logout({
      onError: (error) => {
        console.error("Failed to logout:", error);
      },
    });
  };
  return (
    <button
      onClick={handleLogout}
      className="text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
    >
      {isLoadingLogout ? "Loading..." : "Logout"}
    </button>
  );
}
