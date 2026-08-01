"use client";

import { useEffect, useState } from "react";
import QRCode from "qrcode";

/**
 * Menampilkan QR Code tiket dari kode teks (mis. "SH3-2-23-0SEICFpd").
 * Backend hanya menyediakan kode teks, bukan gambar base64.
 */
export default function TicketQR({ value, size = 256, downloadName = "tiket.png" }) {
  const [src, setSrc] = useState(null);

  useEffect(() => {
    if (!value) return;
    QRCode.toDataURL(value, { width: size, margin: 1 })
      .then(setSrc)
      .catch((err) => console.error("Gagal generate QR:", err));
  }, [value, size]);

  function handleDownload() {
    if (!src) return;
    const link = document.createElement("a");
    link.href = src;
    link.download = downloadName;
    link.click();
  }

  return (
    <div className="flex flex-col items-center gap-3">
      {src ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={src} alt="QR Code Tiket" width={size} height={size} className="w-48 h-48 md:w-64 md:h-64" />
      ) : (
        <div style={{ width: size, height: size }} className="bg-neutral-bg flex items-center justify-center text-sm text-neutral-dark">
          Memuat QR...
        </div>
      )}
      <button
        type="button"
        onClick={handleDownload}
        disabled={!src}
        className="cursor-pointer flex justify-center items-center gap-2 bg-secondary-bg hover:bg-secondary-bg-hover active:bg-secondary-bg-active text-white font-bold px-8 py-3 font-young rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Download QR
      </button>
    </div>
  );
}
