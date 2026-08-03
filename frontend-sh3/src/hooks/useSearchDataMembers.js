import { useState } from "react";
import useAuth from "./useAuth";
import Swal from "sweetalert2";
import { useRouter } from "next/navigation";

export default function useSearchDataMembers() {
    const [id, setId] = useState(""); // ini untuk email
    const [password, setPassword] = useState("");
    const [userData, setUserData] = useState(null);
    const [error, setError] = useState(null);
    const { loading, login } = useAuth();
    const router = useRouter();

    const requiredFields = [
        { key: "blood_type", label: "Golongan Darah" },
        { key: "emergency_contact", label: "Kontak Darurat" },
        { key: "emergency_phone", label: "Nomor Kontak Darurat" },
        { key: "medical_conditions", label: "Riwayat Alergi/Kondisi Medis" },
    ];

    async function checkTheID(e) {
        e.preventDefault();
        
        // Validasi
        if (!id || !password) {
            Swal.fire({
                icon: "warning",
                title: "Data Kurang",
                text: "Masukkan Email dan Password dulu!",
            });
            return;
        }

        setError(null);
        setUserData(null);

        // Login dengan email dan password (mengambil profile lengkap dari /profile)
        const user = await login(id, password);

        if (user) {
            // Cek field yang wajib diisi
            const missingFields = requiredFields
                .filter(field => !user[field.key])
                .map(field => field.label);

            if (missingFields.length > 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Data Belum Lengkap!",
                    html: `
                        <p>Lengkapi data berikut sebelum daftar event:</p>
                        <ul style="text-align:left; margin-top:8px">
                            ${missingFields.map(f => `<li>❌ ${f}</li>`).join("")}
                        </ul>
                    `,
                    confirmButtonText: "Lengkapi Sekarang",
                    showCancelButton: true,
                    cancelButtonText: "Nanti",
                }).then(result => {
                    if (result.isConfirmed) router.push("/members/detail");
                });
                return;
            }

            setUserData({
                id: user.hash_id,
                name: user.name,
                email: user.email,
                telp_number: user.phone ?? "-",
            });
        } else {
            setError("Email atau password salah.");
            Swal.fire({
                icon: "error",
                title: "Login Gagal",
                text: "Email atau password salah.",
            });
        }
    }

    return { 
        loading, 
        id, 
        setId, 
        password, 
        setPassword, 
        userData, 
        error, 
        checkTheID 
    };
}