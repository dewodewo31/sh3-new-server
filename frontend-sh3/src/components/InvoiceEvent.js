"use client"
import Image from "next/image"
import { useRef } from "react"
import dynamic from "next/dynamic"

const PDFDownloadLink = dynamic(
    () => import("@react-pdf/renderer").then((mod) => mod.PDFDownloadLink),
    { ssr: false }
)
import InvoiceEventPDF from "./InvoiceEventPDF"

export default function InvoiceEvent({
    name,
    email,
    hash_id,
    invoice_id,
    event_title,
    event_price,
    event_qty,
    status = "paid" // ← tambah status (default: paid)
}) {
    const isPending = status === "pending";

    return (
        <div className="flex flex-col gap-4 my-4">
            {/* Tombol Download PDF */}
            <div className="flex justify-end">
                <PDFDownloadLink
                    document={
                        <InvoiceEventPDF
                            name={name}
                            email={email}
                            hash_id={hash_id}
                            invoice_id={invoice_id}
                            event_title={event_title}
                            event_price={event_price}
                            event_qty={event_qty}
                            status={status} // ← kirim status ke PDF
                        />
                    }
                    fileName={`Invoice-${invoice_id}.pdf`}
                >
                    {({ loading }) => (
                        <button className="bg-secondary-bg hover:bg-secondary-bg-hover active:bg-secondary-bg-active text-white font-bold py-2 px-6 transition-colors rounded-md">
                            {loading ? "Menyiapkan PDF..." : "Download PDF"}
                        </button>
                    )}
                </PDFDownloadLink>
            </div>

            {/* Invoice Content - UI SAMA PERSIS SEPERTI YANG KAMU PUNYA */}
            <div className="flex flex-col bg-primary-light p-8 border-2 border-neutral-normal rounded-md">
                {/* Logo */}
                <div className="flex flex-col items-center p-8">
                    <img
                        src="/assets/images/sh3logo.png"
                        alt="Logo"
                        width={125}
                        height={125}
                        className="w-20 h-20 md:w-32 md:h-32 lg:w-32 lg:h-32 object-cover"
                    />
                </div>

                {/* Title + Status Badge */}
                <div className="flex flex-col items-center gap-2">
                    <div className="font-bold text-3xl md:text-5xl text-center">INVOICE</div>
                    {isPending ? (
                        <div className="inline-block bg-yellow-100 text-yellow-700 font-bold px-4 py-1 rounded-full text-sm">
                            ⏳ Menunggu Konfirmasi
                        </div>
                    ) : (
                        <div className="inline-block bg-green-100 text-green-700 font-bold px-4 py-1 rounded-full text-sm">
                            ✅ Lunas / Aktif
                        </div>
                    )}
                </div>

                {/* Info Customer */}
                <div className="flex flex-col md:flex-row mt-8 md:mt-16">
                    <div className="flex flex-col w-full md:w-1/2 px-4 md:px-16 text-lg ">
                        <div>To : {name}</div>
                        <div>Email : {email}</div>
                        <div>Hash ID : {hash_id}</div>
                    </div>
                    <div className="flex flex-col items-center w-full md:w-1/2 px-8 mt-4 md:mt-0">
                        <div className="font-bold text-xl ">
                            Invoice : {invoice_id}
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="overflow-x-auto my-8 md:my-16">
                    <table className="border border-neutral-dark table-auto divide-y w-full">
                        <thead>
                            <tr className="divide-neutral-dark divide-x bg-secondary-bg text-white">
                                <th className="p-4">Qty</th>
                                <th className="p-4">Description</th>
                                <th className="p-4">Price</th>
                                <th className="p-4">Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-dark">
                            <tr className="text-right divide-neutral-dark divide-x ">
                                <td className="text-center p-4">{event_qty}</td>
                                <td className="text-left p-4">{event_title} Ticket</td>
                                <td className="p-4">Rp. {event_price}</td>
                                <td className="p-4">Rp. {event_price}</td>
                            </tr>
                            <tr className="divide-neutral-dark divide-x bg-secondary-bg text-white">
                                <th></th>
                                <th></th>
                                <th className="p-4">Total</th>
                                <th className="p-4">Rp. {event_price}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {/* Status Message */}
                {isPending ? (
                    <div className="text-yellow-600 text-center font-medium">
                        ⏳ Pembayaran sedang diverifikasi oleh admin.<br />
                        QR Code akan aktif setelah pembayaran dikonfirmasi.
                    </div>
                ) : (
                    <div className="text-green-600 text-center font-medium">
                        ✅ Pembayaran telah dikonfirmasi. QR Code aktif!
                    </div>
                )}

                <div className="text-red-500 text-center mt-4">
                    Tolong hubungin Admin jika ada pertanyaan terkait pembayaran atau hal yang lain!
                </div>
            </div>
        </div>
    )
}