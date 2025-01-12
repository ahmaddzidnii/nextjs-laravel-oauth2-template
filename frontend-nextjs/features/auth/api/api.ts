import axios from "axios";
import { getCookie, setCookie, deleteCookie } from "cookies-next";

// Create axios instance
const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_BACKEND_URL, // Replace with your API base URL
  withCredentials: true, // Ensure cookies are sent with requests
});

// Function to refresh token
const refreshAccessToken = async () => {
  try {
    const refreshToken = getCookie("refresh_token");
    if (!refreshToken) {
      return null;
    }

    const response = await axios.get(`${process.env.NEXT_PUBLIC_BACKEND_URL}/auth/refresh`, {
      withCredentials: true,
    });

    const { access_token } = response.data.data;

    // Update cookies with new tokens
    setCookie("access_token", access_token);

    return access_token;
  } catch (error) {
    console.error("Failed to refresh token", error);
    throw error;
  }
};

// Axios interceptor
api.interceptors.response.use(
  (response) => response, // Pass through successful responses
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true; // Mark this request as retried

      try {
        const newAccessToken = await refreshAccessToken();
        originalRequest.headers["Authorization"] = `Bearer ${newAccessToken}`;
        return api(originalRequest); // Retry the original request with the new token
      } catch (refreshError) {
        console.error("Refresh token failed", refreshError);
        deleteCookie("access_token");
        return null;
      }
    }

    return Promise.reject(error);
  }
);

export default api;
