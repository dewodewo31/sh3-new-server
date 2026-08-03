import Image from "next/image"

export default function StructureProfileCard({ images, name, position }) {
    return (
        // Lebar dikunci di 200px agar rapi saat dibuat grid struktur
        <div className="flex flex-col items-center bg-primary-light h-full w-full max-w-50 border-2 border-neutral-normal">
            {images ? (
                <Image
                    src={images}
                    alt="image"
                    width={80}
                    height={80}
                    className="w-full object-cover aspect-square"
                />
            ) : (
                <div className="w-full aspect-square bg-neutral-bg flex items-center justify-center text-4xl font-bold font-young">
                    {name.slice(0, 1).toUpperCase()}
                </div>
            )}
            {/* Padding diperkecil menjadi p-4 dan gap menjadi gap-1.5 */}
            <div className="flex flex-col w-full gap-1.5 p-4 text-center"> 
                {/* Ukuran teks nama turun ke text-base (16px) */}
                <div className="text-base font-bold font-young text-neutral-normal leading-tight">{name}</div>
                {/* Ukuran teks jabatan turun ke text-sm (14px) */}
                <div className="text-sm text-neutral-dark leading-tight">{position}</div>
            </div>
        </div>
    )
}