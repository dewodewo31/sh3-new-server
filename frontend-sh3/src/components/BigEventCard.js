import Image from "next/image"
import Link from "next/link"
import { concateDate } from "@/src/lib/utils";

export default function BigEventCard({
  id,        // ← tambah prop id
  title,
  start_date,
  end_date,
  category,
  img,
  status
}) {

  const isOngoing = status == "publish" || status == "ongoing" || status == "upcoming"

  return (
    <div className="flex flex-col md:flex-row flex-1 shrink-0 basis-1/2 items-center bg-white h-full w-full max-w-3xl rounded-md">
      {img ? (
        <Image
          src={img}
          alt="image"
          width={250}
          height={250}
          className="w-full object-cover rounded-l-md"
        />
      ) : (
        <div className="w-full min-h-40 bg-neutral-bg flex items-center justify-center text-5xl font-bold font-young rounded-l-md">
          {title.slice(0, 2).toUpperCase()}
        </div>
      )}
      <div className="flex flex-col gap-4 p-8 justify-between h-full">

        <div className="text-2xl font-bold font-young">{title}</div>
        <div className="text-lg">{concateDate(start_date, end_date)}</div>
        <div className="text-xl font-semibold">{category}</div>
        <Link
          href={isOngoing ? `/events/upcoming?id=${id}` : `/events/finished?id=${id}`}
          className={`text-white text-center px-5 py-2.5 font-medium transition-colors font-young shadow-md rounded-md
              ${isOngoing ? "bg-primary-bg hover:bg-primary-bg-hover active:bg-primary-bg-active" : "bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-400  "}`}
        >
          {isOngoing ? "Daftar" : "Detail"}
        </Link>
      </div>
    </div>
  )
}