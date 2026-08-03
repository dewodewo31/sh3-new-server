"use client";
import { useState, useEffect } from "react";

import Container from "@/src/components/Container";
import Image from "next/image";
import Link from "next/link";
import { ArrowLongLeftIcon } from "@heroicons/react/24/outline";
import { MapPinIcon } from "@heroicons/react/24/solid";
import { concateDate } from "@/src/lib/utils";
import { eventService } from "@/src/services/eventService";
import SponsorSection from "@/src/components/SponsorSection";
import BatikOverlay from "@/src/components/BatikOverlay";
import TicketQR from "@/src/components/TicketQR";
import EventGallery from "@/src/components/EventGallery";

export default function UpcomingEvents() {
  const [event, setEvent] = useState(null);
  const [myOrder, setMyOrder] = useState(null);
  const [qrCode, setQrCode] = useState(null);
  const [showQR, setShowQR] = useState(false);
  const [qrLoading, setQrLoading] = useState(false);
  const [attendanceCode, setAttendanceCode] = useState(null);
  const [eventId, setEventId] = useState(null);

  const formatRupiah = (angka) => new Intl.NumberFormat("id-ID").format(angka);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get("id") ?? 1;

    setEventId(id);

    // Ambil detail event
    eventService
      .getById(id)
      .then((res) => setEvent(res.data.data))
      .catch(console.error);

    // Cek apakah user sudah join
    const token = localStorage.getItem("token");

    if (token) {
      eventService
        .getMyEvents()
        .then((res) => {
          const joined = res.data.data.find(
            (e) => e.id === Number(id)
          );

          if (joined) {
            setMyOrder(joined.order);
          }
        })
        .catch(() => { });
    }
  }, []);

  async function handleLihatQR() {
    if (qrCode) {
      setShowQR(!showQR);
      return;
    }

    setQrLoading(true);
    try {
      const qr = myOrder?.attendance?.qr_code || myOrder?.ticket_code;

      if (qr) {
        setQrCode(qr);
        setAttendanceCode(qr);
        setShowQR(true);
        return;
      }

      // Fallback: coba daftar ulang (untuk event gratis yang belum ada order-nya)
      const res = await eventService.book(event.id);
      const payload = res.data.data;
      if (payload.qr_code) {
        setQrCode(payload.qr_code);
        setAttendanceCode(payload.qr_code);
        setShowQR(true);
      }
    } catch (err) {
      const payload = err.response?.data?.data;
      if (payload?.qr_code) {
        setQrCode(payload.qr_code);
        setAttendanceCode(payload.qr_code);
        setShowQR(true);
      }
    } finally {
      setQrLoading(false);
    }
  }

  function higherPrice(event_price) {
    if (event_price > 0) {
      return event_price * 2;
    } else {
      return 1000000;
    }
  }

  if (!event)
    return <div className="flex justify-center p-16 text-2xl mt-16 h-screen">Loading...</div>;

  const isPaid = myOrder?.status === "paid" || myOrder?.status === "free";
  const isPending = myOrder?.status === "pending";
  const isCancelled = myOrder?.status === "cancelled";

  return (
    <Container className="flex flex-col gap-y-4 w-full">
      <div className="relative bg-linear-to-br from-primary-light via-primary-light-active to-primary-light">
        <BatikOverlay />
        <div className="mt-8 max-w-306 mx-auto relative z-1">
          <Link href="/events" className="static md:absolute">
            <ArrowLongLeftIcon className="w-8 h-8 md:w-16 md:h-16" />
          </Link>
          <div className="flex items-center justify-center w-full">
            <h1 className="text-4xl font-bold  mt-16">{event.title}</h1>
          </div>

          <div className="flex flex-row justify-between gap-x-2 mt-8 ">
            <div className="flex flex-row justify-center gap-x-2 w-1/2">
              <MapPinIcon className="w-8 h-8 " />
              <div className="text-lg font-bold">{event.location}</div>
            </div>
            <div className="text-lg font-bold">
              {concateDate(event.start_date, event.end_date)}
            </div>
          </div>

          {event.image_url ? (
            <Image
              src={event.image_url}
              alt={event.title}
              width={600}
              height={450}
              className="h-128 w-full flex object-cover mt-4"
            />
          ) : (
            <div className="h-128 w-full bg-neutral-bg flex items-center justify-center text-5xl font-bold font-young border-1 mt-4">
              {event.title.slice(0, 2).toUpperCase()}
            </div>
          )}

          <div className="grid grid-rows-1 gap-x-16 md:grid-cols-3 my-8 mx-4 md:mx-0">
            <div className="col-span-1 flex flex-col md:col-span-2">
              <h2 className="text-2xl font-bold ">Tentang Event</h2>
              <div className="text-sm">{event.description}</div>

              {/* Belum join → Daftar Sekarang */}
              {!myOrder && (
                <Link href={`/events/register?id=${event.id}`}>
                  <div className="cursor-pointer flex justify-center items-center rounded-md bg-secondary-bg h-32 font-bold text-2xl text-white hover:bg-secondary-bg-hover m-10 md:text-5xl active:bg-secondary-bg-active">
                    Daftar Sekarang
                  </div>
                </Link>
              )}

              {/* Sudah join tapi belum dikonfirmasi admin */}
              {isPending && (
                <div className="flex flex-col gap-2 m-10">
                  <div className="flex justify-center items-center bg-neutral-normal-active h-20 font-bold text-xl text-white cursor-not-allowed rounded-md text-center">
                    Menunggu Konfirmasi Admin
                  </div>
                  <p className="text-center text-sm text-gray-500 rounded-md">
                    Pembayaranmu sedang diverifikasi. Silakan cek kembali nanti.
                  </p>
                </div>
              )}

              {isCancelled && (
                <>
                  <div className="flex flex-col gap-2 m-10">
                    <div className="flex justify-center items-center bg-red-500 h-20 font-bold text-xl text-white cursor-not-allowed rounded-md">
                      Ordermu Telah dibatalkan
                    </div>
                    <p className="text-center text-sm text-gray-500 rounded-md">
                      Ordermu telah dibatalkan oleh Admin, silahkan menghubungi admin untuk informasi lebih lanjut
                    </p>
                  </div>
                  <Link href={`/events/register?id=${event.id}`}>
                    <div className="cursor-pointer flex justify-center items-center rounded-md bg-secondary-bg h-32 font-bold text-2xl text-white hover:bg-secondary-bg-hover m-10 md:text-5xl active:bg-secondary-bg-active">
                      Daftar Sekarang
                    </div>
                  </Link>
                </>
              )}

              {/* Sudah dikonfirmasi → QR + Lihat Peserta */}
              {isPaid && (
                <div className="flex flex-col gap-4 m-10">
                  <button
                    onClick={handleLihatQR}
                    disabled={qrLoading}
                    className={`flex justify-center items-center h-20 font-bold text-2xl text-white transition-colors rounded-md
                                    ${qrLoading ? "bg-neutral-normal-active cursor-not-allowed" : " cursor-pointer bg-secondary-bg hover:bg-secondary-bg-hover active:bg-secondary-bg-active"}`}
                  >
                    {qrLoading
                      ? "Memuat..."
                      : showQR
                        ? "Sembunyikan Tiket QR"
                        : "Lihat Tiket QR"}
                  </button>

                  <Link href={`/events/members?id=${event.id}`}>
                    <div className="flex justify-center items-center  rounded-md font-young h-20 font-bold text-2xl text-secondary-bg bg-transparent border-2 border-secondary-bg hover:border-transparent hover:bg-secondary-bg hover:text-white active:border-transparent active:bg-secondary-bg active:text-white focus:border-transparent focus:bg-secondary-bg focus:text-white transition-all">
                      Lihat Peserta
                    </div>
                  </Link>
                </div>
              )}
            </div>

            <div className="bg-primary-light gap-x-4 p-4 border-4 border-primary-normal text-primary-normal rounded-md">
              <div className="flex flex-col">
                <h3 className="text-2xl font-bold font-young">Early Bid</h3>
                <div className="text-sm line-through">
                  Rp. {formatRupiah(higherPrice(event.price))}
                </div>
                <div className="text-lg font-bold">
                  Rp. {formatRupiah(event.price)}/person
                </div>
              </div>
              <div className="flex flex-col">
                <ol className="list-decimal list-inside p-2">
                  {event.key_points?.map((point, i) => (
                    <li key={i}>{point}</li>
                  ))}
                </ol>
                <div className="text-xl font-bold">Event Organizer</div>
                <div className="text-lg font-semibold">{event.creator?.name}</div>
              </div>
            </div>
          </div>
          {/* Tampilan QR Code */}
          {isPaid && showQR && qrCode && (
            <div className="flex flex-col items-center gap-4 bg-primary-light border-2 border-neutral-normal p-8 mb-10 mt-8 rounded-md">
              <div className="text-xl font-bold">Tiket QR Kamu</div>

              <div className="p-8 bg-white rounded-md">
                <TicketQR value={attendanceCode || myOrder?.ticket_code || qrCode} />
              </div>

              <div className="text-sm text-center">
                Tunjukkan QR ini saat check-in di Acara
              </div>
              <div className="text-2xl font-young px-4 py-2 text-neutral-dark bg-neutral-bg lining-nums rounded-md">
                {attendanceCode ?? myOrder?.ticket_code}
              </div>
            </div>
          )}

          {/* 🔥 TAMPILKAN MERCHANDISE UNTUK SEMUA (tidak perlu isPaid) */}
          {event.merchandise?.length > 0 && (
            <div className="mt-8 mb-10">
              <h2 className="text-2xl font-bold font-young mb-4 text-primary-darker">Merchandise Event</h2>
              <p className="text-sm text-neutral-dark mb-4">
                {isPaid ? "Harga spesial khusus peserta event ini" : "Dapatkan harga spesial dengan mendaftar event!"}
              </p>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                {event.merchandise.map(item => {
                  // 🔥 Ambil harga dari database
                  const originalPrice = Number(item.price) || 0;
                  const eventPrice = Number(item.event_price) || 0; // ← dari admin
                  const hasDiscount = eventPrice > 0 && eventPrice < originalPrice;
                  const finalPrice = isPaid && hasDiscount ? eventPrice : originalPrice;

                  // 🔥 Hitung diskon persen untuk badge
                  const discountPercent = hasDiscount
                    ? Math.round((1 - eventPrice / originalPrice) * 100)
                    : 0;

                  // 🔥 Build URL
                  let orderUrl = `/merchandise/order?id=${item.id}`;
                  if (isPaid && hasDiscount) {
                    orderUrl += `&event_id=${event.id}&event_price=${finalPrice}&discount_percentage=${discountPercent}`;
                  }

                  return (
                    <div key={item.id} className="flex flex-col bg-primary-light border-2 border-neutral-normal hover:border-secondary-bg transition-colors rounded-md">
                      {/* Gambar */}
                      <div className="relative w-full h-[250px] overflow-hidden bg-neutral-bg">
                        {item.image_url ? (
                          <img
                            src={item.image_url}
                            alt={item.name}
                            className="w-full h-full object-cover"
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center text-neutral-dark text-4xl font-bold font-young">
                            {item.name.slice(0, 2).toUpperCase()}
                          </div>
                        )}
                        {hasDiscount && (
                          <div className="absolute top-2 left-2 bg-secondary-bg text-white text-xs font-bold px-2 py-1">
                            -{discountPercent}%
                          </div>
                        )}
                        {item.stock === 0 && (
                          <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <span className="text-white font-bold text-lg">Habis</span>
                          </div>
                        )}
                        {item.stock_status === "limited" && (
                          <div className="absolute top-2 right-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1">
                            Sisa {item.stock}
                          </div>
                        )}
                      </div>

                      {/* Info */}
                      <div className="p-3 flex flex-col gap-1 flex-1">
                        <div className="flex flex-col gap-1 flex-1">
                          <div className="font-semibold text-sm line-clamp-2">{item.name}</div>
                          {hasDiscount ? (
                            <div className="flex flex-col">
                              <div className="text-xs text-neutral-dark line-through">
                                Rp {formatRupiah(originalPrice)}
                              </div>
                              <div className="font-bold text-secondary-bg">
                                Rp {formatRupiah(eventPrice)}
                              </div>
                              {!isPaid && (
                                <div className="text-xs text-secondary-bg font-medium mt-1">
                                  🔒 Harga spesial untuk peserta event
                                </div>
                              )}
                            </div>
                          ) : (
                            <div className="font-bold text-secondary-bg">
                              Rp {formatRupiah(originalPrice)}
                            </div>
                          )}
                          {item.sizes?.length > 0 && (
                            <div className="text-xs text-neutral-dark">
                              Size: {item.sizes.join(", ")}
                            </div>
                          )}
                        </div>
                        <Link
                          href={orderUrl}
                          className={`mt-2 text-white text-center px-5 py-2.5 text-sm font-medium transition-colors font-young shadow-md
                        ${item.stock === 0
                              ? "bg-neutral-normal pointer-events-none opacity-60"
                              : isPaid && hasDiscount
                                ? "bg-secondary-bg hover:bg-secondary-bg-hover active:bg-secondary-bg-active"
                                : "bg-primary-bg hover:bg-primary-bg-hover active:bg-primary-bg-active"
                            }`}
                        >
                          {item.stock === 0 ? "Habis" : isPaid && hasDiscount ? "Pesan (Spesial)" : "Pesan"}
                        </Link>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {event.galleries && event.galleries.length > 0 && (
            <div className="mt-8 mb-10">
              <EventGallery images={event.galleries} />
            </div>
          )}

          {event.sponsors && <SponsorSection sponsors={event.sponsors} />}
        </div>
      </div>
    </Container>
  );
}
