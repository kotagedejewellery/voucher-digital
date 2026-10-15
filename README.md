# Voucher Digital Sat-Set — Kotagede Jewellery

Sistem iklan/funnel, pembelian QRIS manual, penerbitan dan pengelolaan voucher. Customer memakai landing/halaman voucher tanpa akun; operator bernama memakai satu panel Admin/CS. Potongan diklaim setelah order/DP dikonfirmasi lalu dimasukkan manual ke sistem order existing.

Dokumen: [PRD](docs/PRD.md), [stack](docs/TECH-STACK.md), [arsitektur](docs/ARCHITECTURE.md), [desain](DESIGN.md), [hasil dan batas implementasi](docs/IMPLEMENTATION-HANDOFF.md). Baca [AGENTS.md](AGENTS.md) sebelum pekerjaan engineering.

## Preview lokal saat ini

- Customer: http://localhost:8088/
- Admin: http://localhost:8088/admin/login
- Akun lokal pengujian: lihat file privat `.local/admin.json`. Password dihasilkan acak; tidak ditampilkan di chat atau dipaketkan ke image. Akun ini hanya untuk review lokal; buat operator bernama dan nonaktifkan akun pengujian sebelum produksi.
- Database utama belum memuat QRIS/cabang/transaksi sintetis. Masukkan gambar QRIS merchant asli, identitas penerima, cabang dan nomor bantuan melalui Pengaturan. Checkout pembayaran baru tersedia setelah QRIS disimpan.
- Port 8089 hanya fixture QA terpisah; bukan tempat menerima pembayaran.

## Menjalankan checkout baru melalui Docker

Docker Desktop dengan Linux containers diperlukan. Di checkout baru, salin `.env.example` ke `.env`, isi `DB_PASSWORD` acak panjang dan atur `APP_URL` lokal. Jangan commit file environment.

