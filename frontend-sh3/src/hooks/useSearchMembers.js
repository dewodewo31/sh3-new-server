import Swal from "sweetalert2";
import { useState } from "react";
import useAuth from "./useAuth";
import { useAuth as useAuthContext } from "@/src/contexts/AuthContext"; // ← TAMBAHKAN

export default function useSearchMembers() {
    const [searchId, setSearchId] = useState("");
    const [password, setPassword] = useState("");
    const [userData, setUserData] = useState(null);
    const { loading, error, login } = useAuth();
    const { setAuthUser } = useAuthContext(); // ← AMBIL setAuthUser

    function handleChange(e) {
        setSearchId(e.target.value);
    }

    function handlePasswordChange(e) {
        setPassword(e.target.value);
    }

    async function handleSearch(e) {
        e.preventDefault();
        
        if (!searchId || !password) {
            Swal.fire({
                icon: "warning",
                title: "Data Kurang",
                text: "Masukkan Email dan Password dulu!",
            });
            return;
        }

        const user = await login(searchId, password);

        if (user) {
            setUserData(user);
            // 🔥 UPDATE AUTH CONTEXT - biar navbar berubah!
            setAuthUser(user);
            
            Swal.fire({
                icon: "success",
                title: "Login Berhasil!",
                html: `
                    <p>Nama: ${user.name}</p>
                    <p>Email: ${user.email}</p>
                    <p>Tipe: ${user.participant_type}</p>
                `,
            });
        } else {
            setUserData(null);
            Swal.fire({
                icon: "error",
                title: "Login Gagal",
                text: "Email atau password salah.",
            });
        }
    }

    return { 
        loading, 
        searchId, 
        setSearchId, 
        password, 
        handlePasswordChange, 
        handleSearch, 
        handleChange, 
        userData,
        setUserData,
    };
}