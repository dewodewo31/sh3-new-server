import api from "./api";

export const galleryService = {
  getAll: () => api.get("/galleries"),
};
