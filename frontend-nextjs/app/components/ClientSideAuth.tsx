"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { useAuth } from "@/features/auth/hooks/useAuth";
import { useUser } from "@/features/auth/hooks/useUser";

export const ClientSideAuth = () => {
  const [isClient, setIsClient] = useState(false);
  const { isAuthenticated } = useAuth();
  const { user, isLoading } = useUser();

  useEffect(() => {
    setIsClient(true);
  }, []);

  if (!isClient) {
    return null;
  }

  if (isLoading) {
    return <div className="text-center">Loading...</div>;
  }

  return (
    <>
      <pre
        className="mt-5 overflow-x-auto max-w-full [&::-webkit-scrollbar]:w-2
  [&::-webkit-scrollbar-track]:bg-gray-100
  [&::-webkit-scrollbar-thumb]:bg-gray-300
  dark:[&::-webkit-scrollbar-track]:bg-neutral-700
  dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500 pb-5"
      >
        {JSON.stringify({ isAuthenticated, user }, null, 2)}
      </pre>
      <div className="mt-5">
        {isAuthenticated ? (
          <Link
            className="text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            href="/dashboard"
          >
            Go to dashboard
          </Link>
        ) : (
          <Link
            className="text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            href="/login"
          >
            Go to login
          </Link>
        )}
      </div>
    </>
  );
};
