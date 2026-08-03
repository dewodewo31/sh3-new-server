import api from "./api";

export const sponsorService = {
  getAll: (params) => api.get("/sponsors", { params }),
};
