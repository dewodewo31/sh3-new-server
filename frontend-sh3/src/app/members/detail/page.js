/* eslint-disable @next/next/no-img-element */
"use client";

import useSearchMembers from "@/src/hooks/useSearchMembers";
import InputType from "@/src/components/Inputs";
import SelectInput from "@/src/components/SelectInput";
import Container from "@/src/components/Container";
import ImageUpload from "@/src/components/ImageUpload";
import PasswordInput from "@/src/components/passwordInput";
import { RevealSection } from "@/src/components/RevealSection";
import { useState, useEffect } from "react";
import { profileService } from "@/src/services/profileService";
import { eventService } from "@/src/services/eventService";
import Link from "next/link";
import Swal from "sweetalert2";
import BatikOverlay from "@/src/components/BatikOverlay";
import { useAuth } from "@/src/contexts/AuthContext";
import { normalizeUser } from "@/src/lib/utils";
import { EyeIcon, EyeSlashIcon } from "@heroicons/react/24/outline";

import { PencilIcon } from "@heroicons/react/24/outline";

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

export default function DetailMember() {
  const { user, logout, isLoggedIn, setAuthUser } = useAuth();
  const [isMounted, setIsMounted] = useState(false);
  const {
    loading,
    searchId,
    setSearchId,
    password,
    handlePasswordChange,
    handleSearch,
    handleChange,
    userData,
    setUserData,
  } = useSearchMembers();

  const [showPassword, setShowPassword] = useState(false);

  const [showEditForm, setShowEditForm] = useState(false);
  const [submitLoading, setSubmitLoading] = useState(false);
  const [photo, setPhoto] = useState(null);
  const [identityPhoto, setIdentityPhoto] = useState(null);
  const [myEvents, setMyEvents] = useState([]);
  const [formData, setFormData] = useState({
    name: "",
    phone: "",
    gender: "",
    date_of_birth: "",
    blood_type: "",
    emergency_contact: "",
    emergency_phone: "",
    medical_conditions: "",
  });

  // Set mounted setelah render client
  useEffect(() => {
    setIsMounted(true);
  }, []);

  // Auto isi form ketika userData berhasil didapat
  useEffect(() => {
    if (userData) {
      setShowEditForm(false);
      setFormData({
        name: userData.name ?? "",
        phone: userData.phone ?? "",
        gender: userData.gender ?? "",
        date_of_birth: userData.date_of_birth ?? "",
        blood_type: userData.blood_type ?? "",
        emergency_contact: userData.emergency_contact ?? "",
        emergency_phone: userData.emergency_phone ?? "",
        medical_conditions: userData.medical_conditions ?? "",
      });
    }
  }, [userData]);

  // Auto isi form ketika user dari AuthContext berubah (setelah login)
  useEffect(() => {
    if (user) {
      setUserData(user);
      setFormData({
        name: user.name ?? "",
        phone: user.phone ?? "",
        gender: user.gender ?? "",
        date_of_birth: user.date_of_birth ?? "",
        blood_type: user.blood_type ?? "",
        emergency_contact: user.emergency_contact ?? "",
        emergency_phone: user.emergency_phone ?? "",
        medical_conditions: user.medical_conditions ?? "",
      });
    }
  }, [user, setUserData]);

  useEffect(() => {
    if (userData) {
      eventService
        .getMyEvents()
        .then((res) => setMyEvents(res.data.data))
        .catch(() => {});
    }
  }, [userData]);

  function handleFormChange(e) {
    setFormData((prev) => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
  }

  async function handleUpdateProfile(e) {
    e.preventDefault();
    setSubmitLoading(true);
    try {
      const payload = {};
      if (formData.name) payload.name = formData.name;
      if (formData.phone) payload.phone = formData.phone;
      if (formData.gender) payload.gender = formData.gender;
      if (formData.date_of_birth) payload.date_of_birth = formData.date_of_birth;
      if (formData.blood_type) payload.blood_type = formData.blood_type;
      if (formData.emergency_contact)
        payload.emergency_contact = formData.emergency_contact;
      if (formData.emergency_phone)
        payload.emergency_phone = formData.emergency_phone;
      if (formData.medical_conditions)
        payload.medical_conditions = formData.medical_conditions;

      await profileService.update(payload);

      if (photo) {
        const photoForm = new FormData();
        photoForm.append("avatar", photo);
        await profileService.uploadPhoto(photoForm);
      }

      // Update userData setelah update
      const profileRes = await profileService.getProfile();
      const profilePayload = profileRes.data.data;
      const updatedUser = normalizeUser(
        profilePayload.user,
        profilePayload.participant
      );
      setUserData(updatedUser);
      setAuthUser(updatedUser);

      // Update juga di localStorage
      localStorage.setItem("user", JSON.stringify(updatedUser));

      Swal.fire({
        icon: "success",
        title: "Profil Berhasil Diupdate!",
        text: "Data kamu sudah tersimpan.",
      });
      setShowEditForm(false);
      setPhoto(null);
      setIdentityPhoto(null);
    } catch (err) {
      const message =
        err.response?.data?.message || "Terjadi kesalahan, coba lagi.";
      const errors = err.response?.data?.errors;
      Swal.fire({
        icon: "error",
        title: "Gagal Update!",
        text: errors ? Object.values(errors).flat().join(", ") : message,
      });
    } finally {
      setSubmitLoading(false);
    }
  }

  const handleLogout = async () => {
    const result = await Swal.fire({
      title: "Yakin mau logout?",
      text: "Kamu akan keluar dari sesi ini.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Ya, Logout!",
      cancelButtonText: "Batal",
    });

    if (result.isConfirmed) {
      logout();
      setSearchId("");
      setUserData(null);
      setShowEditForm(false);
      setFormData({
        name: "",
        phone: "",
        gender: "",
        date_of_birth: "",
        blood_type: "",
        emergency_contact: "",
        emergency_phone: "",
        medical_conditions: "",
      });
      setMyEvents([]);

      Swal.fire({
        icon: "success",
        title: "Logout Berhasil!",
        text: "Sampai jumpa lagi!",
      });
    }
  };

  // Cek apakah user sudah login
  const isUserLoggedIn = isLoggedIn || userData;

  // Ambil foto profil langsung dari response
  const profilePhoto = userData?.photo || "";
  const name = userData?.name || user?.name || "";

  return (
    <Container className="flex flex-col w-full">
      <div className="relative bg-linear-to-br from-primary-light via-primary-light-active to-primary-light">
        <BatikOverlay />
        <div className="gap-y-8 px-4 md:px-0 max-w-306 mx-auto min-h-screen">
          {/* ====== Cek Login Status ====== */}
          {isMounted && isUserLoggedIn ? (
            // ====== SUDAH LOGIN ======
            <RevealSection direction="up">
              <div className="flex flex-col items-center justify-center mt-24 mb-8">
                <div className="bg-primary-light p-8 rounded-lg shadow-lg text-center w-full border-2 border-neutral-normal">
                  {/* Foto Profil */}
                  <div className="flex flex-col items-center gap-4">
                    <h1 className="text-4xl font-bold font-young text-primary-darker">
                      Selamat Datang!
                    </h1>
                    <div className="w-32 h-32 rounded-full bg-secondary-bg flex items-center justify-center overflow-hidden border-4 border-secondary-bg mx-auto mb-4">
                      {profilePhoto ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img
                          src={profilePhoto}
                          alt={name}
                          className="object-cover w-full h-full"
                          onError={(e) => {
                            e.target.style.display = "none";
                            e.target.parentElement.innerHTML = `
                                                            <span class="text-white font-bold text-4xl">
                                                                ${name ? name.charAt(0).toUpperCase() : "?"}
                                                            </span>
                                                        `;
                          }}
                        />
                      ) : (
                        <span className="text-white font-bold text-4xl">
                          {name ? name.charAt(0).toUpperCase() : "?"}
                        </span>
                      )}
                    </div>
                  </div>
                  <p className="text-2xl font-semibold text-secondary-bg mt-4">
                    {name}
                  </p>
                  <p className="text-gray-600 mt-2">
                    {userData?.email || user?.email}
                  </p>
                  <div className="mt-6 flex gap-4 justify-center">
                    <button
                      onClick={handleLogout}
                      className="cursor-pointer px-8 py-3 border-red-500 hover:bg-red-600/10 active:bg-red-700/10 text-red-600 font-bold border-2 rounded-md transition-all"
                    >
                      Logout
                    </button>
                  </div>
                </div>
              </div>
            </RevealSection>
          ) : (
            <RevealSection direction="up">
              <div className="flex items-center justify-center w-full mt-8">
                <h1 className="text-4xl font-bold m-2 font-young mt-24">
                  Kamu sudah jadi Member?
                </h1>
              </div>
              <div className="flex flex-col justify-center items-center gap-4 w-full max-w-md mx-auto">
                <InputType
                  label="Masukkan Email"
                  id="email"
                  type="email"
                  name="email"
                  placeholder="you@example.com"
                  required
                  className="flex flex-col gap-2 w-full"
                  value={searchId}
                  onChange={handleChange}
                />

                {/* 🔥 PASSWORD dengan toggle manual */}
                <div className="flex flex-col gap-2 w-full">
                  <label className="font-medium text-xl">
                    Password <span className="text-red-500 ml-0.5">*</span>
                  </label>
                  <div className="relative w-full">
                    <input
                      id="password"
                      name="password"
                      type={showPassword ? "text" : "password"}
                      value={password}
                      onChange={handlePasswordChange}
                      placeholder="••••••••"
                      required
                      className="outline-2 p-3 bg-white outline-tertiary-normal rounded-md w-full pr-12"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10"
                      style={{
                        cursor: "pointer",
                        background: "transparent",
                        padding: "4px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                      }}
                    >
                      {showPassword ? (
                        <EyeSlashIcon className="w-5 h-5" />
                      ) : (
                        <EyeIcon className="w-5 h-5" />
                      )}
                    </button>
                  </div>
                </div>

                <button
                  className={`flex justify-center items-center p-8 rounded-md w-full ${loading ? "bg-neutral-bg-active" : "bg-secondary-bg"} hover:bg-secondary-bg-hover active:bg-secondary-bg-active h-16 font-bold text-xl text-white m-10 md:text-3xl`}
                  type="button"
                  disabled={loading}
                  onClick={handleSearch}
                >
                  {loading ? "Mencari..." : "Cek Member"}
                </button>
              </div>
            </RevealSection>
          )}

          {/* Data User — muncul setelah ditemukan */}
          {isMounted && userData && (
            <RevealSection direction="up">
              <div className="flex flex-col gap-4 bg-card-bg p-8 border-2 bg-primary-light border-neutral-normal rounded-md my-4">
                <h2 className="text-3xl font-bold font-young text-neutral-normal">
                  Data Member
                </h2>
                <hr className="border-t-2 border-neutral-normal" />

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-lg">
                  <div>
                    <span className="font-semibold">Hash ID: </span>
                    <span className="font-mono">{userData.hash_id}</span>
                  </div>
                  <div>
                    <span className="font-semibold">Nama: </span>
                    <span>{userData.name}</span>
                  </div>
                  <div>
                    <span className="font-semibold">Email: </span>
                    <span>{userData.email}</span>
                  </div>
                  <div>
                    <span className="font-semibold">Tipe: </span>
                    <span
                      className={`font-bold ${userData.participant_type === "member" ? "text-secondary-dark" : "text-tertiary-bg"}`}
                    >
                      {userData.participant_type === "member"
                        ? "Member"
                        : "Non Member"}
                    </span>
                  </div>
                </div>

                {!showEditForm && (
                  <div className="flex items-center">
                    <button
                      onClick={() => setShowEditForm(true)}
                      className="cursor-pointer flex justify-center items-center rounded-md bg-secondary-bg hover:bg-secondary-bg-hover active:bg-secondary-bg-active h-16 font-bold text-lg text-white mt-4 md:text-2xl font-young w-1/2 md:w-1/4"
                    >
                      <PencilIcon className="m-2" width={24} height={24} /> Edit
                      Profil
                    </button>
                  </div>
                )}
              </div>
            </RevealSection>
          )}

          {/* History Order Event */}
          {isMounted && userData && myEvents.length > 0 && (
            <RevealSection direction="up">
              <div className="flex flex-col gap-4 bg-primary-light border-2 border-neutral-normal p-8 my-4 rounded-md">
                <h2 className="text-3xl font-bold font-young text-neutral-normal">
                  Riwayat Event
                </h2>
                <hr className="border-t-2 border-neutral-normal" />
                <div className="flex flex-col gap-4">
                  {myEvents.map((event, i) => (
                    <div
                      key={i}
                      className="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-neutral-normal pb-4 gap-2"
                    >
                      <div className="flex flex-col gap-1">
                        <div className="font-bold text-lg text-neutral-normal">
                          {event.title}
                        </div>
                        <div className="text-sm text-neutral-dark">
                          {event.location}
                        </div>
                      </div>
                      <div className="flex flex-col items-end gap-1">
                        <span
                          className={`text-sm font-bold px-3 py-1 rounded-md ${
                            event.order?.status === "paid"
                              ? "bg-secondary-bg text-white"
                              : event.order?.status === "free"
                                ? "bg-secondary-bg text-white"
                                : event.order?.status === "pending"
                                  ? "bg-primary-normal text-white"
                                  : event.order?.status === "cancelled"
                                    ? "bg-red-500 text-white"
                                    : "bg-neutral-bg text-white"
                          }`}
                        >
                          {event.order?.status === "paid"
                            ? "Lunas"
                            : event.order?.status === "free"
                              ? "Gratis"
                              : event.order?.status === "pending"
                                ? "Menunggu"
                                : event.order?.status === "cancelled"
                                  ? "Dibatalkan"
                                  : event.order?.status}
                        </span>

                        <Link
                          href={
                            event.status === "ongoing" ||
                            event.status === "publish"
                              ? `/events/upcoming?id=${event.id}`
                              : `/events/finished?id=${event.id}`
                          }
                          className={`text-white text-center px-5 py-2.5 font-medium transition-colors font-young shadow-md rounded-md
                                                    ${event.status === "ongoing" || event.status === "publish" ? "bg-primary-bg hover:bg-primary-bg-hover active:bg-primary-bg-active" : "bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-400  "}`}
                        >
                          Detail
                        </Link>
                        <div className="text-xs font-mono text-neutral-dark">
                          {event.order?.invoice_number}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </RevealSection>
          )}

          {/* Form Edit Profile */}
          {isMounted && userData && showEditForm && (
            <RevealSection direction="up">
              <div className="flex flex-col gap-4 bg-card-bg p-8 border-2 border-neutral-normal bg-primary-light rounded-md my-4">
                <div className="flex justify-between items-center">
                  <h2 className="text-3xl font-bold font-young text-neutral-normal">
                    Edit Profil
                  </h2>
                  <button
                    onClick={() => setShowEditForm(false)}
                    className="cursor-pointer font-medium px-8 py-2 rounded-md bg-transparent border-2 border-neutral-normal hover:border-transparent hover:bg-neutral-normal-active hover:text-white active:border-transparent active:bg-neutral-normal active:text-white focus:border-transparent focus:bg-neutral-normal focus:text-white transition-all"
                  >
                    Batal
                  </button>
                </div>
                <hr className="border-t-2 border-neutral-normal" />

                {/* Foto Profil Saat Ini */}
                {userData.photo && (
                  <div className="flex flex-col items-center gap-2">
                    <label className="text-lg font-medium">
                      Foto Profil Saat Ini
                    </label>

                    <img
                      src={userData.photo}
                      alt="Foto Profil"
                      className="w-32 h-32 rounded-full object-cover border-2 border-gray-300"
                    />
                  </div>
                )}

                <form
                  onSubmit={handleUpdateProfile}
                  className="flex flex-col gap-4"
                >
                  <h3 className="text-xl font-bold font-young">Data Diri</h3>
                  <InputType
                    label="Nama Lengkap"
                    id="name"
                    type="text"
                    name="name"
                    placeholder="John Doe"
                    className="flex flex-col gap-2"
                    value={formData.name}
                    onChange={handleFormChange}
                  />
                  <InputType
                    label="Nomor Telepon/WA"
                    id="phone"
                    type="text"
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
                    value={formData.gender}
                    placehold="Pilih Gender..."
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        gender: e.target.value,
                      }))
                    }
                  />
                  <InputType
                    label="Tanggal Lahir"
                    id="date_of_birth"
                    type="date"
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
                    value={formData.blood_type}
                    placehold="Pilih Golongan Darah..."
                    onChange={(e) =>
                      setFormData((prev) => ({
                        ...prev,
                        blood_type: e.target.value,
                      }))
                    }
                  />
                  <hr className="border-t-2 border-neutral-normal" />
                  <h3 className="text-xl font-bold font-young">
                    Kontak Darurat
                  </h3>
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

                  <hr className="border-t-2 border-neutral-normal" />
                  <h3 className="text-xl font-bold font-young">
                    Info Kesehatan
                  </h3>
                  <div className="flex flex-col gap-2">
                    <label className="text-xl font-medium">
                      Riwayat Alergi / Kondisi Medis
                    </label>
                    <textarea
                      name="medical_conditions"
                      placeholder="Contoh: alergi debu, makanan laut, dll"
                      className="border-tertiary-normal p-3 text-lg bg-white border-2 rounded-md"
                      rows={3}
                      value={formData.medical_conditions}
                      onChange={handleFormChange}
                    />
                  </div>
                  <hr className="border-t-2 border-neutral-normal" />
                  <h3 className="text-xl font-bold mt-4 font-young">
                    Foto Profil
                  </h3>
                  <ImageUpload
                    id="photo"
                    label="Upload Foto Profil Baru"
                    onChange={(file) => setPhoto(file)}
                  />

                  <div className="flex gap-4 mt-4">
                    <button
                      type="button"
                      onClick={() => setShowEditForm(false)}
                      className="cursor-pointer flex-1 flex justify-center items-center rounded-md h-16 font-bold text-xl font-young bg-transparent border-2 border-neutral-normal hover:border-transparent hover:bg-neutral-normal hover:text-white active:border-transparent active:bg-neutral-normal-active active:text-white focus:border-transparent focus:bg-neutral-normal-active focus:text-white transition-all"
                    >
                      Batal
                    </button>
                    <button
                      type="submit"
                      disabled={submitLoading}
                      className={`flex-1 flex justify-center items-center ${submitLoading ? "bg-neutral-normal text-white cursor-not-allowed" : "bg-secondary-bg hover:bg-secondary-bg-hover cursor-pointer"} active:bg-secondary-bg-active h-16 font-bold text-xl text-white font-young rounded-md`}
                    >
                      {submitLoading ? "Menyimpan..." : "Simpan Perubahan"}
                    </button>
                  </div>
                </form>
              </div>
            </RevealSection>
          )}
        </div>
      </div>
    </Container>
  );
}
