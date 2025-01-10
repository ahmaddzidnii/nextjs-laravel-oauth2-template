"use client";

import { useLoginWithGoogle } from "@/features/auth/hooks/useLoginWithGoogle";

export default function LoginPage() {
  const { login } = useLoginWithGoogle();
  return (
    <div className=" w-full h-screen flex justify-center items-center">
      <div className="w-[370px] bg-gray-200 flex flex-col justify-center items-center rounded-lg p-4 shadow-sm">
        <h1 className="text-4xl font-bold">Login Page</h1>

        <button
          className="w-full bg-white mt-5 rounded-md p-2"
          onClick={login}
        >
          Login with Google
        </button>
      </div>
    </div>
  );
}
