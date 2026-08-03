// components/SponsorList.js
"use client"

import { useSponsor } from "../hooks/useSponsor";

function SponsorCard({ sponsor }) {
    const inner = (
        <div className="flex flex-col items-center justify-center gap-2 p-4 transition-colors min-w-30 cursor-pointer">
            {sponsor.logo_url ? (
                <img
                    src={sponsor.logo_url}
                    alt={sponsor.name}
                    className="w-20 h-20 object-contain"
                />
            ) : (
                <div className="flex items-center justify-center bg-neutral-bg text-neutral-dark text-xl font-bold font-young w-20 h-20 rounded-full">
                    {sponsor.name.slice(0, 2).toUpperCase()}
                </div>
            )}
            <div className="text-sm font-semibold text-center">{sponsor.name}</div>
        </div>
    )

    if (sponsor.website) {
        return (
            <a
                href={sponsor.website}
                target="_blank"
                rel="noopener noreferrer"
                className="no-underline"
            >
                {inner}
            </a>
        )
    }

    return <div>{inner}</div>
}

export function SponsorList() {
    const { sponsors, loading, error } = useSponsor();

    if (loading) return <p className="min-h-screen text-center text-2xl p-16">Loading ....</p>;
    if (error) return <p className="min-h-screen text-center text-2xl p-16">Gagal Membuat Sponsor</p>;
    if (!sponsors) return null;

    const allSponsors = (sponsors || []).filter(s => s.is_active);

    if (allSponsors.length === 0) return <p className="min-h-screen text-center text-2xl p-16">Belum ada Sponsor</p>;

    const groupedByYear = allSponsors.reduce((acc, sponsor) => {
        const year = sponsor.year ?? "Lainnya";
        if (!acc[year]) acc[year] = [];
        acc[year].push(sponsor);
        return acc;
    }, {});
    // descending (terbaru dulu)
    // const sortedYears = Object.keys(groupedByYear).sort((a, b) => b - a);

    // ascending (terlama dulu)
    const sortedYears = Object.keys(groupedByYear).sort((a, b) => a - b);

    return (
        <section className="py-10 px-6 min-h-screen">
            <div className="flex flex-col gap-10">
                {sortedYears.map((year) => (
                    <div key={year} className="flex flex-col gap-4">
                        
                        <h3 className="text-lg font-semibold mx-auto heading-separator relative w-3/4 text-center text-primary-darker">{year}</h3>
                        <div className="flex flex-wrap justify-center gap-6">
                            {groupedByYear[year].map((sponsor) => (
                                <SponsorCard key={sponsor.id} sponsor={sponsor} />
                            ))}
                        </div>
                        {/* <hr className="w-80 m-auto text-primary-bg"/> */}
                    </div>
                    
                ))}
            </div>
        </section>
    )
}