import Image from "next/image"
import Link from "next/link"
import { concateDate } from "@/src/lib/utils";

export default function EventCard({
  id,        // ← tambah prop id
  title,
  start_date,
  end_date,
  category,
  description,
  location,
  img,
  status
}) {

  const isOngoing = status == "publish" || status == "ongoing" || status == "upcoming"

  return (
    <div className="flex flex-col flex-1 basis-1/4 items-center bg-white h-full w-full max-w-xl max-h-sm rounded-md border-2 border-neutral-normal">

      {img ? (
        <Image
          src={img}
          alt={title}
          width={250}
          height={250}
          className="w-full h-full object-cover rounded-t-md aspect-2/1"
        />
      ) : (
        <div className="w-full h-full object-cover flex items-center justify-center bg-neutral-bg text-neutral-dark text-5xl font-bold font-young rounded-t-md aspect-square">
          {title.slice(0, 2).toUpperCase()}
        </div>
      )}
      <div className="flex flex-col gap-4 p-8 justify-between h-full w-full">
        <div className="text-lg">{concateDate(start_date, end_date, isOngoing)}</div>
        <div className="text-2xl font-bold font-young">{title}</div>
        <div className="text-ellipsis">{description}</div>
        <div className="flex flex-row">
          <div className="text-md truncate basis-1/2">
            {location}
          </div>
          <div className="text-xl font-semibold text-right basis-1/2">
            {category}
            </div>
        </div>

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