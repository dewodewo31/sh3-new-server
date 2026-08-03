@extends('layouts.app')

@section('title', 'Scan QR Attendance')
@section('subtitle', 'Scan QR peserta untuk check-in secara real-time')

@section('breadcrumb')
    @include('includes.breadcrumb', ['items' => [
        ['label' => 'Attendance', 'url' => route('admin.attendance.report')],
        ['label' => 'Scan QR'],
    ]])
@endsection

@push('head')
    <script defer src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endpush

@section('content')
<div x-data="qrScanner" x-init="init()" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Scanner --}}
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="card-header-title">Kamera Scanner</h3>
                    <p class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">Arahkan kamera ke QR Code peserta</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <div id="qr-reader" class="w-full"></div>

                <div x-show="!scanning && !loading" x-cloak class="flex flex-col items-center px-6 py-10 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-700/60 text-slate-300 dark:text-slate-500">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">Kamera belum aktif</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Klik "Mulai Scan" untuk mengaktifkan kamera.</p>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-center">
                <button type="button" @click="scanning ? stopScanner() : startScanner()" class="btn btn-primary flex-1" :disabled="processing">
                    <svg x-show="!scanning" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                    </svg>
                    <svg x-show="scanning" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 15L9 20.25m0 0l3 3m-3-3H6.75a2.25 2.25 0 01-2.25-2.25V15.75A2.25 2.25 0 014.5 13.5m6.75 6.75H15m0 0l3 3m-3-3V21m6-3.75A2.25 2.25 0 0015.75 15.75H15m0 0V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v2.25m13.5 3.75h.008v.008h-.008v-.008z"/>
                    </svg>
                    <span x-text="scanning ? 'Stop Scan' : 'Mulai Scan'"></span>
                </button>
                <button type="button" @click="stopScanner()" class="btn btn-secondary flex-1 sm:flex-none" :disabled="!scanning">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Hentikan
                </button>
            </div>
        </div>
    </div>

    {{-- Hasil & input manual --}}
    <div class="space-y-6">
        {{-- Manual input --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Input Manual</h3>
            </div>
            <div class="card-body">
                <form @submit.prevent="submitManual()" class="space-y-3">
                    <div class="form-group">
                        <label for="qr_code" class="form-label">Kode QR Peserta</label>
                        <input type="text" id="qr_code" name="qr_code" x-model="manualCode" placeholder="SH3-1-42-aBcDeFgH" class="form-input font-mono text-xs" aria-label="Kode QR">
                        <p class="form-hint">Gunakan jika kamera tidak tersedia atau QR sulit terbaca.</p>
                    </div>
                    <button type="submit" class="btn btn-outline w-full" :disabled="!manualCode || processing">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Proses Check-in
                    </button>
                </form>
            </div>
        </div>

        {{-- Result panel --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title">Hasil Scan</h3>
                <span class="badge" :class="result ? (result.success ? 'badge-success' : 'badge-danger') : 'badge-secondary'" x-text="result ? (result.success ? 'Berhasil' : 'Gagal') : 'Menunggu'"></span>
            </div>
            <div class="card-body min-h-56">
                {{-- Processing --}}
                <div x-show="processing" x-cloak class="flex flex-col items-center py-10 text-center">
                    <svg class="h-8 w-8 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="mt-4 text-sm font-medium text-slate-900 dark:text-slate-100">Memproses check-in...</p>
                </div>

                {{-- Empty --}}
                <div x-show="!processing && !result" x-cloak class="empty-state py-8">
                    <div class="empty-state-icon">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z"/>
                        </svg>
                    </div>
                    <p class="empty-state-title">Belum ada hasil scan</p>
                    <p class="empty-state-text">Hasil check-in peserta akan tampil di sini.</p>
                </div>

                {{-- Success --}}
                <div x-show="!processing && result && result.success" x-cloak class="flex flex-col items-center py-4 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-slate-900 dark:text-slate-100" x-text="result.data.participant_name"></p>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400" x-text="result.data.event_title"></p>
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                        <span class="badge badge-success">Check-in berhasil</span>
                        <span class="badge badge-info" x-text="'Waktu: ' + result.data.check_in_time"></span>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-5" @click="resetResult()">
                        Scan Berikutnya
                    </button>
                </div>

                {{-- Error --}}
                <div x-show="!processing && result && !result.success" x-cloak class="flex flex-col items-center py-4 text-center">
                    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Check-in gagal</p>
                    <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400" x-text="result.message"></p>
                    <button type="button" class="btn btn-secondary btn-sm mt-5" @click="resetResult()">
                        Coba Lagi
                    </button>
                </div>
            </div>
        </div>

        {{-- Info --}}
        <div class="alert alert-info" role="note">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <div>
                <p class="alert-title">Tips</p>
                <p class="alert-desc">Akses kamera membutuhkan koneksi HTTPS atau localhost. Pastikan izin kamera diizinkan oleh browser. Setiap QR hanya berlaku untuk 1 peserta pada 1 event.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('qrScanner', () => ({
            scanner: null,
            scanning: false,
            loading: false,
            processing: false,
            manualCode: '',
            result: null,
            lastScanAt: 0,

            init() {
                window.__qrScanner = this;
            },

            async startScanner() {
                if (typeof Html5Qrcode === 'undefined') {
                    showToast('error', 'Library tidak termuat', 'html5-qrcode gagal dimuat. Periksa koneksi internet.');
                    return;
                }
                this.loading = true;
                this.result = null;

                try {
                    this.scanner = new Html5Qrcode('qr-reader');
                    await this.scanner.start(
                        { facingMode: 'environment' },
                        {
                            fps: 20,
                            qrbox: (viewfinderWidth, viewfinderHeight) => {
                                const min = Math.min(viewfinderWidth, viewfinderHeight);
                                return { width: min * 0.8, height: min * 0.8 };
                            },
                            aspectRatio: 1.0,
                            videoConstraints: {
                                facingMode: 'environment',
                                width: { ideal: 1280 },
                                height: { ideal: 720 },
                            },
                            experimentalFeatures: {
                                useBarCodeDetectorIfSupported: true,
                            },
                        },
                        (decodedText) => {
                            if (!this.processing && Date.now() - this.lastScanAt > 1500) {
                                this.processQr(decodedText);
                            }
                        },
                        () => {}
                    );
                    this.scanning = true;
                } catch (err) {
                    this.scanning = false;
                    showToast('error', 'Kamera tidak tersedia', 'Gunakan input manual untuk check-in.');
                } finally {
                    this.loading = false;
                }
            },

            async stopScanner() {
                if (this.scanner) {
                    try {
                        await this.scanner.stop();
                        this.scanner.clear();
                    } catch (e) {}
                    this.scanner = null;
                }
                this.scanning = false;
            },

            async submitManual() {
                if (!this.manualCode.trim()) return;
                this.result = null;
                await this.processQr(this.manualCode.trim());
                this.manualCode = '';
            },

            async processQr(qrText) {
                this.processing = true;

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                    const response = await fetch('{{ route("admin.attendance.scan.process") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ qr_code: qrText }),
                    });

                    const data = await response.json();

                    this.result = data;

                    if (data.success) {
                        showToast('success', 'Check-in berhasil', data.data.participant_name + ' hadir di ' + data.data.event_title);
                        navigator.vibrate?.(200);
                    } else {
                        showToast('error', 'Check-in gagal', data.message);
                    }
                } catch (e) {
                    this.result = { success: false, message: 'Terjadi kesalahan koneksi. Coba lagi.' };
                    showToast('error', 'Kesalahan', 'Tidak dapat terhubung ke server.');
                } finally {
                    this.processing = false;
                    this.lastScanAt = Date.now();
                }
            },

            resetResult() {
                this.result = null;
            },
        }));
    });
</script>
@endpush
