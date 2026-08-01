// src/components/MerchandiseCard.jsx
import Link from "next/link"
import { formatRupiah } from "@/src/lib/utils"

export default function MerchandiseCard({ id, name, price, image_url, stock, sizes }) {
    return (
        <div className="flex flex-col h-full bg-white border-2 border-neutral-normal hover:border-emerald-600 transition-colors rounded-md">
            {/* Gambar */}
            <div className="relative w-full h-62.5 overflow-hidden bg-neutral-bg rounded-t-md">
                {image_url ? (
                    <img
                        src={image_url}
                        alt={name}
                        className="w-full h-full object-cover"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-neutral-dark text-4xl font-bold font-young">
                        {name.slice(0, 2).toUpperCase()}
                    </div>
                )}
                {stock <= 10 && stock > 0 && (
                    <div className="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1">
                        Sisa {stock}
                    </div>
                )}
                {stock === 0 && (
                    <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                        <span className="text-white font-bold text-lg">Habis</span>
                    </div>
                )}
            </div>

            {/* Info */}
            <div className="p-3 flex flex-col gap-1 flex-1">
                <div className="flex flex-col gap-1 flex-1">
                    <div className="font-semibold text-sm line-clamp-2">{name}</div>
                    <div className="font-bold text-emerald-600">
                        Rp. {formatRupiah(price)}
                    </div>
                    {sizes?.length > 0 && (
                        <div className="text-xs text-neutral-dark">
                            Size: {sizes.join(", ")}
                        </div>
                    )}
                </div>
                <Link
                    href={`/merchandise/order?id=${id}`}
                    className={`mt-2 text-white text-center px-5 py-2.5 text-sm font-medium transition-colors font-young shadow-md rounded-md
            ${stock === 0
                            ? "bg-neutral-normal pointer-events-none opacity-60"
                            : "bg-primary-bg hover:bg-primary-bg-hover active:bg-primary-bg-active"
                        }`}
                >
                    {stock === 0 ? "Habis" : "Pesan"}
                </Link>
            </div>
        </div>
    )
}