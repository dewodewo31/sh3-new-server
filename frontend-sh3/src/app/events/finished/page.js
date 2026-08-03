"use client"
import { useState, useEffect } from "react";

import Container from "@/src/components/Container";
import Image from "next/image";
import Link from "next/link";
import { ArrowLongLeftIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon } from "@heroicons/react/24/outline";
import { MapPinIcon, UserGroupIcon, TagIcon } from "@heroicons/react/24/solid";
import { concateDate } from "@/src/lib/utils";
import { eventService } from "@/src/services/eventService";
import { RevealSection } from "@/src/components/RevealSection";
import SponsorSection from "@/src/components/SponsorSection";
import EventGallery from "@/src/components/EventGallery";


// ============ MAIN PAGE ============
export default function PastEvents({ }) {
  const [event, setEvent] = useState(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const eventId = params.get("id") ?? 1;

    eventService
      .getById(eventId)
      .then((res) => setEvent(res.data.data))
      .catch(console.error);
  }, []);

  if (!event) return <div className="flex justify-center p-16 text-2xl mt-16 h-screen">Loading...</div>;

  return (
    <Container className="flex flex-col gap-y-4 w-full">
      <div className="relative bg-linear-to-b from-primary-light to-primary-light-hover">
        <div
          className="absolute top-0 left-0 h-full w-28 bg-repeat-y bg-left mask-r-from-5%"
          style={{
            backgroundImage: `url('/assets/images/batik4.svg')`,
            backgroundSize: "112px",
          }}
        />
        <div
          className="absolute top-0 right-0 h-full w-28 bg-repeat-y bg-left -scale-x-100 mask-r-from-5%"
          style={{
            backgroundImage: `url('/assets/images/batik4.svg')`,
            backgroundSize: "112px",
          }}
        />
        <div className="max-w-306 items-center md:p-0 p-4 mx-auto">
          <div className="mt-24">
            <Link href="/events" className="static md:absolute">
              <ArrowLongLeftIcon className="w-8 h-8 md:w-16 md:h-16" />
            </Link>
            <div className="flex items-center justify-center w-full">
              <h1 className="text-4xl font-bold">{event.title}</h1>
            </div>
            <div className="flex flex-row justify-between gap-x-2 mt-8">
              <div className="flex flex-row justify-center gap-x-2 w-1/2">
                <MapPinIcon className="w-8 h-8" />
                <div className="text-lg font-bold">{event.location}</div>
              </div>
              <div className="text-lg font-bold">{concateDate(event.start_date, event.end_date)}</div>
            </div>
          </div>

          <RevealSection direction="up" delay="100">
            {event.image_url ? (
              <Image
                src={event.image_url}
                alt={event.title}
                width={600}
                height={450}
                className="h-128 w-full flex object-cover mt-4 rounded-md"
              />
            ) : (
              <div className="h-128 w-full bg-neutral-bg flex items-center justify-center text-5xl font-bold font-young border-1 mt-4 rounded-md">
                {event.title.slice(0, 2).toUpperCase()}
              </div>
            )}
          </RevealSection>

          <RevealSection direction="up" delay="100">
            <div className="flex flex-col gap-x-16 my-4">
              <h2 className="text-2xl font-bold py-4 font-young">Tentang Event</h2>
              <div className="text-sm">{event.description}</div>
            </div>
          </RevealSection>

          {/* Gallery Section */}
          {event.galleries && event.galleries.length > 0 && (
            <RevealSection direction="up" delay="100">
              <EventGallery images={event.galleries} />
            </RevealSection>
          )}
        </div>

        <RevealSection direction="up" delay="100">
          <div className="flex flex-col w-full justify-center items-center gap-8 md:gap-32 md:flex-row bg-emerald-600 p-8 text-white font-young">
            <div className="flex flex-row items-center justify-center gap-8">
              <UserGroupIcon className="w-16 h-16 md:w-32 md:h-32" />
              <div className="flex flex-col">
                <div className="font-bold text-4xl">Joined</div>
                <div className="font-semibold text-3xl">{event.registered_count} Members</div>
              </div>
            </div>
            <div className="flex flex-row items-center justify-center gap-8">
              <TagIcon className="w-16 h-16 md:w-32 md:h-32" />
              <div className="flex flex-col">
                <div className="font-bold text-4xl">Category</div>
                <div className="font-semibold text-3xl">{event.category?.name}</div>
              </div>
            </div>
          </div>
        </RevealSection>

        {event.sponsors && (
          <RevealSection direction="up" delay="100">
            <div className="max-w-306 mx-auto w-full px-4">
              <SponsorSection sponsors={event.sponsors} />
            </div>
          </RevealSection>
        )}
      </div>
    </Container>

  );
}