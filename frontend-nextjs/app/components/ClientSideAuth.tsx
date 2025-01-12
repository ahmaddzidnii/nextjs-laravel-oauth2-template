"use client";

import { useAuth } from "@/features/auth/context/useAuthContext";
import { useEffect, useState } from "react";

export const ClientSideAuth = () => {
  const { isAuthenticated, isLoading, user, getAccessToken } = useAuth();
  const [isClient, setIsClient] = useState(false);

  useEffect(() => {
    setIsClient(true);
  }, []);

  if (!isClient) {
    return null;
  }

  return (
    <div>
      {isLoading ? (
        <p>Loading...</p>
      ) : (
        <div>
          <h1 className="text-4xl font-bold">Client Side Rendering</h1>
          <pre className="mt-5 overflow-x-auto max-w-full">
            {JSON.stringify({ isAuthenticated, accsessToken: getAccessToken(), user }, null, 2)}
          </pre>
        </div>
      )}
    </div>
  );
};
