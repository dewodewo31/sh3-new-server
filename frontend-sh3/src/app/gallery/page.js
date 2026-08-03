"use client"

import { useState, useEffect } from "react";
import Container from "@/src/components/Container";
import MasonryGallery from "@/src/components/MasonryGallery";
import { galleryService } from "@/src/services/galleryService";
import BatikOverlay from "@/src/components/BatikOverlay";

export default function Gallery() {
    const [images, setImages] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        galleryService.getAll()
            .then(res => {
                const photos = res.data.data ?? [];

                const mappedImages = (Array.isArray(photos) ? photos : [])
                    .map(photo => ({
                        id: photo.event?.id ?? photo.id,
                        url: photo.url,
                        title: photo.event?.title ?? photo.title,
                        subtitle: photo.event?.category ?? "",
                        status: photo.event?.status,
                    }))
                    .filter(img => img.url && img.url.startsWith("http"));

                setImages(mappedImages);
            })
            .catch(err => console.error(err))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <div className="flex justify-center p-16 text-2xl h-screen mt-16">Loading...</div>;
    }

    return (
        <Container className="flex flex-col w-full">
            <div className="relative bg-linear-to-br from-primary-light via-primary-light-active to-primary-light">
                <BatikOverlay />
                <div className="gap-y-4 max-w-306 mx-auto">
                    <h1 className="text-5xl font-bold text-center p-8 font-young mt-16">Cerita Kami saat Berlari!</h1>

                    {images.length === 0 ? (
                        <p className="text-center text-2xl py-16 min-h-screen">Belum ada foto event.</p>
                    ) : (
                        <MasonryGallery images={images} />
                    )}
                </div>
            </div>
        </Container>
    );
}