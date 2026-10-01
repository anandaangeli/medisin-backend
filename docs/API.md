# Medisin API — Dokumentasi untuk Frontend

Base URL (dev): `http://localhost:8080/api`
Semua request: header `Accept: application/json`. Body JSON: `Content-Type: application/json`.
Endpoint selain `/login` wajib header `Authorization: Bearer <access_token>`.

Akun seeder: username `admin`, password `password`.

---

## 1. Format response

Sukses:
```json
{ "message": "....", "data": { ... } }
```
`message` tidak selalu ada pada GET. `data` bisa object atau array.

Error:

| HTTP | Kapan | Body |
|---|---|---|
| 401 | Token tidak ada / invalid / expired / sudah logout | `{ "message": "Unauthenticated." }` |
| 401 | Login: username/password salah | `{ "message": "Username atau password salah." }` |
| 404 | Pasien by No RM tidak ada | `{ "message": "Pasien dengan No Rekam Medik tersebut tidak ditemukan." }` |
| 422 | Validasi gagal | `{ "message": "<error pertama>", "errors": { "<field>": ["pesan", ...] } }` |
| 500 | Server error | `{ "message": "Server Error" }` |

Contoh 422:
```json
{
  "message": "NIK sudah terdaftar. (and 1 more error)",
  "errors": {
    "name": ["nama pasien minimal 3 karakter."],
    "nik": ["NIK sudah terdaftar."]
  }
}
```
Untuk menampilkan error per field di form, pakai `errors[field][0]`.

---

## 2. Enkripsi payload login (WAJIB)

`username` dan `password` **tidak boleh dikirim plaintext**. Backend menolak dengan 422.

- Algoritma: **AES-256-CBC**, padding PKCS7
- Key: `PAYLOAD_KEY` dari `.env` backend (tepat 32 karakter, dipakai sebagai UTF-8 bytes). Di frontend simpan sebagai `VITE_PAYLOAD_KEY`.
- IV: 16 byte random, **beda tiap field, tiap request**
- Nilai yang dikirim per field: `base64( iv ‖ ciphertext )`

Install: `npm i crypto-js`

```js
// src/utils/payloadCrypto.js
import CryptoJS from 'crypto-js'

const key = CryptoJS.enc.Utf8.parse(import.meta.env.VITE_PAYLOAD_KEY)

export function encryptPayload(plain) {
  const iv = CryptoJS.lib.WordArray.random(16)
  const c = CryptoJS.AES.encrypt(String(plain), key, {
    iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7
  })
  return CryptoJS.enc.Base64.stringify(iv.concat(c.ciphertext))
}
```

Pemakaian:
```js
await api.post('/login', {
  username: encryptPayload(form.username),
  password: encryptPayload(form.password)
})
```

Contoh nilai terenkripsi (selalu berbeda karena IV random):
```json
{
  "username": "KsqETvewLc6mmPzDZUTxlS8W4eZJCOAd0P0GGbXIco0=",
  "password": "YWG5zqxhrkU3MNvjyiKJQ8fOL09fUmApbTRi3OCK52s="
}
```

---

## 3. Auth

### POST /login
Body:
```json
{ "username": "<encrypted>", "password": "<encrypted>" }
```
200:
```json
{
  "message": "Login berhasil.",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9....",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Administrator",
      "username": "admin",
      "email": "admin@medisin.test",
      "email_verified_at": null,
      "created_at": "2026-10-01T16:15:41.000000Z",
      "updated_at": "2026-10-01T16:15:41.000000Z"
    }
  }
}
```
`expires_in` dalam detik (default 3600 = 1 jam). Simpan `access_token` di localStorage, kirim di header Authorization.

401 jika kredensial salah. 422 jika payload tidak terenkripsi / tidak valid:
```json
{
  "message": "Username wajib diisi atau payload tidak terenkripsi dengan benar. (and 1 more error)",
  "errors": {
    "username": ["Username wajib diisi atau payload tidak terenkripsi dengan benar."],
    "password": ["Password wajib diisi atau payload tidak terenkripsi dengan benar."]
  }
}
```

### GET /me
200: `{ "data": { ...user } }` (object user sama seperti di login).

### POST /refresh
Tukar token lama dengan token baru (token lama langsung tidak berlaku). Bisa dipakai sampai 14 hari setelah token pertama dibuat.
200: body sama seperti login, `message` = `"Token diperbarui."`.

### POST /logout
200: `{ "message": "Berhasil logout." }`. Token di-blacklist; request berikutnya dengan token itu → 401.

---

## 4. Pasien

### Object Patient
```json
{
  "id": 1,
  "no_rm": "RM0001",
  "name": "NURFAZA M.",
  "nik": "3214124123124120",
  "address": "Jl Wisata 2 No 18",
  "created_at": "2026-10-01T16:37:25.000000Z",
  "updated_at": "2026-10-01T16:37:25.000000Z",
  "visits_count": 2
}
```
`nik` sudah di-decrypt oleh backend, frontend menerima plaintext. `visits_count` = Jumlah Kunjungan untuk kolom tabel.

