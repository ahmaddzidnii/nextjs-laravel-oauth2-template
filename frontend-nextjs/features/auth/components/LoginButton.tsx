"use client";

import { useLoginWithGoogle } from "@/features/auth/hooks/useLoginWithGoogle";

export default function LoginButton() {
  const { login, isLoadingLogin } = useLoginWithGoogle();
  return (
    <button
      onClick={login}
      className="w-full bg-white mt-5 rounded-md p-2"
    >
      {isLoadingLogin ? "Loading..." : "Login With Google"}
    </button>
  );
}