```powershell
Copy-Item .env.example .env
docker compose build app
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Masukkan hasil key ke `APP_KEY` di `.env`; simpan key yang sama selama menggunakan database/storage yang sama. Kemudian:

```powershell
docker compose up -d db
docker compose run --rm app php artisan migrate --seed --force
docker compose up -d app worker scheduler
docker compose exec app php artisan voucher:admin
```

Perintah terakhir menanyakan nama/email dan password tersembunyi minimal 12 karakter. Seed hanya membuat aturan awal, tanpa akun/default password/QRIS/cabang fiktif. Migrasi dilakukan satu kali oleh operator setup, bukan otomatis dari setiap container. Image app yang sama dipakai worker dan scheduler; Node hanya tahap build.

Sesudah mengubah source, rebuild app dan jalankan `docker compose up -d app worker scheduler`. `docker compose stop` menghentikan sistem tanpa menghapus volume. Periksa `docker compose ps` untuk status proses/health.

## Navigasi panel

Ringkasan menampilkan antrean verifikasi dan pencarian/scan voucher. Pesanan & Voucher menyediakan pencarian, filter, pagination dan ekspor; daftar berbentuk tabel pada desktop dan kartu pada mobile. Pengaturan dibagi menjadi Pembayaran, Cabang, Operator, Aturan voucher dan Integrasi; buka bagian yang diperlukan. Desktop memakai sidebar tetap, mobile/tablet memakai tombol menu pada header.

## Alur operator

1. Cek riwayat transaksi merchant. Pada detail pesanan masukkan referensi penerimaan unik, waktu bayar aktual **WIB**, nominal pelanggan dan konfirmasi kecocokan uang masuk.
2. Approval menerbitkan voucher sekali. Customer dapat membuka voucher dari pesanan; operator dapat mengirim link melalui tombol WhatsApp manual.
3. Setelah order/DP di sistem existing dikonfirmasi, cari/scan voucher, pilih cabang/nomor order dan konfirmasi syarat. Scan hanya preview.
4. Klaim mengunci potongan. Salin nilainya ke order yang sama. Respons hilang: cek riwayat/hasil request, jangan mengaktifkan voucher kembali.
5. Transfer hanya sekali dengan persetujuan WA pemilik terdaftar. Link/kode lama dicabut; umur/policy tetap. Tandai kasus bantuan terbuka bila anonimisasi perlu ditunda.

## Feedback dan pemulihan

Verifikasi pembayaran, klaim, transfer, versi aturan baru dan penonaktifan operator meminta konfirmasi dengan ringkasan tindakan. Batal/Escape menutup dialog tanpa mengirim transaksi; tombol Batal mendapat fokus awal. Checkbox pemeriksaan dan validasi server tetap wajib. Pesan kesalahan berbahasa Indonesia ditampilkan dekat input, dengan fokus menuju isian yang perlu diperbaiki. Toast hanya untuk hasil ringan seperti salin kode dan simpan kontak; hasil transaksi tetap terlihat di halaman.

Customer dapat menyimpan link pesanan, menghubungi bantuan dan menekan **Periksa status terbaru** setelah konfirmasi pembayaran. Pembayaran tetap diverifikasi manual; jangan membayar ulang saat menunggu. Voucher aktif menunjukkan tanggal/nilai tier berikutnya dan rincian tier; voucher terpakai menjadi riwayat tanpa QR aktif. Jika sesi formulir berakhir, periksa status melalui tautan pemulihan sebelum mencoba tindakan lagi.

Jika pembaruan daftar pesanan gagal, hasil sebelumnya dinonaktifkan sementara. **Periksa kembali daftar** hanya memuat ulang daftar, tanpa mengulang transaksi atau ekspor. Pencarian/filter dapat direset. Scanner menampilkan status membuka kamera dan menyediakan input manual ketika kamera ditolak/tidak didukung. Bagian Pengaturan dipertahankan melalui fragment URL saat halaman dibuka ulang.

## Meta dan WhatsApp

Isi `META_PIXEL_ID`, `META_DATASET_ID`, `META_ACCESS_TOKEN` dan versi API di environment. Untuk uji, gunakan `META_TEST_EVENT_CODE` dari Events Manager, lalu recreate app/worker setelah perubahan environment. Rahasiakan access token.

ViewContent memakai consent pada landing; InitiateCheckout ketika submit customer. Purchase hanya server setelah approval, dari waktu bayar aktual dan matching checkout customer. VoucherRedeemed terpisah setelah klaim. Tidak ada Pixel pada URL bearer pesanan/voucher. Pengiriman gagal terlihat di detail dan dapat diulang dengan event ID/waktu yang sama; penerimaan event tidak menjamin atribusi semua transaksi.

**Adapter WACO sudah tersedia** berdasarkan [kontrak resmi](https://docs.waco.id/api-mulai); akun KGJ dan pengiriman nyata belum diuji. Endpoint tetap `https://waco.id/api/v1/kirim`, tanpa endpoint arbitrary. Isi environment:

```dotenv
WACO_ENABLED=true
WACO_TOKEN=
WACO_TEMPLATE=
WACO_SENDER=
```

`WACO_TOKEN` adalah API key privat dari Developer WACO. `WACO_SENDER` adalah nomor bisnis WACO yang sudah tersambung, format internasional tanpa `+`; `WACO_TEMPLATE` nama template APPROVED dengan tepat tiga variabel body, berurutan: **nama pemilik, kode voucher, URL voucher lengkap**. Gunakan template teks tanpa header media atau tombol URL dinamis. Contoh konten untuk diajukan melalui dashboard: “Halo {{1}}, voucher Kotagede Jewellery Anda sudah aktif. Kode voucher: {{2}}. Lihat voucher: {{3}}. Tunjukkan voucher saat order di cabang dikonfirmasi.” Persetujuan/kategori template mengikuti hasil Meta, bukan dijamin aplikasi.

