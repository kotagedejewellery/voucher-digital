# Kontrak API WhatsApp WACO

Diperiksa 14 Oktober 2026. Penelitian memakai sumber resmi; tidak memakai credentials atau mengirim pesan. Catatan ini menjadi dasar adapter, bukan bukti integrasi akun KGJ sudah aktif.

## Kontrak pengiriman

Base `https://waco.id`, autentikasi `Authorization: Bearer waco_…`, body JSON. `POST /api/v1/kirim` mengirim template; `GET /api/v1/template` menampilkan template APPROVED. `nomor` dan `template` wajib; `isi` mengisi variabel berurutan dengan jumlah tepat; `nama` opsional. `dari` memilih nomor pengirim; tanpa nilai dipakai nomor pertama, salah nomor menghasilkan 400. Panduan meminta E.164 tanpa `+`. [Panduan API](https://docs.waco.id/api-mulai).

Spesifikasi menerima `08…`, `62…`, dan `+62…`; adapter sebaiknya tetap memakai nomor internasional ternormalisasi. HTTP 200 dengan `status: "terkirim"` berarti diterima WhatsApp. `message_id` dapat tidak muncul meskipun pesan terkirim; `percakapan_id` bergantung kotak masuk. Field `ok` bukan kontrak pengiriman. [OpenAPI, POST /api/v1/kirim dan HasilKirim](https://waco.id/openapi.json).

## Variabel template

Body memakai `isi: ["nilai1", "nilai2", …]`. Header gambar memakai `gambar_url`; PDF memakai `dokumen_url`, opsional `dokumen_nama`. Gambar JPEG/PNG publik HTTPS ≤5 MB. Tidak ada parameter header teks, bahasa pengiriman, atau tombol dinamis pada skema `/kirim`. Pembuatan template menyediakan tombol balasan cepat dan tombol URL **statis**. [OpenAPI, TemplateBaru dan POST /api/v1/kirim](https://waco.id/openapi.json).

**Usulan implementasi KGJ:** satu template transaksional tanpa header/tombol dinamis, tiga variabel: nama pemilik, kode voucher, URL voucher lengkap. Contoh konten untuk diajukan lewat dashboard: “Halo {{1}}, voucher Kotagede Jewellery Anda sudah aktif. Kode voucher: {{2}}. Lihat voucher: {{3}}. Tunjukkan voucher saat order di cabang dikonfirmasi.” Ini usulan konten, belum template APPROVED. Jangan membuat URL bearer publik sebagai gambar/media, atau menganggap nama template usulan sudah terdaftar.

## Galat dan pengulangan

| HTTP | Makna kontrak |
| --- | --- |
| 400 | Input/jumlah variabel/pengirim salah |
| 401 | Kunci tidak dikenali |
| 403 | Layanan ditangguhkan |
| 404 | Template tidak ditemukan/belum disetujui |
| 409 | WhatsApp belum tersambung atau template belum disetujui, menurut panduan |
| 429 | Batas 120 permintaan/menit/kunci |
| 502 | WhatsApp/Meta menolak pengiriman |

Skema galat menyediakan `galat`; panduan menyebut `detail` untuk 502 dan mencantumkan 409 yang tidak tercantum pada respons `/kirim` OpenAPI. Adapter perlu menampung perbedaan itu tanpa menyimpan payload sensitif mentah. [OpenAPI](https://waco.id/openapi.json), [panduan API](https://docs.waco.id/api-mulai).

**Rekomendasi keselamatan:** retry otomatis hanya setelah penolakan rate-limit 429, dengan jeda minimal 60 detik dan jumlah percobaan terbatas. Tidak ditemukan kontrak idempotency key, deduplikasi outgoing, atau jaminan `Retry-After`. Timeout setelah POST, respons rusak, dan 5xx tidak dikenal harus diberi status belum pasti dan diperiksa admin sebelum kirim ulang; pesan mungkin sudah terkirim. 502 tidak boleh diperlakukan sebagai sukses ataupun selalu sebagai galat sementara. Simpan ID internal antrean untuk mencegah pekerjaan lokal yang sama berjalan dua kali; itu tidak memberi jaminan tepat satu kali di provider.

## Status pesan dan webhook

Webhook `message.status` membawa `pesan_id` yang cocok dengan `message_id`, dengan status `sent`, `delivered`, `read`, atau `failed`. HTTP sukses pengiriman belum membuktikan delivered/read. Signature `X-WACO-Signature: sha256=…` memakai HMAC-SHA256 body mentah dan secret webhook. Endpoint HTTPS harus memverifikasi signature, membalas 2xx cepat, dan idempoten karena event dapat diulang. Retry webhook provider berbeda dari retry POST pengiriman aplikasi. [Panduan webhook](https://docs.waco.id/api-webhook).

## Yang masih perlu tersedia

- Credentials environment, nomor bisnis WACO tersambung, dan nama template APPROVED dengan tepat tiga variabel.
- Domain HTTPS final agar URL voucher yang dikirim dapat dibuka pelanggan; localhost tidak memadai.
- Bila perlu delivered/read di aplikasi: secret webhook, URL callback, dan pemetaan status. Pengiriman dasar dapat berjalan tanpa mengklaim status tersebut.
- Konfirmasi WACO untuk jaminan idempotency/dynamic URL button bila kelak dibutuhkan; jangan menebak kontraknya.

OpenAPI berhasil dibaca menggunakan Python `urllib.request` tanpa autentikasi; browser-tool menolak URL JSON dan PowerShell `Invoke-RestMethod` gagal koneksi TLS. Tidak ada pengujian akun nyata, approval template, rate-limit nyata, atau penerimaan pesan pelanggan dalam penelitian ini.
