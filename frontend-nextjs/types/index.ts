export interface AuthUser {
  id: string;
  username: string;
  email: string;
  avatar: string;
  role: string;
}

export interface AuthContextType {
  isLoading: boolean;
  user: AuthUser | null;
  isAuthenticated: boolean;
  getAccessToken: () => string | null;
  setToken: (token: string) => void;
}
