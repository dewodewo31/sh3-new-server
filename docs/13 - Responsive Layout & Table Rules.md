# Responsive Layout & Table Rules (WAJIB)

Ikuti aturan berikut untuk setiap halaman yang memiliki tabel atau konten lebar.

## Sortable Table Columns (WAJIB)

Sejak **2026-08-15**, semua tabel index admin mendukung **sorting kolom**. Gunakan komponen
`x-th-sort` pada setiap `<th>` yang kolomnya bisa diurutkan.

### Cara Pakai

```blade
<x-th-sort column="name">Name</x-th-sort>
<x-th-sort column="created_at">Tanggal</x-th-sort>
<th>Kategori</th>  {{-- kolom non-sortable tetap pakai <th> biasa --}}
```

- `column` harus **persis nama kolom database** (whitelist di repository).
- Tanpa atribut `column` → render teks polos (non-sortable).
- Link yang dihasilkan mempertahankan query string lain (search/filter) saat toggle sort.

### Backend (WAJIB ikut diterapkan)

Setiap kolom yang dirender `x-th-sort` **harus** masuk whitelist `$allowed` di repository
agar query `orderBy` aman (anti SQL injection via kolom).

- Repositori memakai helper `App\Support\Sort`:

```php
Sort::apply($query, ['name', 'created_at', 'status'], 'created_at', 'desc');
return $query->paginate($perPage)->withQueryString();
```

- Atau gunakan method `BaseRepository`:
  - `allSorted(array $allowed, string $default = 'id', string $defaultDirection = 'asc', array $relations = [])`
  - `paginateSorted(array $allowed, int $perPage = 15, array $relations = [], string $default = 'created_at', string $defaultDirection = 'desc')`

### Query String

- `?sort={kolom}&direction={asc|desc}`
- Direksi tidak valid / kolom di luar whitelist → fallback ke default repository.
- `direction` default saat kolom pertama diklik adalah `asc`; klik lagi → toggle ke `desc`.

### CSS

Class `.th-sort`, `.th-sort-icon`, `.th-sort.active`, `.th-sort.asc/.desc` didefinisikan di
`resources/css/app.css` (`@layer components`). Ikon panah otomatis berubah arah berdasarkan
class aktif. Jangan override dengan CSS inline.

## Layout

- Jangan pernah membuat halaman memiliki horizontal scrollbar.
- Seluruh halaman harus selalu berada di dalam viewport browser.
- Gunakan `max-w-full`, `w-full`, `min-w-0`, dan `overflow-hidden` pada parent container jika diperlukan.
- Semua konten harus responsif pada Mobile, Tablet, Laptop, dan Desktop.
- Hindari penggunaan fixed width seperti `w-[1400px]`, `min-w-screen`, atau ukuran lain yang menyebabkan halaman melebar.
- Gunakan Flexbox dan Grid secara responsif.

## Table

Setiap tabel WAJIB dibungkus seperti berikut:

```html
<div class="w-full overflow-x-auto rounded-2xl border border-slate-200">
    <table class="min-w-full whitespace-nowrap">
        ...
    </table>
</div>
```

Aturan tabel:

- Gunakan `overflow-x-auto` pada parent.
- Jangan gunakan `overflow-x-hidden` pada parent tabel.
- Gunakan `whitespace-nowrap` agar isi tabel tidak rusak.
- Gunakan `min-w-full` atau `min-w-max` sesuai kebutuhan.
- Header tabel (`thead`) tetap rapi dan mudah dibaca.
- Tambahkan efek hover pada baris.
- Gunakan sticky header jika jumlah data banyak.
- Gunakan zebra striping yang halus bila diperlukan.
- Pastikan tabel tetap dapat di-scroll secara horizontal di perangkat kecil.

## Form

- Input, select, dan textarea harus menggunakan `w-full`.
- Hindari fixed width.
- Gunakan Grid yang responsif, misalnya:

  - `grid-cols-1`
  - `md:grid-cols-2`
  - `xl:grid-cols-3`

## Card

- Semua card menggunakan `w-full`.
- Jangan membuat card melebihi parent.
- Gunakan `overflow-hidden` jika terdapat gambar atau konten panjang.

## Container

Gunakan struktur seperti berikut:

```html
<div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    ...
</div>
```

atau

```html
<div class="mx-auto w-full max-w-7xl px-6">
    ...
</div>
```

## Overflow Rules

- Tidak boleh ada elemen yang menyebabkan body memiliki horizontal scroll.
- Jika ada konten yang terlalu lebar, hanya konten tersebut yang boleh memiliki scrollbar horizontal.
- Body dan layout utama harus tetap memenuhi lebar layar tanpa overflow.

## Output

Setiap halaman yang memiliki tabel harus menerapkan aturan di atas secara otomatis tanpa perlu diminta kembali.

## CRITICAL RESPONSIVE RULES (WAJIB DIPATUHI)

Jangan pernah membuat seluruh halaman (body/html) memiliki horizontal scrollbar.

### Aturan Mutlak

* **Body tidak boleh memiliki overflow horizontal.**
* **Yang boleh memiliki horizontal scroll HANYA tabel.**
* Jika ada elemen yang menyebabkan body melebar, perbaiki layout tersebut.
* Jangan gunakan width yang melebihi parent.
* Jangan gunakan `w-screen` pada container utama.
* Jangan gunakan `100vw`.
* Jangan gunakan `min-w-screen`.
* Jangan gunakan `w-[1200px]`, `w-[1500px]`, atau fixed width lainnya.
* Jangan gunakan `absolute` yang keluar dari parent.
* Jangan gunakan `translate-x` yang menyebabkan overflow.
* Jangan gunakan margin negatif yang membuat layout melebar.

### Layout Utama

Gunakan struktur berikut:

```html
<body class="overflow-x-hidden bg-slate-50">
    <div class="min-h-screen w-full overflow-x-hidden">
        <main class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            ...
        </main>
    </div>
</body>
```

### Parent Table

SETIAP tabel WAJIB menggunakan struktur berikut:

```html
<div class="w-full max-w-full overflow-x-auto">
    <table class="min-w-max w-full">
        ...
    </table>
</div>
```

atau

```html
<div class="relative w-full overflow-x-auto">
    <table class="min-w-max">
        ...
    </table>
</div>
```

### Parent Card

```html
<div class="w-full overflow-hidden rounded-2xl bg-white shadow">
    <div class="overflow-x-auto">
        <table class="min-w-max">
            ...
        </table>
    </div>
</div>
```

### Semua Container

Semua parent WAJIB memiliki:

* `min-w-0`
* `max-w-full`
* `overflow-hidden`

Contoh:

```html
<div class="flex min-w-0">
<div class="w-full min-w-0">
<div class="max-w-full">
```

### DILARANG

* Jangan pernah membuat body dapat di-scroll horizontal.
* Jangan pernah membuat container lebih lebar dari viewport.
* Jangan pernah menggunakan width tetap untuk card, form, atau tabel.

### Jika tabel terlalu lebar

Yang harus bergeser adalah:

✅ Tabel

Bukan:

❌ Body

❌ Container

❌ Halaman

Output yang dihasilkan harus memastikan bahwa hanya area tabel yang memiliki scrollbar horizontal, sementara seluruh halaman tetap pas dengan lebar viewport tanpa horizontal scrolling.
