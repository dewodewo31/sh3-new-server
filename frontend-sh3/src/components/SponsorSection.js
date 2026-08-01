"use client"

const TIER_CONFIG = {
    platinum: {
        label: "Platinum",
        badgeClass: "bg-purple-100 text-purple-800 border border-purple-300",
        logoSize: 96,
        order: 1,
    },
    gold: {
        label: "Gold",
        badgeClass: "bg-yellow-100 text-yellow-800 border border-yellow-300",
        logoSize: 80,
        order: 2,
    },
    silver: {
        label: "Silver",
        badgeClass: "bg-gray-100 text-gray-700 border border-gray-300",
        logoSize: 64,
        order: 3,
    },
    bronze: {
        label: "Bronze",
        badgeClass: "bg-orange-100 text-orange-800 border border-orange-300",
        logoSize: 56,
        order: 4,
    },
    partner: {
        label: "Partner",
        badgeClass: "bg-blue-100 text-blue-800 border border-blue-300",
        logoSize: 56,
        order: 5,
    },
}

function SponsorCard({ sponsor, logoSize }) {
    const inner = (
        <div className="flex flex-col items-center justify-center gap-2 p-4 transition-colors min-w-30 cursor-pointer">
            {sponsor.logo_url ? (
                <img
                    src={sponsor.logo_url}
                    alt={sponsor.name}
                    style={{ width: logoSize, height: logoSize }}
                    className="object-contain"
                />
            ) : (
                <div
                    className="flex items-center justify-center bg-neutral-bg text-neutral-dark text-xl font-bold font-young rounded-full"
                    style={{ width: logoSize, height: logoSize }}
                >
                    {sponsor.name.slice(0, 2).toUpperCase()}
                </div>
            )}
            <div className="text-sm font-semibold text-center">{sponsor.name}</div>
            {/* {sponsor.website && (
                <div className="text-xs text-secondary-bg font-medium">Kunjungi Website →</div>
            )} */}
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

export default function SponsorSection({ sponsors }) {
    if (!sponsors) return null

    const tiersWithData = Object.entries(TIER_CONFIG)
        .sort((a, b) => a[1].order - b[1].order)
        .filter(([tier]) => sponsors[tier]?.length > 0)

    if (tiersWithData.length === 0) return null

    return (
        <div className="flex flex-col gap-8 mt-8">
            <h2 className="text-2xl font-bold font-young">Sponsor pada event ini!</h2>

            {tiersWithData.map(([tier, config]) => (
                <div key={tier} className="flex flex-col gap-3 items-center">
                    {/* Tier header */}
                    <div className="flex items-center gap-3">
                        <span className={`text-xs font-semibold px-3 py-1 uppercase tracking-wide ${config.badgeClass}`}>
                            {config.label}
                        </span>
                    </div>

                    {/* Logo grid */}
                    <div className="flex flex-col md:flex-row gap-4 items-center">
                        {sponsors[tier].map((sponsor) => (
                            <SponsorCard
                                key={sponsor.id}
                                sponsor={sponsor}
                                logoSize={config.logoSize}
                            />
                        ))}
                    </div>
                </div>
            ))}
        </div>
    )
}