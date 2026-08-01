import Link from "next/link";
export default function LinkButton({
    destination,
    text,
    bg_color,
    bg_color_hover,
    bg_color_active,
}) {
    return (
        <Link
        href={destination}
        className={`bg-${bg_color} text-white rounded-sm px-6 py-2.5 m-5 font-medium hover:bg-${bg_color_hover} active:bg-${bg_color_active} font-young shadow-flat transition-all hover:shadow-flat-interact hover:-translate-x-1 hover:-translate-y-1  active:shadow-flat-interact active:-translate-x-1 active:-translate-y-1 focus:shadow-flat-interact focus:-translate-x-1 focus:-translate-y-1`}
        >
        {text}
        </Link>
    )
}