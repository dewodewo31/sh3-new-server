// components/SponsorList.js
"use client"

import { useSponsor } from "../hooks/useSponsor";

function SponsorCard({ sponsor }) {
    const inner = (
        <div className="flex flex-col items-center justify-center gap-2 p-4 min-w-[120px] shrink-0 cursor-pointer">
            {sponsor.logo_url ? (
                <img
                    src={sponsor.logo_url}
                    alt={sponsor.name}
                    className="w-20 h-20 object-contain"
                    draggable={false}
                />
            ) : (
                <div className="flex items-center justify-center bg-neutral-bg text-neutral-dark text-xl font-bold font-young w-20 h-20 rounded-full shrink-0">
                    {sponsor.name.slice(0, 2).toUpperCase()}
                </div>
            )}
            <div className="text-sm font-semibold text-center text-neutral-dark whitespace-nowrap">
                {sponsor.name}
            </div>
        </div>
    )

    if (sponsor.website) {
        return (
            <a
                href={sponsor.website}
                target="_blank"
                rel="noopener noreferrer"
                className="no-underline shrink-0"
            >
                {inner}
            </a>
        )
    }

    return <div className="shrink-0">{inner}</div>
}

export function SponsorMarquee() {
    const { sponsors, loading, error } = useSponsor();

    if (loading) return <p className="min-h-screen text-center text-2xl p-16">Loading...</p>;
    if (error) return <p className="min-h-screen text-center text-2xl p-16">Gagal memuat sponsor.</p>;
    if (!sponsors) return null;

    const allSponsors = (sponsors || []).filter(s => s.is_active);

    if (allSponsors.length === 0) return <p className="min-h-screen text-center text-2xl p-16">Belum ada Sponsor</p>;

    // Pastikan track selalu cukup panjang biar animasi mulus meski sponsor cuma sedikit.
    // Ulangi list-nya sampai minimal 12 item per "set", baru digandakan 2x untuk loop seamless.
    const MIN_ITEMS_PER_SET = 12;
    const repeatCount = Math.max(1, Math.ceil(MIN_ITEMS_PER_SET / allSponsors.length));
    const baseSet = Array.from({ length: repeatCount }, () => allSponsors).flat();

    // Duplikat PERSIS 1x lagi (bukan slice sponsor tertentu) → separuh pertama & kedua identik
    const marqueeItems = [...baseSet, ...baseSet];

    return (
        <section className="py-10 px-6">
            <div className="overflow-hidden w-full">
                <div className="flex w-max gap-8 animate-marquee hover:[animation-play-state:paused]">
                    {marqueeItems.map((sponsor, index) => (
                        <SponsorCard key={`${sponsor.id}-${index}`} sponsor={sponsor} />
                    ))}
                </div>
            </div>
        </section>
    )
}