"use client";
import Container from "@/src/components/Container";
import InputType from "@/src/components/Inputs";
import SelectInput from "@/src/components/SelectInput";
import { useState } from "react";
import { memberService } from "@/src/services/memberService";
import { profileService } from "@/src/services/profileService";
import api from "@/src/services/api";
import { useAuth } from "@/src/contexts/AuthContext";
import ImageUpload from "@/src/components/ImageUpload";
import Swal from "sweetalert2";
import { RevealSection } from "@/src/components/RevealSection";
import PasswordInput from "@/src/components/passwordInput";
import BatikOverlay from "@/src/components/BatikOverlay";
import { normalizeUser } from "@/src/lib/utils";

const genderOptions = [
    { value: "male", label: "Laki-laki" },
    { value: "female", label: "Perempuan" },
];

const bloodTypeOptions = [
    { value: "A", label: "A" },
    { value: "B", label: "B" },
    { value: "AB", label: "AB" },
    { value: "O", label: "O" },
];

export default function Members() {
    const [gender, setGender] = useState("");
    const [bloodType, setBloodType] = useState("");
    const [photo, setPhoto] = useState(null);
    const [identityPhoto, setIdentityPhoto] = useState(null);
    const [submitLoading, setSubmitLoading] = useState(false);
    const [passwordConfirm, setPasswordConfirm] = useState("");
    const [password, setPassword] = useState("");
    const { setAuthUser } = useAuth();
    const [formData, setFormData] = useState({
        name: "",
        email: "",
        phone: "",
        date_of_birth: "",
        emergency_contact: "",
        emergency_phone: "",
        medical_conditions: "",
    });

    // 🔥 State untuk validasi password (tetap pakai state untuk real-time)
    const [passwordError, setPasswordError] = useState("");
    const [confirmError, setConfirmError] = useState("");

    function handleFormChange(e) {
        setFormData((prev) => ({
            ...prev,
            [e.target.name]: e.target.value,
        }));
    }

    // 🔥 Validasi password real-time
    function handlePasswordChange(e) {
        const newPassword = e.target.value;
        setPassword(newPassword);
        
        // Cek panjang password
        if (newPassword.length > 0 && newPassword.length < 6) {
            setPasswordError("Password minimal 6 karakter!");
        } else {
            setPasswordError("");
        }
        
        // Cek konfirmasi password
        if (passwordConfirm.length > 0 && newPassword !== passwordConfirm) {
            setConfirmError("Password tidak cocok!");
        } else if (passwordConfirm.length > 0 && newPassword === passwordConfirm) {
            setConfirmError("");
        }
    }

    // 🔥 Validasi konfirmasi password real-time
    function handleConfirmPasswordChange(e) {
        const newConfirm = e.target.value;
        setPasswordConfirm(newConfirm);
        
        if (newConfirm.length > 0 && password !== newConfirm) {
            setConfirmError("Password tidak cocok!");
        } else if (newConfirm.length > 0 && password === newConfirm) {
            setConfirmError("");
        } else {
            setConfirmError("");
        }
    }

    async function handleRegister(e) {
        e.preventDefault();

        if (!formData.name || !formData.email || !formData.phone || !formData.date_of_birth) {
            Swal.fire({ icon: "warning", title: "Pastikan data wajib terisi semua!" });
            return;
        }

        // 🔥 Validasi password sebelum submit
        if (password !== passwordConfirm) {
            setConfirmError("Password tidak cocok!");
            Swal.fire({ icon: "warning", title: "Password tidak cocok!" });
            return;
        }
        if (password.length < 6) {
            setPasswordError("Password minimal 6 karakter!");
            Swal.fire({ icon: "warning", title: "Password minimal 6 karakter!" });
            return;
        }

        setSubmitLoading(true);
        try {
            const form = new FormData();
            form.append("name", formData.name);
            form.append("email", formData.email);
            form.append("phone", formData.phone);
            form.append("date_of_birth", formData.date_of_birth);
            form.append("password", password);
            form.append("password_confirmation", passwordConfirm);
            form.append("gender", gender);
            if (bloodType) form.append("blood_type", bloodType);
            if (formData.emergency_contact) form.append("emergency_contact", formData.emergency_contact);
            if (formData.emergency_phone) form.append("emergency_phone", formData.emergency_phone);
            if (formData.medical_conditions) form.append("medical_conditions", formData.medical_conditions);

            const res = await memberService.register(form);
            const { user, token } = res.data;

            // Simpan token + set header otomatis
            localStorage.setItem("token", token);
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            const userData = normalizeUser(user, user.participants?.[0]);
            localStorage.setItem("user", JSON.stringify(userData));
            setAuthUser(userData);

            // 🔥 Upload foto profil setelah registrasi (endpoint register tidak menerima file)
            if (photo) {
                const photoForm = new FormData();
                photoForm.append("avatar", photo);
                await profileService.uploadPhoto(photoForm).catch(() => {});
            }

            Swal.fire({
                icon: "success",
                title: "Registrasi Berhasil!",
                html: `
                    <p>Selamat datang, <strong>${userData.name}</strong>!</p>
                    <p>Login dengan email kamu: <strong>${userData.email}</strong></p>
                `,
            });

            // Reset form
            setFormData({
                name: "",
                email: "",
                phone: "",
                date_of_birth: "",
                emergency_contact: "",
                emergency_phone: "",
                medical_conditions: "",
            });
            setGender("");
            setBloodType("");
            setPhoto(null);
            setIdentityPhoto(null);
            setPassword("");
            setPasswordConfirm("");
            setPasswordError("");
            setConfirmError("");

        } catch (err) {
            const message = err.response?.data?.message || "Terjadi kesalahan, coba lagi.";
            const errors = err.response?.data?.errors;

            Swal.fire({
                icon: "error",
                title: "Registrasi Gagal!",
                text: errors ? Object.values(errors).flat().join(", ") : message,
            });
        } finally {
            setSubmitLoading(false);
        }
    }

    return (
        <Container className="flex flex-col w-full">
            <div className="relative bg-linear-to-br from-primary-light via-primary-light-active to-primary-light">
                <BatikOverlay />
                <div className="gap-y-8 px-4 md:px-0 max-w-306 mx-auto">
                    <RevealSection direction="up">
                        <div className="flex flex-col flex-1 items-center justify-center p-8">
                            <h1 className="text-primary-darker text-5xl font-bold font-young mt-16">
                                Ayo jadi bagian dari kami!
                            </h1>
                        </div>

                        <div className="flex flex-col gap-x-16 md:grid md:grid-cols-3">
                            <form onSubmit={handleRegister} className="col-span-1 flex flex-col md:col-span-2 gap-4">

                                {/* Data Dasar */}
                                <h3 className="text-xl font-bold font-young">Data Diri</h3>

                                <InputType 
                                    label="Nama Lengkap" 
                                    id="name" 
                                    required 
                                    type="text" 
                                    name="name"
                                    placeholder="John Doe" 
                                    className="flex flex-col gap-2"
                                    value={formData.name} 
                                    onChange={handleFormChange} 
                                />
                                <InputType 
                                    label="Email" 
                                    id="email" 
                                    required 
                                    type="email" 
                                    name="email"
                                    placeholder="you@example.com" 
                                    className="flex flex-col gap-2"
                                    value={formData.email} 
                                    onChange={handleFormChange} 
                                />
                                
                                {/* 🔥 PASSWORD dengan error */}
                                <div className="relative">
                                    <PasswordInput
                                        label="Password"
                                        id="password"
                                        required
                                        name="password"
                                        placeholder="••••••••"
                                        className="flex flex-col gap-2"
                                        value={password}
                                        onChange={handlePasswordChange}
                                    />
                                    {passwordError && (
                                        <p className="text-red-500 text-sm mt-1 flex items-center gap-1">
                                            {passwordError}
                                        </p>
                                    )}
                                </div>

                                {/* 🔥 KONFIRMASI PASSWORD dengan error */}
                                <div className="relative">
                                    <PasswordInput
                                        label="Konfirmasi Password"
                                        id="password_confirmation"
                                        required
                                        name="password_confirmation"
                                        placeholder="••••••••"
                                        className="flex flex-col gap-2"
                                        value={passwordConfirm}
                                        onChange={handleConfirmPasswordChange}
                                    />
                                    {confirmError && (
                                        <p className="text-red-500 text-sm mt-1 flex items-center gap-1">
                                            {confirmError}
                                        </p>
                                    )}
                                </div>

                                <InputType 
                                    label="Nomor Telepon/WA" 
                                    type="text" 
                                    id="phone" 
                                    required 
                                    name="phone"
                                    placeholder="08123456789" 
                                    className="flex flex-col gap-2"
                                    value={formData.phone} 
                                    onChange={handleFormChange} 
                                />
                                <SelectInput 
                                    id="gender" 
                                    name="gender" 
                                    label="Gender" 
                                    options={genderOptions} 
                                    value={gender} 
                                    placehold="Pilih Gender..."
                                    onChange={(e) => setGender(e.target.value)} 
                                />
                                <InputType 
                                    label="Tanggal Lahir" 
                                    type="date" 
                                    id="date_of_birth" 
                                    required 
                                    name="date_of_birth"
                                    className="flex flex-col gap-2"
                                    value={formData.date_of_birth} 
                                    onChange={handleFormChange} 
                                />
                                <SelectInput 
                                    id="blood_type" 
                                    name="blood_type" 
                                    label="Golongan Darah" 
                                    options={bloodTypeOptions} 
                                    value={bloodType} 
                                    placehold="Pilih Golongan Darah..."
                                    onChange={(e) => setBloodType(e.target.value)} 
                                />

                                {/* Kontak Darurat */}
                                <hr className="border-t-2 border-neutral-normal mt-2" />
                                <h3 className="text-xl font-bold font-young">Kontak Darurat</h3>
                                <InputType 
                                    label="Nama Kontak Darurat" 
                                    id="emergency_contact" 
                                    type="text" 
                                    name="emergency_contact" 
                                    placeholder="Nama keluarga/teman"
                                    className="flex flex-col gap-2"
                                    value={formData.emergency_contact} 
                                    onChange={handleFormChange} 
                                />
                                <InputType 
                                    label="Nomor Kontak Darurat" 
                                    id="emergency_phone" 
                                    type="text" 
                                    name="emergency_phone" 
                                    placeholder="08123456789"
                                    className="flex flex-col gap-2"
                                    value={formData.emergency_phone} 
                                    onChange={handleFormChange} 
                                />

                                {/* Info Kesehatan */}
                                <hr className="border-t-2 border-neutral-normal mt-2" />
                                <h3 className="text-xl font-bold font-young">Info Kesehatan</h3>
                                <div className="flex flex-col gap-2">
                                    <label className="text-xl font-medium">Riwayat Alergi / Kondisi Medis</label>
                                    <textarea
                                        name="medical_conditions"
                                        placeholder="Contoh: alergi debu, makanan laut, dll"
                                        className="border-tertiary-normal p-3 text-lg bg-white border-2 rounded-md"
                                        rows={3}
                                        value={formData.medical_conditions}
                                        onChange={handleFormChange}
                                    />
                                </div>

                                {/* Foto Profil */}
                                <hr className="border-t-2 border-neutral-normal mt-2" />
                                <h3 className="text-xl font-bold font-young">Foto Profil</h3>
                                <ImageUpload 
                                    id="photo" 
                                    label="Foto Profil"
                                    onChange={(file) => setPhoto(file)} 
                                />

                                <button
                                    className={`flex justify-center items-center mb-8 rounded-md ${submitLoading ? "bg-neutral-normal " : "bg-secondary-bg hover:bg-secondary-bg-hover"} active:bg-secondary-bg-active h-16 font-bold text-xl text-white mt-4 md:text-3xl font-young`}
                                    type="submit"
                                    disabled={submitLoading}
                                >
                                    {submitLoading ? "Memproses..." : "Registrasi Member"}
                                </button>
                            </form>

                            <div className="bg-primary-light rounded-lg gap-x-4 p-4 h-fit border-primary-normal border-2">
                                <div className="flex flex-col">
                                    <h3 className="text-2xl font-bold font-young text-primary-normal">Benefits Member</h3>
                                </div>
                                <div className="flex flex-col">
                                    <ol className="list-decimal list-outside p-2 pl-8 text-2xl text-primary-normal">
                                        <li className="mt-2">Mendapatkan Informasi yang Up-to-Date</li>
                                        <li className="mt-2">Mendapatkan teman yang banyak</li>
                                        <li className="mt-2">Sesi Down-Down setiap event.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </RevealSection>
                </div>
            </div>
        </Container>
    );
}