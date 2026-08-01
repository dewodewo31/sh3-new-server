import api from "./api";

export const authService = {
    login: (email, password) => api.post("/auth/login", { email, password }),
    logout: () => api.post("/auth/logout"),
    getProfile: () => api.get("/auth/me"),
    register: (data) =>
        api.post("/auth/register", data, {
            headers: { "Content-Type": "multipart/form-data" },
        }),
};
