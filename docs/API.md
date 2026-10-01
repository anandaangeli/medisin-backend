# Medisin API — Endpoint

Base URL: `http://localhost:8080/api`
Header semua request: `Accept: application/json`, `Content-Type: application/json`.
Endpoint selain `POST /login` wajib header `Authorization: Bearer <access_token>`.

Akun seeder: username `admin`, password `password`.

## Format error

| HTTP | Body |
|---|---|
| 401 | `{ "message": "Unauthenticated." }` |
| 404 | `{ "message": "..." }` |
| 422 | `{ "message": "<error pertama>", "errors": { "<field>": ["pesan"] } }` |

---

## POST /login
`user_name` dan `password` dikirim terenkripsi AES-256-CBC dengan key `PAYLOAD_KEY`, format tiap field `base64(iv16 + ciphertext)`.

Request:
```json
{ "user_name": "<encrypted>", "password": "<encrypted>" }
```
Response 200:
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
401: `{ "message": "Username atau password salah." }`
422: payload tidak terenkripsi / kosong.

## GET /me
Response 200:
```json
{ "data": { "id": 1, "name": "Administrator", "username": "admin", "email": "admin@medisin.test", "email_verified_at": null, "created_at": "...", "updated_at": "..." } }
```

## POST /refresh
Response 200: sama seperti login, `message` = `"Token diperbarui."`. Token lama tidak berlaku lagi.

## POST /logout
Response 200:
```json
{ "message": "Berhasil logout." }
```

---

## GET /patients
Response 200:
```json
{
  "data": [
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
  ]
}
```

## POST /patients
`no_rm` di-generate otomatis (RM0001, RM0002, ...).

Request:
```json
{ "name": "NURFAZA M.", "nik": "3214124123124120", "address": "Jl Wisata 2 No 18" }
```
Validasi:

| Field | Aturan |
|---|---|
| name | wajib, 3–100 karakter |
| nik | wajib, 16 digit angka, belum terdaftar |
| address | wajib, 5–500 karakter |

Response 201:
```json
{
  "message": "Pasien berhasil didaftarkan.",
  "data": {
    "id": 1,
    "no_rm": "RM0001",
    "name": "NURFAZA M.",
    "nik": "3214124123124120",
    "address": "Jl Wisata 2 No 18",
    "created_at": "2026-10-01T16:37:25.000000Z",
    "updated_at": "2026-10-01T16:37:25.000000Z",
    "visits_count": 0
  }
}
```
Response 422 contoh:
```json
{
  "message": "NIK sudah terdaftar.",
  "errors": { "nik": ["NIK sudah terdaftar."] }
}
```

## GET /patients/{no_rm}
`no_rm` tidak case-sensitive.

Response 200:
```json
{
  "data": {
    "id": 1,
    "no_rm": "RM0001",
    "name": "NURFAZA M.",
    "nik": "3214124123124120",
    "address": "Jl Wisata 2 No 18",
    "created_at": "2026-10-01T16:37:25.000000Z",
    "updated_at": "2026-10-01T16:37:25.000000Z",
    "visits_count": 2
  }
}
```
Response 404:
```json
{ "message": "Pasien dengan No Rekam Medik tersebut tidak ditemukan." }
```

---

## POST /visits
Request:
```json
{ "no_rm": "RM0001" }
```
Validasi `no_rm`: wajib, format `RM` + angka, harus terdaftar.

Response 201:
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
    "patient": {
      "id": 1,
      "no_rm": "RM0001",
      "name": "NURFAZA M.",
      "nik": "3214124123124120",
      "address": "Jl Wisata 2 No 18",
      "created_at": "2026-10-01T16:37:25.000000Z",
      "updated_at": "2026-10-01T16:37:25.000000Z",
      "visits_count": 2
    }
  }
}
```
Response 422 contoh:
```json
{
  "message": "Pasien dengan No Rekam Medik tersebut tidak ditemukan.",
  "errors": { "no_rm": ["Pasien dengan No Rekam Medik tersebut tidak ditemukan."] }
}
```

## GET /visits
Terbaru dulu.

Response 200:
```json
{
  "data": [
    {
      "id": 4,
      "patient_id": 1,
      "visited_at": "2026-10-01T16:51:22.000000Z",
      "created_at": "2026-10-01T16:51:22.000000Z",
      "updated_at": "2026-10-01T16:51:22.000000Z",
      "patient": {
        "id": 1,
        "no_rm": "RM0001",
        "name": "NURFAZA M.",
        "nik": "3214124123124120",
        "address": "Jl Wisata 2 No 18",
        "created_at": "2026-10-01T16:37:25.000000Z",
        "updated_at": "2026-10-01T16:37:25.000000Z"
      }
    }
  ]
}
```
