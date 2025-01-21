"use client";

import React from "react";

import { useLoginWithGoogle } from "@/features/auth/hooks/useLoginWithGoogle";

const SocialLoginComponent = () => {
  const { login } = useLoginWithGoogle();
  const handleGoogleLogin = () => {
    login();
  };

  return (
    <div className="w-full flex gap-5">
      <button
        onClick={handleGoogleLogin}
        className="w-full border rounded-sm p-3 flex items-center justify-center hover:bg-gray-100 hover:scale-105 transition-all"
      >
        <div className="bg-cover bg-center bg-[url('/img/google.png')] size-6" />
      </button>
      <button className="w-full border rounded-sm p-3 flex items-center justify-center hover:bg-gray-100 hover:scale-105 transition-all">
        <div className="bg-cover bg-center bg-[url('/img/github.png')] size-6" />
      </button>
    </div>
  );
};

export default SocialLoginComponent;
