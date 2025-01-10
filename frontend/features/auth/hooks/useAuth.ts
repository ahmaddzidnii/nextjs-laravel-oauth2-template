import { useContext } from "react";

import { AuthContext } from "@/features/auth/context/useAuthContext";

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthContextProvider");
  }
  return context;
};