# Organization Revision

## Tujuan

Menyamakan fitur API Organization dengan API terdahulu. Saat ini API organization hanya menyediakan satu endpoint publik untuk menampilkan struktur organisasi; fitur lanjutan (tree, statistik, pencarian, filter periode, level jabatan, dan jabatan yang sedang dijabat) belum tersedia di API.

## Existing API

`routes/api.php`:

```php
Route::get('/organization', [OrganizationController::class, 'index']);
```

- Publik (tanpa autentikasi).
- Mengembalikan semua `organization_members` aktif, diurutkan `sort_order`.

## Missing API

| Endpoint | Fungsi | Status |
|---|---|---|
| `GET /api/v1/organization/tree` | Struktur organisasi berbentuk tree | ❌ |
| `GET /api/v1/organization/stats` | Statistik pengurus (total, per level, aktif) | ❌ |
| `GET /api/v1/organization?search=...` | Pencarian anggota | ❌ |
| `GET /api/v1/organization?year=...` | Filter periode tahun jabatan | ❌ |
| `GET /api/v1/organization?level=...` | Filter level jabatan | ❌ |
| `GET /api/v1/organization/{id}` | Detail anggota | ❌ |

## Route

Route saat ini hanya `GET /organization`. Rute lanjutan (usulan):

```php
Route::get('/organization', [OrganizationController::class, 'index']);
Route::get('/organization/{id}', [OrganizationController::class, 'show']);
Route::get('/organization/stats', [OrganizationController::class, 'stats']);
```

Atau gunakan query param pada `index()` untuk `search`, `year`, `level`.

## Tree Structure

- Tabel `organization_members` tidak menyimpan `parent_id`/hierarki — struktur saat ini **flat** (posisi + `sort_order`).
- Untuk membangun tree perlu ditambahkan kolom `parent_id` (nullable, self-reference) atau level (lihat bagian Level).
- Seeder (`OrganizationMemberSeeder`) membuat 8 jabatan: Ketua Umum → Wakil Ketua → Sekretaris → Bendahara → Koordinator Event → Koordinator Membership → Koordinator Humas → Koordinator Media, dengan `sort_order` 1–8.

## Statistics

Belum ada. Usulan statistik yang bisa dihitung dari `organization_members`:

- Total pengurus aktif (`is_active = true`)
- Jumlah pengurus per level/jabatan
- Jumlah pengurus per periode (`period_start`–`period_end`)
- Jumlah pengurus yang terhubung ke participant (`participant_id` tidak null)

## Search

Belum ada. Usulan: filter `search` pada `name` / `position` / `role_description`:

```php
public function search(array $filters = [], int $perPage = 15)
{
    $query = $this->model->where('is_active', true);

    if (! empty($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('position', 'like', "%{$filters['search']}%")
                ->orWhere('role_description', 'like', "%{$filters['search']}%");
        });
    }

    return $query->orderBy('sort_order')->paginate($perPage)->withQueryString();
}
```

## Year

Belum ada. Filter berdasarkan `period_start` / `period_end`:

- `?year=2026` → anggota yang periodenya mencakup tahun 2026 (`period_start <= 2026-12-31 && period_end >= 2026-01-01`).

## Level

Belum ada. Tabel `organization_members` tidak memiliki kolom level. Usulan:

- Tambah kolom `level` (enum/int) atau gunakan `sort_order` sebagai penanda hierarki (1 = tertinggi).
- Filter `?level=X` akan menyaring berdasarkan level tersebut.

## Holder

- `organization_members.participant_id` (nullable, nullOnDelete) menautkan jabatan ke `Participant`.
- API dapat mengembalikan detail participant sebagai pemegang jabatan: `GET /api/v1/organization/{id}` → `{ ..., "holder": { participant data } }`.

## Controller

`app/Http/Controllers/API/OrganizationController.php` — saat ini hanya:

- `index()` → `OrganizationMemberRepository::findActive()` (is_active, orderBy sort_order)

## Response

`GET /api/v1/organization` → `200`:

```json
{
  "data": [
    {
      "id": 1,
      "participant_id": 3,
      "name": "Hendra Wijaya",
      "position": "Ketua Umum",
      "role_description": "Memimpin organisasi SH3",
      "sort_order": 1,
      "is_active": true,
      "period_start": "2026-01-01",
      "period_end": "2026-12-31"
    }
  ]
}
```

## Testing

- Test list publik (tanpa token) → `200`.
- Test urutan `sort_order` benar.
- Test hanya anggota aktif yang tampil.
- Test search, filter year, dan level (setelah diimplementasikan).

## Checklist

- [ ] Implementasikan `show()`, `stats()`, search, filter `year`/`level` di API.
- [ ] Putuskan apakah menambah kolom `parent_id`/`level` pada `organization_members` (migration baru).
- [ ] Gunakan Resource untuk response API.
- [ ] Tambahkan test feature.
