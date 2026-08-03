export default function BatikOverlay() {
    return (
        <>
            <div
                className="absolute top-0 left-0 h-full w-28 bg-repeat-y bg-left mask-r-from-5% md:opacity-50 opacity-25 pointer-events-none"
                style={{
                    backgroundImage: `url('/assets/images/batik4.svg')`,
                    backgroundSize: "112px",
                }}
            />
            <div
                className="absolute top-0 right-0 h-full w-28 bg-repeat-y bg-left -scale-x-100 mask-r-from-5% md:opacity-50 opacity-25 pointer-events-none"
                style={{
                    backgroundImage: `url('/assets/images/batik4.svg')`,
                    backgroundSize: "112px",
                }}
            />
        </>
    )
}