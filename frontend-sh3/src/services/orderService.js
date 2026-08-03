import api from "./api";

export const orderService = {
  create: (event_id) => api.post(`/events/${event_id}/register`),
  uploadPayment: (orderId, formData) =>
    api.post(`/events/${orderId}/payment`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    }),
};
