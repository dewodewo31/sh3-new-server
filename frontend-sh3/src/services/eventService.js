import api from "./api";
import { assetUrl } from "@/src/lib/utils";

export const eventService = {
  getAll: (params) => api.get("/events", { params }),
  getUpcoming: () => api.get("/events/upcoming"),
  getById: (id) => api.get(`/events/${id}`),
  getParticipants: (id) => api.get(`/events/${id}/participants`),
  getMyEvents: () => api.get("/my-events"),
  register: (id, data) =>
    api.post(`/events/${id}/register`, data, {
      headers: { "Content-Type": "multipart/form-data" },
    }),
  book: (id) => api.post(`/events/${id}/register`),
};

// Konversi path gambar event (mis. "uploads/events/x.jpg") ke URL penuh.
export function eventImage(event) {
  return assetUrl(event?.image_url || event?.image);
}
