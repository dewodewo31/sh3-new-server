import { useState } from "react";
import { authService } from "@/src/services/authService";
import { profileService } from "@/src/services/profileService";
import api from "@/src/services/api";
import { useAuth as useAuthContext } from "@/src/contexts/AuthContext";
import { normalizeUser } from "@/src/lib/utils";

export default function useAuth() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const { setAuthUser } = useAuthContext();

    async function login(email, password) {
        setLoading(true);
        setError(null);
        try {
            const res = await authService.login(email, password);
            const token = res.data.token;

            localStorage.setItem("token", token);
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            const profileRes = await profileService.getProfile();
            const payload = profileRes.data.data || {};
            const participant = normalizeUser(payload.user, payload.participant);

            localStorage.setItem("user", JSON.stringify(participant));

            // 🔥 UPDATE AUTH CONTEXT
            setAuthUser(participant);

            return participant;
        } catch (err) {
            setError(err.response?.data?.message || "Login gagal.");
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            delete api.defaults.headers.common['Authorization'];
            setAuthUser(null);
            return null;
        } finally {
            setLoading(false);
        }
    }

    function logout() {
        authService.logout().catch(() => {});
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        delete api.defaults.headers.common['Authorization'];
        setAuthUser(null);
    }

    function getUser() {
        const user = localStorage.getItem("user");
        return user ? JSON.parse(user) : null;
    }

    function isLoggedIn() {
        return !!localStorage.getItem("token");
    }

    return { loading, error, login, logout, getUser, isLoggedIn };
}
