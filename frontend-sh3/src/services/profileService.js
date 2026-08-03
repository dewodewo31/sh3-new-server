import api from "./api";

export const profileService = {
    getProfile: () => api.get("/profile"),
    update: (data) => api.put("/profile", data),
    uploadPhoto: (formData) => api.post("/profile/photo", formData, {
        headers: { "Content-Type": "multipart/form-data" }
    }),
};
