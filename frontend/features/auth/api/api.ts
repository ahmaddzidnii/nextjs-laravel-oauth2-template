import axios from "axios";

// Define public routes that don't need authentication
const publicRoutes = [
  "/auth/login",
  "/auth/google/callback",
  "/auth/register",
  // Add other public routes as needed
];

const TOKEN_KEY = "access_token"; // Centralize token key name

const api = axios.create({
  baseURL: "http://localhost:8000/api",
  withCredentials: true,
});

// Request Interceptor
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY); // Use consistent key

  // Add token only if it exists and the route is not public
  if (token && !isPublicRoute(config.url)) {
    config.headers["Authorization"] = `Bearer ${token}`;
  }
  return config;
});

// Response Interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // Check if the error is 401 and the route needs authentication
    if (
      error.response?.status === 401 &&
      !originalRequest._retry &&
      !isPublicRoute(originalRequest.url)
    ) {
      originalRequest._retry = true;

      try {
        // Request new access token
        const { data } = await axios.get(`${api.defaults.baseURL}/auth/refresh`, {
          withCredentials: true,
        });

        // Save new access token
        localStorage.setItem(TOKEN_KEY, data.data.access_token); // Use consistent key

        // Update the original request with new token
        originalRequest.headers["Authorization"] = `Bearer ${data.data.access_token}`;

        // Retry the failed request
        return api.request(originalRequest);
      } catch (refreshError) {
        // If refresh fails, clear token and reject
        localStorage.removeItem(TOKEN_KEY); // Use consistent key
        return Promise.reject(refreshError);
      }
    }

    // For public routes or other errors, just reject
    return Promise.reject(error);
  }
);

// Helper function to check if a route is public
function isPublicRoute(url: string | undefined): boolean {
  if (!url) return false;
  return publicRoutes.some((route) => url.includes(route));
}

export default api;
