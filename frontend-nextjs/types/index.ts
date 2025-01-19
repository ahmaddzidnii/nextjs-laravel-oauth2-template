export interface AuthUser {
  id: string;
  name: string;
  email: string;
  avatar: string;
  role: string;
}

export interface AuthContextType {
  isAuthenticated: boolean;
  getAccessToken: () => string | null;
  setToken: (token: string) => void;
}
