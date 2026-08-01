"use client"
import { useState, useEffect } from "react"
import Link from "next/link"
import Image from "next/image"
import Container from "@/src/components/Container"
import SelectInput from "@/src/components/SelectInput"
import InputType from "@/src/components/Inputs"
import InvoiceMerch from "@/src/components/InvoiceMerch"
import { RevealSection } from "@/src/components/RevealSection"
import { ArrowLongLeftIcon } from "@heroicons/react/24/outline"
import { ChevronUpIcon } from "@heroicons/react/24/solid"
import { merchandiseService } from "@/src/services/merchandiseService"
import { formatRupiah } from "@/src/lib/utils"
import { useRouter } from "next/navigation"
import ImageUpload from "@/src/components/ImageUpload"
import Swal from "sweetalert2"
import BatikOverlay from "@/src/components/BatikOverlay"

export default function MerchandiseOrderPage() {
    const paymentMethodOptions = [
        { value: "bank_transfer", label: "Bank Transfer" },
        { value: "qris", label: "QRIS" },
    ]

    // 🔥 OPSI PENGIRIMAN
    const shippingOptions = [
        { value: "delivery", label: "Kirim ke Alamat" },
        { value: "pickup", label: "Ambil di Tempat" },
    ]

    const [item, setItem] = useState(null)
    const [qty, setQty] = useState(1)
    const [selectedSize, setSelectedSize] = useState("")
    const [selectedColor, setSelectedColor] = useState("")
    const [shippingMethod, setShippingMethod] = useState("delivery") // ← baru
    const [shippingAddress, setShippingAddress] = useState("")
    const [shippingPhone, setShippingPhone] = useState("")
    const [submitLoading, setSubmitLoading] = useState(false)
    const [orderResult, setOrderResult] = useState(null)
    const [userData, setUserData] = useState(null)
    const [paymentFile, setPaymentFile] = useState(null)
    const [paymentMethod, setPaymentMethod] = useState("")
    const [confirmOrder, setConfirmedOrder] = useState(null)

    // State untuk diskon event
    const [eventId, setEventId] = useState(null)
    const [eventPrice, setEventPrice] = useState(null)
    const [discountPercentage, setDiscountPercentage] = useState(null)

    const router = useRouter()

    useEffect(() => {
        const token = localStorage.getItem("token")
        if (!token) {
            Swal.fire({
                icon: "warning",
                title: "Belum login!",
                text: "Kamu harus login dulu untuk memesan merchandise.",
                confirmButtonText: "Login Sekarang",
            }).then(() => router.push("/members/detail"))
            return
        }

        const params = new URLSearchParams(window.location.search)
        const id = params.get("id")
        const eId = params.get("event_id")
        const ePrice = params.get("event_price")
        const eDiscount = params.get("discount_percentage")

        if (!id) return

        if (eId) setEventId(eId)
        if (ePrice) setEventPrice(Number(ePrice))
        if (eDiscount) setDiscountPercentage(Number(eDiscount))

        merchandiseService.getById(id)
            .then(res => setItem(res.data.data))
            .catch(err => console.error(err))

        const user = localStorage.getItem("user")
        if (user) setUserData(JSON.parse(user))
    }, [])

    const activePrice = eventPrice ?? item?.price ?? 0
    const totalPrice = activePrice * qty

    // 🔥 HANDLE SHIPPING METHOD CHANGE
    const handleShippingMethodChange = (e) => {
        const method = e.target.value
        setShippingMethod(method)
        if (method === "pickup") {
            setShippingAddress("Ambil di Tempat (Event)")
            setShippingPhone("")
        } else {
            setShippingAddress("")
            setShippingPhone("")
        }
    }

    async function submitOrder(e) {
        e.preventDefault()

        if (!userData) {
            Swal.fire({ icon: "warning", title: "Belum login!", text: "Kamu harus login terlebih dahulu." })
            return
        }
        if (item.sizes?.length > 0 && !selectedSize) {
            Swal.fire({ icon: "warning", title: "Pilih ukuran dulu!" })
            return
        }
        if (item.colors?.length > 0 && !selectedColor) {
            Swal.fire({ icon: "warning", title: "Pilih warna dulu!" })
            return
        }
        if (!shippingMethod) {
            Swal.fire({ icon: "warning", title: "Pilih metode pengiriman dulu!" })
            return
        }
        if (shippingMethod === "delivery") {
            if (!shippingAddress) {
                Swal.fire({ icon: "warning", title: "Isi alamat pengiriman dulu!" })
                return
            }
            if (!shippingPhone) {
                Swal.fire({ icon: "warning", title: "Isi nomor HP pengiriman dulu!" })
                return
            }
        }
        if (!paymentFile) {
            Swal.fire({ icon: "warning", title: "Upload bukti pembayaran dulu!" })
            return
        }
        if (!paymentMethod) {
            Swal.fire({ icon: "warning", title: "Pilih metode pembayaran dulu!" })
            return
        }

        setSubmitLoading(true)
        try {
            const orderPayload = {
                merchandise_id: item.id,
                customer_name: userData.name || "Member",
                customer_contact: userData.phone || userData.email || "-",
                quantity: qty,
                size: selectedSize || "-",
            }

            const orderRes = await merchandiseService.createOrder(orderPayload)
            const order = orderRes.data.data
            const orderId = order.id
            const invoiceNumber = order.payment?.invoice_number || `#MO-${order.id}`

            const formData = new FormData()
            formData.append("payment_proof", paymentFile)
            formData.append("payment_method", paymentMethod)

            await merchandiseService.uploadPayment(orderId, formData)

            setOrderResult({
                order_id: orderId,
                invoice_number: invoiceNumber,
                payment_instructions: null,
            })
            setConfirmedOrder(true);
            setTimeout(() => {
                document.getElementById("invoice-section")?.scrollIntoView({ behavior: "smooth" })
            }, 300)

        } catch (err) {
            setConfirmedOrder(false);
            Swal.fire({
                icon: "error",
                title: "Gagal!",
                text: err.response?.data?.message || "Terjadi kesalahan, coba lagi.",
            })
        } finally {
            setSubmitLoading(false)
        }
    }

    if (!item) return <div className="flex justify-center p-16 text-2xl">Loading...</div>

    const sizeOptions = item.sizes?.map(s => ({ value: s, label: s })) ?? []
    const colorOptions = item.colors?.map(c => ({ value: c, label: c })) ?? []
    const originalPrice = Number(item?.price) || 0;
    const eventPriceFromParams = Number(eventPrice) || 0;
    const hasDiscount = eventPriceFromParams > 0 && eventPriceFromParams < originalPrice;

    return (
        <Container className="flex flex-col gap-y-4 w-full px-4 md:px-0 ">
            <div className="relative bg-linear-to-br from-primary-light via-primary-light-active to-primary-light p-8">
                <BatikOverlay />
                <div className="max-w-306 mx-auto">
                    <RevealSection direction="up">
                        <div className="flex flex-col gap-y-4 mt-24">
                            <Link href={eventId ? `/events/upcoming?id=${eventId}` : "/merchandise"} className="static md:absolute">
                                <ArrowLongLeftIcon className="w-8 h-8 md:w-16 md:h-16" />
                            </Link>
                            <div className="flex items-center justify-center w-full">
                                <h1 className="text-4xl font-bold font-young">{item.name}</h1>
                            </div>
                        </div>
                    </RevealSection>

                    <RevealSection direction="up">
                        {item.image_url ? (
                            <img src={item.image_url} alt={item.name} className="h-80 w-full object-cover mt-4" />
                        ) : (
                            <div className="h-80 w-full bg-neutral-bg flex items-center justify-center text-5xl font-bold font-young border-1">
                                {item.name.slice(0, 2).toUpperCase()}
                            </div>
                        )}
                    </RevealSection>

                    <RevealSection direction="up">
                        <div className="flex justify-center gap-8 flex-col md:flex-row">
                            <div className="flex flex-col items-center justify-center my-4 bg-primary-light border-neutral-normal border-2 p-4 w-full rounded-md" >
                                <div className="font-bold text-2xl">Stok Tersisa</div>
                                <div className={`font-bold text-3xl font-young ${item.stock === 0 ? "text-red-500" : ""}`}>
                                    {item.stock === 0 ? "Habis" : item.stock}
                                </div>
                            </div>
                            <div className="bg-primary-light border-2 border-neutral-normal my-4 p-4 w-full rounded-md">
                                <h3 className="text-4xl font-bold font-young mb-2">{item.name}</h3>
                                <div className="text-sm text-neutral-dark mb-4">{item.description}</div>

                                {hasDiscount ? (
                                    <div className="flex flex-col gap-1">
                                        <div className="text-sm text-neutral-dark line-through">
                                            Rp. {formatRupiah(originalPrice)} / pcs
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <div className="text-lg font-bold text-secondary-bg">
                                                Rp. {formatRupiah(activePrice)} / pcs
                                            </div>
                                            {discountPercentage && (
                                                <div className="bg-secondary-bg text-white text-xs font-bold px-2 py-1">
                                                    -{discountPercentage}%
                                                </div>
                                            )}
                                        </div>
                                        <div className="text-xs text-secondary-bg font-medium">
                                            Harga spesial peserta event
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-lg font-bold text-secondary-bg">
                                        Rp. {formatRupiah(originalPrice)} / pcs
                                    </div>
                                )}

                                {item.category && (
                                    <div className="text-sm mt-1">Kategori: <span className="font-medium">{item.category}</span></div>
                                )}
                            </div>

                        </div>
                    </RevealSection>

                    {item.stock === 0 ? (
                        <div className="flex justify-center items-center h-32 bg-neutral-bg text-white font-bold text-2xl font-young">
                            Stok Habis
                        </div>
                    ) : (
                        <form onSubmit={submitOrder} className="flex flex-col gap-8">
                            <RevealSection direction="up">
                                <div className="flex flex-col bg-primary-light p-4 gap-4 border-neutral-normal border-2 rounded-md">
                                    <div className="flex justify-between">
                                        <div className="text-2xl font-bold font-young">Detail Order</div>
                                        <ChevronUpIcon className="w-4 h-4 md:w-8 md:h-8" />
                                    </div>
                                    <hr className="border-t-2 border-text-colors" />
                                    <div className="flex flex-col gap-2">
                                        <label className="font-medium text-xl">
                                            Jumlah <span className="text-red-500">*</span>
                                        </label>
                                        <div className="flex items-center gap-4">
                                            <button type="button" onClick={() => setQty(q => Math.max(1, q - 1))}
                                                className="cursor-pointer w-10 h-10 bg-neutral-normal text-white font-bold text-xl hover:bg-neutral-dark transition-colors">−</button>
                                            <span className="text-2xl font-bold w-8 text-center">{qty}</span>
                                            <button type="button" onClick={() => setQty(q => Math.min(item.stock, q + 1))}
                                                className="cursor-pointer w-10 h-10 bg-secondary-bg text-white font-bold text-xl hover:bg-secondary-bg-hover transition-colors">+</button>
                                        </div>
                                    </div>
                                    {sizeOptions.length > 0 && (
                                        <SelectInput id="size" name="size" label="Ukuran" required placehold="Pilih ukuran..."
                                            options={sizeOptions} value={selectedSize} onChange={e => setSelectedSize(e.target.value)} />
                                    )}
                                    {colorOptions.length > 0 && (
                                        <SelectInput id="color" name="color" label="Warna" required placehold="Pilih warna..."
                                            options={colorOptions} value={selectedColor} onChange={e => setSelectedColor(e.target.value)} />
                                    )}
                                </div>
                            </RevealSection>

                            {/* 🔥 SHIPPING SECTION - DENGAN OPSI */}
                            <RevealSection direction="up">
                                <div className="flex flex-col bg-primary-light border-neutral-normal border-2 p-4 gap-4 rounded-md">
                                    <div className="flex justify-between">
                                        <div className="text-2xl font-bold font-young">Shipping Info</div>
                                        <ChevronUpIcon className="w-4 h-4 md:w-8 md:h-8" />
                                    </div>
                                    <hr className="border-t-2 border-text-colors" />

                                    {/* 🔥 OPSI PENGIRIMAN */}
                                    <SelectInput
                                        id="shippingMethod"
                                        name="shippingMethod"
                                        label="Metode Pengiriman"
                                        required
                                        placehold="Pilih metode pengiriman..."
                                        options={shippingOptions}
                                        value={shippingMethod}
                                        onChange={handleShippingMethodChange}
                                    />

                                    {/* 🔥 ALAMAT - muncul hanya jika pilih "Kirim" */}
                                    {shippingMethod === "delivery" && (
                                        <InputType
                                            label="Alamat Pengiriman"
                                            id="shippingaddress"
                                            required
                                            type="text"
                                            name="shippingaddress"
                                            placeholder="Jl. Contoh No. 1, Kota"
                                            className="flex flex-col gap-2"
                                            value={shippingAddress}
                                            onChange={e => setShippingAddress(e.target.value)}
                                        />
                                    )}

                                    {/* 🔥 AMBIL DI TEMPAT - info alamat */}
                                    {shippingMethod === "pickup" && (
                                        <div className="bg-green-50 border border-green-200 rounded-md p-4">
                                            <p className="text-green-700 font-medium">
                                                Ambil di Tempat (Event)
                                            </p>
                                            <p className="text-sm text-gray-600 mt-1">
                                                Pesanan akan tersedia di lokasi event.
                                                {eventId && " Siapkan kode booking untuk mengambil."}
                                            </p>
                                        </div>
                                    )}

                                    {/* 🔥 NOMOR HP - TETAP MUNCUL UNTUK KEDUA OPSI */}
                                    <InputType
                                        label={shippingMethod === "pickup" ? "Nomor HP (untuk konfirmasi)" : "Nomor HP Pengiriman"}
                                        id="shippingphone"
                                        required
                                        type="text"
                                        name="shippingphone"
                                        placeholder="08123456789"
                                        className="flex flex-col gap-2"
                                        value={shippingPhone}
                                        onChange={e => setShippingPhone(e.target.value)}
                                    />
                                </div>
                            </RevealSection>

                            <RevealSection direction="up">
                                <div className="flex flex-col bg-primary-light border-neutral-normal border-2 p-4 gap-4 rounded-md">
                                    <div className="flex justify-between">
                                        <div className="text-2xl font-bold font-young">Payment Details</div>
                                        <ChevronUpIcon className="w-4 h-4 md:w-8 md:h-8" />
                                    </div>
                                    <hr className="border-t-2 border-text-colors" />
                                    <div className="flex justify-between">
                                        <div className="text-xl font-medium">Merchandise</div>
                                        <div className="text-xl font-medium">{item.name}</div>
                                    </div>
                                    <div className="flex justify-between">
                                        <div className="text-xl font-medium">Harga Satuan</div>
                                        <div className="flex flex-col items-end">
                                            {hasDiscount && (
                                                <div className="text-sm text-neutral-dark line-through">
                                                    Rp. {formatRupiah(item.price)}
                                                </div>
                                            )}
                                            <div className="text-xl font-medium">
                                                Rp. {formatRupiah(activePrice)}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex justify-between">
                                        <div className="text-xl font-medium">Jumlah</div>
                                        <div className="text-xl font-medium">{qty} pcs</div>
                                    </div>
                                    <hr className="border-t border-neutral-normal" />
                                    <div className="flex justify-between">
                                        <div className="text-xl font-bold">Total</div>
                                        <div className="text-2xl font-bold">Rp. {formatRupiah(totalPrice)}</div>
                                    </div>

                                    <SelectInput id="paymentmethod" name="paymentmethod" label="Metode Pembayaran" required
                                        placehold="Pilih metode pembayaran..." options={paymentMethodOptions}
                                        value={paymentMethod} onChange={e => setPaymentMethod(e.target.value)} />

                                    <div className="bg-primary-light-active border border-neutral-normal p-4 text-sm text-neutral-dark">
                                        <div className="flex flex-col items-center">
                                            <div className="font-bold mb-1">Cara Pembayaran:</div>
                                            <div>Setelah order dikonfirmasi, silakan transfer ke rekening berikut:</div>
                                            <div className="mt-2 font-semibold text-xl">Mandiri 1480087846666</div>
                                            <div className="mt-2 font-semibold">An. Samarinda Hidup Hutan Hijau</div>
                                            <div className="mt-1">Atau scan QRIS di bawah ini:</div>
                                            <Image src="/assets/images/qris.jpeg" alt="QRIS" width={300} height={300} className="mt-2 object-contain" />
                                        </div>
                                    </div>

                                    <div className="flex flex-col gap-2 mt-4">
                                        <div className="font-bold text-xl">Upload Bukti Pembayaran</div>
                                        <ImageUpload id="paymentproof" label="Bukti Transfer" required onChange={file => setPaymentFile(file)} />
                                    </div>
                                </div>
                            </RevealSection>

                            <RevealSection direction="up">
                                <button
                                    className={`flex justify-center font-young items-center rounded-md my-8 ${submitLoading ? "bg-primary-bg disabled" : "bg-secondary-bg hover:bg-secondary-bg-hover active:bg-secondary-bg-active"} h-16 font-bold text-xl text-white md:text-3xl w-full ${confirmOrder ? "hidden" : ""}`}
                                    type="submit" disabled={submitLoading}>
                                    {submitLoading ? "Memproses..." : "Confirm Order"}
                                </button>
                            </RevealSection>
                        </form>
                    )}

                    {orderResult && userData && (
                        <RevealSection direction="up">
                            <div id="invoice-section">
                                <InvoiceMerch
                                    name={userData.name}
                                    email={userData.email}
                                    hash_id={userData.id || userData.hash_id}
                                    invoice_id={orderResult.invoice_number}
                                    merch_name={item.name}
                                    merch_price={formatRupiah(activePrice)}
                                    merch_qty={qty}
                                    merch_size={selectedSize}
                                    merch_color={selectedColor}
                                    total_price={formatRupiah(totalPrice)}
                                    shipping_method={shippingMethod}
                                    shipping_address={shippingAddress}
                                />
                            </div>
                        </RevealSection>
                    )}
                </div>
            </div>
        </Container>
    )
}