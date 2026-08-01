"use client"
import { useState, useEffect } from "react";
import { ArrowLongLeftIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon } from "@heroicons/react/24/outline";


// ============ LIGHTBOX ============
function Lightbox({ images, initialIndex, onClose }) {
    const [current, setCurrent] = useState(initialIndex)

    const prev = () => setCurrent(i => (i - 1 + images.length) % images.length)
    const next = () => setCurrent(i => (i + 1) % images.length)

    // Tutup dengan ESC, navigasi dengan arrow key
    useEffect(() => {
        const handleKey = (e) => {
            if (e.key === "Escape") onClose()
            if (e.key === "ArrowLeft") prev()
            if (e.key === "ArrowRight") next()
        }
        window.addEventListener("keydown", handleKey)
        return () => window.removeEventListener("keydown", handleKey)
    }, [])

    // Lock scroll saat lightbox terbuka
    useEffect(() => {
        document.body.style.overflow = "hidden"
        return () => { document.body.style.overflow = "" }
    }, [])

    return (
        <div
            className="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
            onClick={onClose}
        >
            {/* Tombol Close */}
            <button
                className="absolute top-4 right-4 text-white hover:text-neutral-300 transition-colors z-10"
                onClick={onClose}
            >
                <XMarkIcon className="w-8 h-8" />
            </button>

            {/* Counter */}
            <div className="absolute top-4 left-1/2 -translate-x-1/2 text-white text-sm font-medium">
                {current + 1} / {images.length}
            </div>

            {/* Tombol Prev */}
            {images.length > 1 && (
                <button
                    className="absolute left-4 text-white hover:text-neutral-300 transition-colors z-10 p-2"
                    onClick={(e) => { e.stopPropagation(); prev() }}
                >
                    <ChevronLeftIcon className="w-8 h-8" />
                </button>
            )}

            {/* Gambar */}
            <div
                className="max-w-4xl max-h-[85vh] w-full px-16"
                onClick={(e) => e.stopPropagation()}
            >
                <img
                    src={images[current]}
                    alt={`Gallery ${current + 1}`}
                    className="w-full h-full object-contain max-h-[85vh]"
                />
            </div>

            {/* Tombol Next */}
            {images.length > 1 && (
                <button
                    className="absolute right-4 text-white hover:text-neutral-300 transition-colors z-10 p-2"
                    onClick={(e) => { e.stopPropagation(); next() }}
                >
                    <ChevronRightIcon className="w-8 h-8" />
                </button>
            )}

            {/* Thumbnail strip */}
            {images.length > 1 && (
                <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 px-4">
                    {images.map((img, i) => (
                        <button
                            key={i}
                            onClick={(e) => { e.stopPropagation(); setCurrent(i) }}
                            className={`w-12 h-12 overflow-hidden border-2 transition-all ${i === current ? "border-white scale-110" : "border-transparent opacity-60 hover:opacity-100"
                                }`}
                        >
                            <img src={img} alt="" className="w-full h-full object-cover" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    )
}



export default function EventGallery({ images }) {
    const [lightboxIndex, setLightboxIndex] = useState(null)

    if (!images || images.length === 0) return null
    return (
        <>
            <div className="flex flex-col gap-y-4">
                <h2 className="text-2xl font-bold py-4 font-young">Galeri</h2>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                    {images.map((url, i) => (
                        <button
                            key={i}
                            onClick={() => setLightboxIndex(i)}
                            className="relative aspect-square overflow-hidden group cursor-pointer"
                        >
                            <img
                                src={url}
                                alt={`Galeri ${i + 1}`}
                                className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300" />
                        </button>
                    ))}
                </div>
            </div>

            {lightboxIndex !== null && (
                <Lightbox
                    images={images}
                    initialIndex={lightboxIndex}
                    onClose={() => setLightboxIndex(null)}
                />
            )}
        </>
    )
}