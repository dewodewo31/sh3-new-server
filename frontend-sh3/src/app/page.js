"use client";
import { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import "./globals.css";
import Container from "@/src/components/Container";
import Carousel from "@/src/components/Carousel";
import EventCard from "@/src/components/EventCard";
import TotalStatistic from "../components/TotalStatistics";
import BigEventCard from "../components/BigEventCard";
import LinkButton from "../components/LinkButton";
import { SponsorMarquee } from "../components/SponsorMarquee";
import { eventService } from "@/src/services/eventService";
import { HeroAnimate } from "../components/HeroAnimate";

import { RevealSection } from "../components/RevealSection";
import BatikOverlay from "../components/BatikOverlay";
import { categoryService } from "@/src/services/categoryService";

export default function Home() {
  const [events, setEvents] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("upcoming");
  const [activeCategory, setActiveCategory] = useState("all");
  const now = new Date();

  useEffect(() => {
    Promise.all([
      eventService.getAll({ per_page: 100 }),
      categoryService.getAll(),
    ])
      .then(([eventsRes, categoriesRes]) => {
        const data = eventsRes.data.data ?? [];
        setEvents(Array.isArray(data) ? data : []);
        setCategories(categoriesRes.data.data ?? []);
      })
      .catch((err) => console.error(err))
      .finally(() => setLoading(false));
  }, []);

  // ====== FILTER EVENTS ======
  const upcomingEvents = events
    .filter((item) => new Date(item.start_date) > now)
    .sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

  const ongoingEvents = events
    .filter(
      (item) =>
        new Date(item.start_date) <= now && new Date(item.end_date) >= now,
    )
    .sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

  const pastEvents = events
    .filter((item) => new Date(item.end_date) < now)
    .sort((a, b) => new Date(b.start_date) - new Date(a.start_date));

  // ====== GET EVENTS BERDASARKAN TAB ======
  const getEventsByTab = () => {
    switch (activeTab) {
      case "upcoming":
        return upcomingEvents;
      case "ongoing":
        return ongoingEvents;
      case "past":
        return pastEvents;
      default:
        return [];
    }
  };

  // ====== FILTER BERDASARKAN KATEGORI ======
  const displayedEvents = getEventsByTab().filter(
    (item) =>
      activeCategory === "all" || item.category?.name === activeCategory,
  );

  // ====== RENDER EMPTY STATE ======
  const getEmptyMessage = () => {
    switch (activeTab) {
      case "upcoming":
        return "Belum ada event yang akan datang.";
      case "ongoing":
        return "Belum ada event yang sedang berlangsung.";
      case "past":
        return "Belum ada event yang sudah selesai.";
      default:
        return "Belum ada event.";
    }
  };
  return (
    <Container className="flex flex-col">
      <div className="flex flex-col flex-1 pt-8 md:pt-0 min-h-screen relative">
        {/* <div className="absolute inset-0 bg-[url('/assets/images/batik1.jpg')] bg-repeat bg-size-[100px] opacity-15 -z-10" /> */}
        <Image
          src="/assets/images/home1.png"
          alt="Hero background"
          fill // 2. Forces image to expand to the parent size
          priority // 3. Preloads the image if it's above the fold // 4. Tells Next.js to serve a full-width image size
          className="object-cover -z-1 brightness-75 size-full absolute inset-0"
        />
        <div className="flex flex-col justify-center items-center h-full mt-16">
          {/* Logo: muncul dari atas */}
          <HeroAnimate
            animation="fadeDown"
            delay={0}
            className="flex flex-col p-8 w-full md:w-1/2 items-center"
          >
            <Image
              src="/assets/images/sh3logo.png"
              alt="Logo"
              width={250}
              height={250}
              className="object-cover rounded-lg"
            />
          </HeroAnimate>

          <div className="flex flex-col w-full md:w-3/4 text-white">
            {/* Judul: scale dari kecil ke normal */}
            <HeroAnimate animation="scaleUp" delay={300}>
              <h1 className="text-5xl font-bold text-center">
                Samarinda <br className="hidden sm:inline" />
                <span className="text-primary-light">Hash </span>
                House Harriers
              </h1>
            </HeroAnimate>

            {/* Subjudul: fade dari bawah */}
            <HeroAnimate animation="fadeUp" delay={500}>
              <h2 className="text-3xl font-semibold font-young my-4 text-center">
                On On! - <span className="text-primary-text">#Adventure </span>
                in <span className="text-emerald-400">Nature</span>
              </h2>
            </HeroAnimate>

            {/* Paragraf + tombol: fade dari bawah */}
            <HeroAnimate animation="fadeUp" delay={700}>
              <p className="py-5 text-center font-semibold mx-4 md:mx-0">
                A Drinking Club With a Running Problem, Kami mengadakan lari
                mingguan di berbagai sudut kota dan alam Kalimantan, dilanjutkan
                dengan sesi down-down yang penuh keceriaan.
              </p>
              <div className="flex flex-row flex-wrap justify-center">
                <LinkButton
                  destination="/about"
                  text="About"
                  bg_color="primary-bg"
                  bg_color_hover="primary-bg-hover"
                  bg_color_active="primary-bg-active"
                />
                <LinkButton
                  destination="/events"
                  text="Event Terdekat"
                  bg_color="primary-bg"
                  bg_color_hover="primary-bg-hover"
                  bg_color_active="primary-bg-active"
                />
              </div>
            </HeroAnimate>
          </div>
        </div>

        <div className="absolute inset-x-0 bottom-0 h-1/4 bg-linear-to-b from-transparent to-primary-light -z-1" />
      </div>

      <div className="bg-linear-to-t from-primary-light via-primary-light-active to-primary-light">
        <div className="text-center text-3xl  mt-8 font-bold font-young relative heading-separator w-3/4 mx-auto">
          Sponsor Advertisement
        </div>
        <RevealSection direction="up">
          <SponsorMarquee />
        </RevealSection>
      </div>

      <RevealSection direction="up">
        <TotalStatistic />
        <div className="flex flex-col bg-primary-light-active p-8 gap-y-8">
          <div className="text-primary-darker text-2xl text-center font-bold lining-nums">
            REGULAR RUN | EVERY SUNDAY 14.00 WITA OPEN REGISTRATION | Samarinda
          </div>
        </div>
      </RevealSection>

      <div className="relative bg-linear-to-br from-primary-light via-primary-light-active to-primary-light p-4 md:p-8">
        <BatikOverlay />
        <div className="max-w-306 mx-auto px-4 md:px-0">
          <RevealSection direction="up">
            <div className="flex flex-col items-center justify-center pt-24 pb-8">
              <h1 className="text-5xl font-bold font-young ">Events</h1>
            </div>
          </RevealSection>

          <div className="flex flex-col">
            <h2 className="text-5xl font-bold font-young text-center">
              Major Events
            </h2>
            <div className="flex md:flex-row justify-center gap-8 flex-col my-4">
              {loading ? (
                <p className="text-xl w-full text-center">Loading...</p>
              ) : (
                (() => {
                  const bigEvents = events
                    .filter(
                      (item) =>
                        new Date(item.end_date) >= now &&
                        item.category?.name === "Major Events",
                    )
                    .sort(
                      (a, b) => new Date(a.start_date) - new Date(b.start_date),
                    )
                    .slice(0, 4);

                  return bigEvents.length === 0 ? (
                    <p className="text-xl text-center text-neutral-dark py-12 w-full">
                      Belum ada Major Events. Pantau terus ya!
                    </p>
                  ) : (
                    bigEvents.map((item, i) => (
                      <RevealSection
                        key={i}
                        direction="up"
                        delay={i * 100}
                        className="flex justify-center w-full"
                      >
                        <BigEventCard
                          key={item.id}
                          id={item.id}
                          title={item.title}
                          start_date={item.start_date}
                          end_date={item.end_date}
                          category={item.category?.name}
                          img={item.image_url}
                          status={item.status}
                        />
                      </RevealSection>
                    ))
                  );
                })()
              )}
            </div>
          </div>

          {/* ====== TAB FILTER ====== */}
          <div className="flex flex-row gap-4 border-b-2 border-neutral-normal mb-4">
            <button
              onClick={() => setActiveTab("upcoming")}
              className={`cursor-pointer pb-3 px-2 font-bold text-xl font-young transition-all ${
                activeTab === "upcoming"
                  ? "text-secondary-bg border-b-4 border-secondary-bg"
                  : "text-neutral-dark hover:text-secondary-bg"
              }`}
            >
              Upcoming Events
            </button>
            <button
              onClick={() => setActiveTab("ongoing")}
              className={`cursor-pointer pb-3 px-2 font-bold text-xl font-young transition-all ${
                activeTab === "ongoing"
                  ? "text-secondary-bg border-b-4 border-secondary-bg"
                  : "text-neutral-dark hover:text-secondary-bg"
              }`}
            >
              Ongoing Events
              {ongoingEvents.length > 0 && (
                <span className="ml-1 text-sm bg-emerald-500 text-white px-2 py-0.5 rounded-full">
                  {ongoingEvents.length}
                </span>
              )}
            </button>
            <button
              onClick={() => setActiveTab("past")}
              className={`cursor-pointer pb-3 px-2 font-bold text-xl font-young transition-all ${
                activeTab === "past"
                  ? "text-secondary-bg border-b-4 border-secondary-bg"
                  : "text-neutral-dark hover:text-secondary-bg"
              }`}
            >
              Past Events
            </button>
          </div>

          {/* ====== FILTER KATEGORI ====== */}
          <div className="flex flex-row flex-wrap gap-3 mb-8">
            <button
              onClick={() => setActiveCategory("all")}
              className={`px-5 py-2 font-medium font-young transition-all rounded-md border-2 cursor-pointer ${
                activeCategory === "all"
                  ? "bg-secondary-bg text-white border-secondary-bg"
                  : "bg-transparent text-neutral-dark border-neutral-normal hover:border-emerald-600 hover:text-emerald-600"
              }`}
            >
              All Events
            </button>
            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => setActiveCategory(cat.name)}
                className={`px-5 py-2 font-medium font-young transition-all rounded-md border-2 cursor-pointer ${
                  activeCategory === cat.name
                    ? "bg-secondary-bg text-white border-secondary-bg"
                    : "bg-transparent text-neutral-dark border-neutral-normal hover:border-emerald-600 hover:text-emerald-600"
                }`}
              >
                {cat.name}
                <span className="ml-1 text-xs opacity-60">
                  ({cat.events_count || 0})
                </span>
              </button>
            ))}
          </div>

          {/* ====== EVENTS GRID ====== */}
          {loading ? (
            <div className="flex justify-center py-16 text-xl">Loading...</div>
          ) : displayedEvents.length === 0 ? (
            <div className="flex justify-center py-16 text-xl text-neutral-dark">
              {getEmptyMessage()}
            </div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 pb-16">
              {displayedEvents.map((item, i) => (
                <RevealSection key={i} direction="up" delay={i * 50}>
                  <EventCard
                    id={item.id}
                    title={item.title}
                    start_date={item.start_date}
                    end_date={item.end_date}
                    category={item.category?.name}
                    description={item.description}
                    location={item.location}
                    img={item.image_url}
                    status={item.status}
                  />
                </RevealSection>
              ))}
            </div>
          )}
        </div>
      </div>
    </Container>
  );
}