### GET /patients
List semua pasien urut `id` ASC (sama dengan urut No RM). Untuk kolom **NO** di tabel pakai index + 1.
200:
```json
{ "data": [ { ...Patient }, { ...Patient } ] }
```

### POST /patients  (modal Tambah Pasien)
`no_rm` **tidak dikirim**, di-generate backend (RM0001, RM0002, ...). Di modal, field No. Rekam Medik tampilkan readonly kosong / placeholder "otomatis".

Body:
```json
{ "name": "NURFAZA M.", "nik": "3214124123124120", "address": "Jl Wisata 2 No 18" }
```
Aturan validasi:

| Field | Aturan | Pesan |
|---|---|---|
| name | wajib, 3–100 karakter | `nama pasien wajib diisi.` / `nama pasien minimal 3 karakter.` |
| nik | wajib, tepat 16 digit angka, belum terdaftar | `NIK harus 16 digit angka.` / `NIK sudah terdaftar.` |
| address | wajib, 5–500 karakter | `alamat wajib diisi.` / `alamat minimal 5 karakter.` |

201:
```json
{
  "message": "Pasien berhasil didaftarkan.",
  "data": { ...Patient, "visits_count": 0 }
}
```
Setelah 201: tutup modal, tambahkan `data` ke list (atau refetch GET /patients).

### GET /patients/{no_rm}  (modal Buat Kunjungan → Cari Pasien)
`no_rm` case-insensitive (`rm0001` sama dengan `RM0001`).
200: `{ "data": { ...Patient } }` → isi field Nama Pasien, NIK, Alamat (readonly).
404: `{ "message": "Pasien dengan No Rekam Medik tersebut tidak ditemukan." }` → kosongkan field, tampilkan pesan.

Saran UX: panggil saat user tekan Enter atau blur di input No Rekam Medik, atau debounce 400ms.

---

## 5. Kunjungan

### POST /visits  (tombol BUAT KUNJUNGAN)
Body hanya No RM:
```json
{ "no_rm": "RM0001" }
```
Validasi `no_rm`: wajib, format `RM` + angka, harus ada di pasien.

| Pesan |
|---|
| `No Rekam Medik wajib diisi.` |
| `Format No Rekam Medik harus RM diikuti angka, contoh RM0001.` |
| `Pasien dengan No Rekam Medik tersebut tidak ditemukan.` |

201:
```json
{
  "message": "Kunjungan berhasil dibuat.",
  "data": {
    "visit": {
      "id": 4,
      "patient_id": 1,
      "visited_at": "2026-10-01T16:51:22.000000Z",
      "created_at": "2026-10-01T16:51:22.000000Z",
      "updated_at": "2026-10-01T16:51:22.000000Z"
    },
    "patient": { ...Patient, "visits_count": 2 }
  }
}
```
Setelah 201: update `visits_count` pasien di list dengan `data.patient.visits_count` (atau refetch).

### GET /visits
List kunjungan terbaru dulu, tiap item sudah include `patient` (tanpa `visits_count`).
```json
{
  "data": [
    {
      "id": 4, "patient_id": 1,
      "visited_at": "2026-10-01T16:51:22.000000Z",
      "created_at": "...", "updated_at": "...",
      "patient": { "id": 1, "no_rm": "RM0001", "name": "NURFAZA M.", "nik": "...", "address": "...", "created_at": "...", "updated_at": "..." }
    }
  ]
}
```
Tidak dipakai di desain saat ini, tersedia kalau perlu.

---

## 6. Setup Axios di Quasar (boot file)

```js
// src/boot/axios.js
import { boot } from 'quasar/wrappers'
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL, // http://localhost:8080/api
  headers: { Accept: 'application/json' }
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401 && !err.config.url.endsWith('/login')) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default boot(({ app }) => { app.config.globalProperties.$api = api })
export { api }
```

`.env` frontend:
```
VITE_API_URL=http://localhost:8080/api
VITE_PAYLOAD_KEY=<sama dengan PAYLOAD_KEY di backend/.env>
```

CORS: backend sudah mengizinkan semua origin untuk `api/*`, tidak perlu konfigurasi tambahan saat dev.

---

## 7. Alur layar → endpoint

| Layar / aksi | Endpoint |
|---|---|
| Login → tombol LOGIN | `POST /login` (payload terenkripsi) → simpan token → ke halaman pasien |
| Halaman pasien (load) | `GET /patients` → tabel NO, NO REKAM MEDIK, NAMA PASIEN, NIK, ALAMAT, JML KUNJUNGAN |
| Modal Tambah Pasien → SIMPAN | `POST /patients` → tutup modal, refresh tabel |
| Modal Buat Kunjungan → input No RM | `GET /patients/{no_rm}` → isi Nama, NIK, Alamat |
| Modal Buat Kunjungan → BUAT KUNJUNGAN | `POST /visits` → tutup modal, refresh tabel |
| Logout (jika ada) | `POST /logout` → hapus token → ke login |
