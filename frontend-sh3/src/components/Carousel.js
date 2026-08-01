"use client"

import useEmblaCarousel from "embla-carousel-react"
import Image from "next/image"
import { useCallback, useEffect, useState } from "react"
import { eventService } from "../services/eventService" // sesuaikan path

const AUTOPLAY_DELAY = 3000

export default function Carousel() {
  const [emblaRef, emblaApi] = useEmblaCarousel({
    align: "center",
    loop: true,
    skipSnaps: false,
  })

  const [selectedIndex, setSelectedIndex] = useState(0)
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)

  // ✅ Fetch & filter past events by status
  useEffect(() => {
    const fetchEvents = async () => {
      try {
        const res = await eventService.getAll()
        const allEvents = res.data.data ?? []

        const pastEvents = allEvents
          .filter(item => item.status === "completed") // ✅ filter by status
          .slice(0, 3)

        setEvents(pastEvents)
      } catch (err) {
        console.error("Gagal fetch events:", err)
      } finally {
        setLoading(false)
      }
    }

    fetchEvents()
  }, [])

  const onSelect = useCallback(() => {
    if (!emblaApi) return
    setSelectedIndex(emblaApi.selectedScrollSnap())
  }, [emblaApi])

  useEffect(() => {
    if (!emblaApi) return
    emblaApi.on("select", onSelect)
  }, [emblaApi, onSelect])

  useEffect(() => {
    if (!emblaApi || events.length === 0) return
    const interval = setInterval(() => {
      emblaApi.scrollNext()
    }, AUTOPLAY_DELAY)
    return () => clearInterval(interval)
  }, [emblaApi, events])

  const handleCardClick = useCallback((index) => {
    if (!emblaApi) return
    if (index === selectedIndex) return
    if (index > selectedIndex || (selectedIndex === events.length - 1 && index === 0)) {
      emblaApi.scrollNext()
    } else {
      emblaApi.scrollPrev()
    }
  }, [emblaApi, selectedIndex, events.length])

  if (loading) {
    return (
      <div className="bg-bg-colors py-16 flex justify-center items-center min-h-75">
        <p className="text-gray-400">Memuat event...</p>
      </div>
    )
  }

  if (events.length === 0) {
    return (
      <div className="bg-bg-colors py-16 flex justify-center items-center min-h-75">
        <p className="text-gray-400">Belum ada past event.</p>
      </div>
    )
  }

  return (
    <div className="py-16">

      <div className="overflow-hidden" ref={emblaRef}>
        <div className="flex items-center">
          {events.map((event, index) => {
            const isActive = index === selectedIndex
            return (
              <div
                key={event.id}
                onClick={() => handleCardClick(index)}
                className={`relative flex-none mx-3 transition-all duration-500 overflow-hidden
                  ${!isActive ? "cursor-pointer" : ""}
                  ${isActive
                    ? "w-[55%] md:w-[45%] scale-100 shadow-2xl z-10"
                    : "w-[35%] md:w-[28%] scale-90 opacity-60"
                  }
                `}
              >
                <div className="relative h-56 md:h-96">
                  {event.image_url ? (
                    <Image
                      src={event.image_url}
                      alt={event.title}
                      fill
                      className="object-cover rounded-md"
                    />
                  ) : (
                    <div className="w-full h-full bg-neutral-bg flex items-center justify-center text-6xl font-bold font-young rounded-md">
                      {event.title.slice(0, 2).toUpperCase()}
                    </div>
                  )}

                  <div className="absolute inset-0 bg-linear-to-t from-black/70 via-black/20 to-transparent rounded-md" />

                  {isActive && (
                    <div className="absolute bottom-0 left-0 right-0 p-5 text-white">
                      <h3 className="text-xl font-bold mb-1">{event.title}</h3>
                      <p className="text-sm text-gray-300 mb-3">{event.location}</p>
                      <div className="flex items-end justify-between">
                        <span className="font-semibold">{event.category?.name ?? "-"}</span>
                        {event.registered_count != null && (
                          <span className="font-semibold text-right">
                            {event.registered_count}+<br />Peserta
                          </span>
                        )}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )
          })}
        </div>
      </div>

      <div className="flex justify-center gap-2 mt-6">
        {events.map((_, index) => (
          <button
            key={index}
            onClick={() => emblaApi && emblaApi.scrollTo(index)}
            className={`w-2 h-2 rounded-full transition-all duration-300 ${
              index === selectedIndex
                ? "bg-btn-green-normal w-6"
                : "bg-gray-400"
            }`}
          />
        ))}
      </div>

    </div>
  )
}