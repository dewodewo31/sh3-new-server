import api from "./api";

export const merchandiseService = {
    // Public
    getAll: (params) => api.get("/merchandise", { params }),
    getById: (id) => api.get(`/merchandise/${id}`),

    // Auth
    createOrder: (data) => api.post("/merchandise/order", data),
    uploadPayment: (orderId, formData) =>
        api.post(`/merchandise/orders/${orderId}/payment`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        }),
    getMyOrders: () => api.get("/merchandise/orders"),
    getOrderDetail: (id) => api.get(`/merchandise/orders/${id}`),
    cancelOrder: (id) => api.post(`/merchandise/orders/${id}/cancel`),
};
