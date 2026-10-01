# Medisin API — SINTASI Backend Test (Laravel 12 + JWT + PostgreSQL)

REST API untuk Login, Pendaftaran Pasien, dan Buat Kunjungan.

## Stack
- Laravel 12, PHP 8.2
- PostgreSQL
- JWT: `php-open-source-saver/jwt-auth`
- Enkripsi payload login: AES-256-CBC (shared key `PAYLOAD_KEY`)
- Enkripsi data at rest: kolom `patients.nik` dan `users.email` memakai Laravel `encrypted` cast (`APP_KEY`)

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```
Isi di `.env`:
- `DB_*` → koneksi PostgreSQL (buat database kosong dulu, mis. `sintasi_db`)
- `PAYLOAD_KEY` → string tepat **32 karakter** (contoh: `openssl rand -hex 16`). Key ini juga dipakai frontend/Postman untuk mengenkripsi payload login.

```bash
php artisan migrate --seed   # tabel + user login
php artisan serve --port=8080
```

User seeder: username `admin`, password `password`.

## Endpoint
Base URL: `http://localhost:8080/api`. Semua request pakai header `Accept: application/json`.

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/login` | - | Body `{ "username": "<enc>", "password": "<enc>" }` → JWT |
| GET | `/me` | Bearer | User yang login |
| POST | `/refresh` | Bearer | Refresh token |
| POST | `/logout` | Bearer | Invalidate token |
| GET | `/patients` | Bearer | List pasien + `visits_count` |
| POST | `/patients` | Bearer | Body `{ name, nik(16 digit), address }`. `no_rm` di-generate otomatis `RM0001`, `RM0002`, ... |
| GET | `/patients/{no_rm}` | Bearer | Cari pasien by No Rekam Medik |
| GET | `/visits` | Bearer | List kunjungan |
| POST | `/visits` | Bearer | Body `{ no_rm }` → kunjungan baru, balikan pasien + `visits_count` |

Response sukses: `{ "message": "...", "data": ... }`. Validasi gagal: HTTP 422 `{ "message", "errors": { field: [...] } }`.

## Enkripsi payload login
`username` dan `password` dikirim terenkripsi, bukan plaintext.

- Algoritma: AES-256-CBC, PKCS7, key = `PAYLOAD_KEY` (32 byte, UTF-8)
- IV: 16 byte random per field
- Format tiap field: `base64( iv ‖ ciphertext )`

Contoh (JavaScript / crypto-js, dipakai Postman dan frontend):
```js
const key = CryptoJS.enc.Utf8.parse(PAYLOAD_KEY);
function enc(plain) {
  const iv = CryptoJS.lib.WordArray.random(16);
  const c = CryptoJS.AES.encrypt(plain, key, { iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 });
  return CryptoJS.enc.Base64.stringify(iv.concat(c.ciphertext));
}
// body: { username: enc('admin'), password: enc('password') }
```
Backend men-decrypt di `App\Support\PayloadCrypto` sebelum validasi (`LoginRequest`). Payload plaintext ditolak dengan 422.

## Validasi (negative test)
- Pasien: `name` 3–100 karakter, `nik` tepat 16 digit dan belum terdaftar (dicek lewat `nik_hash` = sha256, karena kolom `nik` terenkripsi), `address` 5–500 karakter.
- Kunjungan: `no_rm` wajib, format `RM` + angka, harus ada di tabel pasien.
- Login: payload tidak terenkripsi / tidak valid → 422; kredensial salah → 401; tanpa token → 401.

## Postman
File ada di folder [`postman/`](postman/):
- `Medisin.postman_collection.json` — collection (Auth, Pasien, Kunjungan, Negative Test). Login punya Pre-request Script yang mengenkripsi username/password dan Test script yang menyimpan token otomatis.
- `Medisin.postman_environment.json` — environment (`base_url`, `payload_key`, `username`, `password`, `token`, `no_rm`).

Cara pakai: Import keduanya → pilih environment "Medisin Local" → isi `payload_key` dengan nilai `PAYLOAD_KEY` dari `.env` backend → jalankan **Auth > Login** → request lain otomatis memakai Bearer token.

## Test otomatis
```bash
php artisan test
```
`tests/Feature/ApiFlowTest.php` mencakup alur login terenkripsi → tambah pasien → kunjungan → jumlah kunjungan, plus negative case dan pengecekan data terenkripsi di DB.
