"use client"
import { useState, useEffect } from "react";


import Container from "@/src/components/Container";
import Image from "next/image";
import Link from "next/link";
import { ArrowLongLeftIcon } from "@heroicons/react/24/outline";
import { CalendarDaysIcon, UserGroupIcon } from "@heroicons/react/24/solid";
import { eventService } from "@/src/services/eventService";
import { assetUrl } from "@/src/lib/utils";

import InputType from "@/src/components/Inputs";

export default function EventMembers() {
    const [data, setData] = useState(null);
    const [search, setSearch] = useState("");
    const [eventId, setEventId] = useState(null);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const id = params.get("id") ?? 1;

        setEventId(id);

        eventService
            .getParticipants(id)
            .then(async (res) => {
                const list = res.data.data ?? [];
                const eventRes = await eventService.getById(id);
                const event = eventRes.data.data;

                const participants = list.map((ep) => ({
                    id: ep.participant?.id,
                    name: ep.participant?.name || "-",
                    email: ep.participant?.email || "-",
                    gender: ep.participant?.gender || "",
                    photo_url: assetUrl(ep.participant?.avatar) || null,
                    joined_at: ep.created_at,
                }));

                setData({ event, total_participants: participants.length, participants });
            })
            .catch(console.error);

    }, []);

    const formatDate = (iso) =>
        new Date(iso).toLocaleDateString("id-ID", {
            day: "numeric", month: "long", year: "numeric",
        });

    const filtered = data?.participants?.filter(p =>
        p.name.toLowerCase().includes(search.toLowerCase()) ||
        p.email.toLowerCase().includes(search.toLowerCase())
    ) ?? [];

    if (!data) return (
        <div className="flex justify-center p-16 text-2xl">Loading...</div>
    );

    return (
        <Container className="flex flex-col w-full">
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
                <div className="max-w-306 mx-auto h-screen z-1 relative">
                    {/* Header */}
                    <div className="flex flex-col gap-y-4 ">
                        <Link href={`/events/upcoming?id=${eventId}`} className="static md:absolute">
                            <ArrowLongLeftIcon className="w-8 h-8 md:w-16 md:h-16" />
                        </Link>

                        <div className="flex flex-col items-center gap-y-1 text-center">
                            <h1 className="text-3xl font-bold mt-24">{data.event.title}</h1>
                            <div className="flex items-center gap-x-2 text-gray-500">
                                <CalendarDaysIcon className="w-5 h-5" />
                                <span className="text-sm">{formatDate(data.event.start_date)}</span>
                            </div>
                        </div>

                        {/* Stats badge */}
                        <div className="flex justify-center">
                            <div className="flex items-center gap-x-2 bg-secondary-bg text-white font-semibold px-4 py-2 text-sm rounded-md">
                                <UserGroupIcon className="w-5 h-5" />
                                {data.total_participants} Peserta Terdaftar
                            </div>
                        </div>

                        <div className="flex flex-col justify-center w-full gap-4">
                            <InputType
                                label="Cari Orang atau Teman anda!"
                                type="text"
                                placeholder="Cari nama atau email peserta..."
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="flex flex-col gap-2"
                            />
                        </div>

                    </div>

                    {/* Member grid */}
                    {filtered.length === 0 ? (
                        <div className="text-center text-neutral-bg py-12">Tidak ada peserta ditemukan.</div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mt-8">
                            {filtered.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex items-center gap-x-3 bg-white border-primary-normal border-2 p-3 shadow-sm hover:shadow-md transition-shadow rounded-md"
                                >
                                    {member.photo_url ? (
                                        <Image
                                            src={member.photo_url}
                                            alt={member.name}
                                            width={40}
                                            height={40}
                                            className="w-10 h-10  object-cover shrink-0 rounded-md"
                                        />
                                    ) : (
                                        <div className="w-10 h-10 bg-secondary-bg flex items-center justify-center shrink-0 rounded-md">
                                            <span className="text-white font-bold text-base">
                                                {member.name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                    )}

                                    <div className="flex flex-col min-w-0">
                                        <span className="font-semibold text-neutral-dark truncate">{member.name}</span>
                                        <span className="text-xs text-neutral-bg truncate">{member.email}</span>
                                        <span className="text-xs text-neutral-bg-active capitalize">{member.gender}</span>
                                        <span className="text-xs text-neutral-lighter mt-1">Joined {formatDate(member.joined_at)}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

            </div>
        </Container>
    );
}
