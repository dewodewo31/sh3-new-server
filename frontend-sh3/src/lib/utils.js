const now = new Date();

export function dateConverted(date) {
    const formattedDate = new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    return formattedDate;
}

// Fungsi baru: tanggal bahasa Indonesia + jam WITA
export function dateConvertedWITA(dateString) {
    // Buang 'Z' di akhir agar tidak dianggap UTC
    const localString = dateString.replace('Z', '');
    const date = new Date(localString);

    const formattedDate = date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${formattedDate}, ${hours}:${minutes} WITA`;
    // Output: "24 Mei 2026, 14:30 WITA" ✅
}

export function concateDate(start, end, isOngoing) {
    const startDate = new Date(start);

    if (startDate > now || isOngoing == true) {
        return `${dateConvertedWITA(start)} - ${dateConvertedWITA(end)}`
    } else {
        return dateConvertedWITA(start);
    }
}

export function formatRupiah(angka) {
    return new Intl.NumberFormat("id-ID").format(angka);
}

const ASSET_BASE = process.env.NEXT_PUBLIC_BASE_ASSET_URL || "";

// Ubah path relatif dari backend (mis. "uploads/avatars/x.png")
// menjadi URL absolut yang bisa dipakai <img> / next/image.
export function assetUrl(path) {
    if (!path) return null;
    if (/^(https?:)?\/\//.test(path)) return path;
    if (path.startsWith("/")) return `${ASSET_BASE}${path}`;
    return `${ASSET_BASE}/${path}`;
}

// Normalisasi profil dari backend (User + Participant) menjadi satu objek
// yang dipakai komponen frontend (nama, email, phone, hash_id, photo, dll).
export function normalizeUser(user, participant) {
    const u = user || {};
    const p = participant || {};
    const id = p.id ?? u.id;

    return {
        id,
        hash_id:
            p.hash_id ||
            (id
                ? p.membership_type && p.membership_type !== "none"
                    ? String(id).padStart(4, "0")
                    : `NM-${String(id).padStart(4, "0")}`
                : ""),
        user_id: u.id ?? p.user_id,
        name: u.name || p.name || "",
        email: u.email || p.email || "",
        phone: p.phone || "",
        gender: p.gender || "",
        birthdate: p.date_of_birth || "",
        date_of_birth: p.date_of_birth || "",
        blood_type: p.blood_type || "",
        emergency_contact: p.emergency_contact || "",
        emergency_phone: p.emergency_phone || "",
        medical_conditions: p.medical_conditions || "",
        allergy_history: p.medical_conditions || "",
        address: p.address || "",
        jersey_size: p.jersey_size || "",
        identity_number: p.identity_number || "",
        role: u.role || "",
        avatar: u.avatar || "",
        photo: assetUrl(u.avatar) || null,
        participant_type: p.is_membership_active ? "member" : "non_member",
        membership_type: p.membership_type || "",
    };
}