Atur `APP_URL` ke domain **HTTPS** yang dapat dibuka customer, kemudian recreate app/worker/scheduler. Localhost bukan tujuan link untuk customer. Tanpa konfigurasi lengkap job WA menjadi SKIPPED, kontrol otomatis disabled dan tindakan manual tetap tersedia. Setelah konfigurasi, approval/transfer mengantrekan link pemilik saat ini; operator dapat mengirim ulang dari halaman voucher setelah memeriksa riwayat WACO. Token/code/revision lama tidak dikirim ulang. Klik ulang dengan request key sama tidak membuat antrean baru.

HTTP 200 dengan `status=terkirim` menjadi SENT; `message_id`, bila tersedia, disimpan terenkripsi pada delivery. Ini belum membuktikan delivered/read; webhook belum termasuk implementasi. Retry otomatis hanya untuk HTTP429, jeda60/120/600detik, maksimal4percobaan. Penolakan yang jelas menjadi FAILED. Timeout, respons tidak dikenal atau job terputus setelah mulai mengirim menjadi UNCERTAIN: **periksa riwayat WACO dahulu**, lalu antrekan ulang bila diperlukan. Provider tidak mendokumentasikan idempotency outgoing, sehingga pengiriman tepat sekali tidak dijanjikan. [Riset dan batas kontrak](docs/research/WACO-API.md).

Sesuai pilihan pengguna, pengujian tetap pada pembayaran/klaim/hak akses; tidak ditambahkan pengujian HTTP tiruan WACO. Uji template dan penerimaan nyata masih diperlukan sebelum mengaktifkan pengiriman untuk customer.

## Verifikasi engineering

```powershell
docker compose --profile test up -d test-db
docker compose build test
docker compose run --rm test php vendor/bin/phpunit
```

Test memakai PostgreSQL terpisah `voucher_test` dengan storage ephemeral. Fokus: pembayaran, claim dan akses; termasuk fork dua koneksi database. Jangan menunjuk test ke database utama.

Untuk review UI sintetis gunakan `compose.qa.yaml` dengan **kedua** profile test/qa. `scripts/seed-ui-review.php` menolak database selain voucher_test dan mereset database tersebut. `scripts/inspect-ui.mjs` menggunakan Playwright + Edge yang sudah terpasang; credentials privat lokal dibaca dari `.local/admin.json`. Fixture gambar hanya bertuliskan GAMBAR UJI BUKAN QRIS. Tidak ada kiriman eksternal nyata.

## Deployment milik pengguna

Domain, hosting dan HTTPS mengikuti pilihan Anda. Gunakan environment production, APP_DEBUG=false, URL HTTPS dan SESSION_SECURE_COOKIE=true; batasi akses langsung app ke ingress yang Anda atur. Database tidak dipublikasikan. Atur trusted proxy sesuai IP/CIDR ingress bila diperlukan; jangan percaya header client secara menyeluruh.

Untuk VPS yang memakai Traefik, gunakan [panduan deployment](docs/DEPLOYMENT-VPS.md), [`.env.production.example`](.env.production.example), dan [`compose.production.yml`](compose.production.yml). Image produksi yang sudah ada menjalankan Apache, sehingga tidak perlu menambah Nginx atau bind mount source pada server.

Deploy otomatis setelah setup awal menggunakan [workflow GitHub Actions](.github/workflows/deploy-vps.yml). Lihat secret yang diperlukan dan urutan setup di panduan deployment.

Cadangkan PostgreSQL, volume storage dan APP_KEY bersama. Jangan mengganti key atau menghapus volume pada sistem berisi data. QRIS dan file ekspor disimpan di storage privat; hanya asset merchant dilayani publik melalui route khusus. Cache privat/halaman voucher/admin tidak boleh diaktifkan di CDN. Jangan mencatat URL bearer pada log ingress.

Sebelum melayani customer, uji QRIS asli/merchant/nominal, Meta Test Events dan operator nyata. Hasil lokal serta hal belum diperiksa tercatat di [handoff](docs/IMPLEMENTATION-HANDOFF.md).
