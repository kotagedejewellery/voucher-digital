# Tech Stack — Voucher Digital Sat-Set

Versi 0.3 · 14 Oktober 2026 · QRIS gambar merchant existing/verifikasi manual dan Meta CAPI ditetapkan pengguna. Docker wajib; domain/hosting pengguna.

Acuan: [PRD](PRD.md), [arsitektur](ARCHITECTURE.md), [desain](../DESIGN.md). Satu aplikasi untuk voucher/funnel, dengan order cincin/DP di sistem existing tanpa integrasi.

## 1. Stack final

| Lapisan | Baseline |
|---|---|
| Backend | Laravel 13 + PHP 8.4; aturan voucher, approval manual dan admin |
| Publik | Blade untuk landing, checkout/QRIS/status dan halaman voucher |
| Admin | Livewire 4; satu panel Ringkasan, Pesanan & Voucher, Pengaturan |
| CSS/build | Tailwind 4 + Vite; token DESIGN dan aset hasil build |
| Database | PostgreSQL 17; transaksi, row lock dan unique constraint |
| Auth | Session Laravel/Fortify; akun Admin bernama/aktif, tanpa registrasi publik |
| Background | Database queue Laravel, worker dan scheduler untuk WA/Meta/CSV/cleanup |
| Test | PHPUnit; integrasi/concurrency pada PostgreSQL nyata; alat browser sesuai CI |
| Runtime | Docker + Docker Compose |
| Pembayaran | Gambar QRIS merchant KGJ; uang masuk dikonfirmasi manual; tanpa SDK/gateway/API bank |
| WhatsApp | WACO untuk link awal/ulang; kontrak API/template memerlukan akses nyata |
| Meta | Pixel ViewContent/InitiateCheckout, CAPI Purchase dan VoucherRedeemed sesuai consent |

Major yang sudah dipilih dipertahankan; patch/image/lockfile dipin setelah pemeriksaan kompatibilitas/advisori saat implementasi, bukan tag latest. Gunakan komponen sederhana dari stack yang sama tanpa lisensi UI berbayar/dependency tambahan yang belum diperlukan.

Sumber: [Laravel starter kits](https://laravel.com/framework/docs/13.x/starter-kits), [releases](https://laravel.com/framework/docs/13.x/releases), [PHP support](https://www.php.net/supported-versions.php), [PostgreSQL versioning](https://www.postgresql.org/support/versioning/), [manifest styling](https://github.com/laravel/livewire-starter-kit/blob/main/package.json).

## 2. QRIS dan aset

Admin mengunggah gambar QRIS asli melalui pengaturan. Validasi tipe/ukuran file dan konten gambar, simpan dengan nama aman/versioned; hanya admin aktif boleh mengubahnya. Format awal PNG/JPEG, tanpa SVG atau URL eksternal yang diambil server. Gambar asli/preview lossless ditampilkan utuh, tanpa crop/payload modification/AI generation.

Tujuan merchant dan versi gambar dicatat pada checkout agar pergantian QRIS tidak mengubah konteks pesanan lama. Asset QRIS merchant boleh dilayani publik dengan nama content-hash/cache invalidation; ini bukan halaman voucher pelanggan yang berisi bearer token.

Tidak menganggap ada callback/verifikasi otomatis bank. Approve menggunakan referensi penerimaan unik, waktu pembayaran aktual dan konfirmasi nominal Rp100.000 dari sumber merchant. Tidak memasang plugin scraper mutasi atau membuat nominal kode unik.

## 3. Meta dan WACO

Pembayaran dan meta tracking terpisah: approve menyimpan paid/voucher/queue, worker mengirim Purchase IDR 100000 dari server walau customer sudah menutup browser. Data matching/campaign/consent berasal dari checkout pelanggan; bukan request/browser admin.

Simpan event_id persisten, event_time waktu bayar aktual, consent, serta context klik/browser yang benar saat checkout. Phone dinormalisasi/hash sesuai ketentuan Meta; fbc/fbp tidak diperlakukan sebagai nomor telepon atau direkayasa. HTTP client Laravel cukup untuk interface CAPI; SDK tambahan hanya bila ada kebutuhan yang terbukti. Versi Graph API dan payload diverifikasi ketika implementasi, secrets runtime Docker.

Baseline Purchase hanya CAPI; bila kelak ditambah Pixel Purchase, event_name/event_id sama. VoucherRedeemed adalah custom event terpisah sesudah klaim di cabang, bukan revenue/Purchase cincin lunas. Set action_source sesuai asal peristiwa; data customer/consent aktual tetap diperiksa, khususnya transfer.

Meta menyediakan client server-event dan parameter builder yang mendokumentasikan click/browser context serta hashing data customer. [Business SDK resmi](https://github.com/facebook/facebook-nodejs-business-sdk#conversions-api), [parameter builder PHP](https://github.com/facebook/capi-param-builder/blob/main/php/README.md). Halaman developer utama pada penelusuran sebelumnya dibatasi HTTP429; gunakan sumber resmi dan Test Events untuk memvalidasi versi/payload saat implementasi.

WACO menerima link setelah voucher committed; delivery gagal/ulang tampak pada panel sama. Template kategori mengikuti isi dan approval provider aktual. Kegagalan WA/Meta tidak membatalkan paid/voucher; retry tidak mengubah event_id/paid_at atau menerbitkan voucher baru.

## 4. Docker Compose

Satu image aplikasi PHP 8.4/Apache; image sama dipakai worker/scheduler; PostgreSQL 17 memiliki named volume.

| Container | Tugas |
|---|---|
| app | Laravel publik/admin, static assets hasil Composer/Vite build |
| worker | Queue WA/Meta/CSV; tidak menjalankan Apache |
| scheduler | Cleanup/pekerjaan terjadwal terbatas; tidak memverifikasi uang otomatis |
| db | PostgreSQL 17, volume persisten dan healthcheck |

Multi-stage build memisahkan Composer/Vite build dari runtime; Node hanya builder. app/worker/scheduler menunggu healthcheck DB; migrasi sekali sebagai langkah deploy, tidak berlomba di tiap proses. Secrets/APP_KEY runtime, DB tidak publik; asset QRIS dan storage privat memiliki volume/storage yang tetap saat container diganti. [Multi-stage](https://docs.docker.com/build/building/multi-stage/), [Compose](https://docs.docker.com/compose/intro/compose-application-model/), [startup readiness](https://docs.docker.com/compose/how-tos/startup-order/)

Domain/hosting/ingress/HTTPS/port mapping/biaya milik pengguna. Implementasi menyediakan Dockerfile/compose.yaml, environment example tanpa rahasia, build/migration/start/test/backup guidance. Tidak provision DNS/server atau deploy sekarang. Belum ada konfigurasi/image/container yang dibuat.

## 5. Konsistensi, performa dan pengujian

Jobs database disimpan atomik pada koneksi PostgreSQL yang sama dengan approval/claim, tanpa gap commit–enqueue; uji rollback/crash pada konfigurasi Laravel yang dipilih. Tidak menambah Redis/broker/outbox custom. [Laravel queues](https://laravel.com/framework/docs/13.x/queues), [scheduler](https://laravel.com/framework/docs/13.x/scheduling)

Uang integer rupiah, waktu timestamptz, tanggal kalender Asia/Jakarta. PostgreSQL adalah otoritas claim; row lock/unique constraint, bukan cache. [Locks](https://www.postgresql.org/docs/17/explicit-locking.html), [constraints](https://www.postgresql.org/docs/17/ddl-constraints.html).

Seluruh aturan AGENTS berlaku: cache/invalidation aset/cabang; server pagination/proyeksi/index sesuai access pattern/hindari N+1; scanner lazy/code split, media sesuai ukuran, request dedup; memoization berdasarkan profiling; CSV background. Gambar QRIS/QR voucher menjaga kualitas scannable, tanpa kompresi lossy. Voucher/admin/status checkout/CSV private,no-store; token/PII tidak masuk analytics/log/referrer.

Test implementasi mengikuti PRD: customer tidak bisa approve, referensi pembayaran ganda, dua approve/claim, rollback queue, paid_at≠verified_at, tier WIB, transfer/token lama, consent/matching Meta, retry WA/CAPI/CSV, file upload dan Compose/health/volume/restore. [Laravel testing](https://laravel.com/framework/docs/13.x/testing)

Belum tersedia/teruji: gambar QRIS dan referensi riwayat nyata, data cabang/kontak, WACO/template, Pixel/Dataset/CAPI credential, host/domain, aplikasi/SDK/payload/live payment. Metode pembayaran sudah final; pilihan gateway lain bukan dependency atau blocker.

## Implementasi lokal aktual

Laravel 13.36.0/PHP 8.4.26, Livewire 4.4.7 untuk daftar/filter/ekspor admin dan Blade untuk layar lain, Tailwind 4.3.3/Vite 8.3.4, PostgreSQL 17.11, PHPUnit 12.5.39. composer.lock/package-lock.json mengunci dependensi; Playwright 1.64.0/Edge dipakai untuk review lokal. Login memakai session/guard Laravel native tanpa registrasi publik; Fortify tidak diperlukan untuk scope login operator ini.

Compose dan multi-stage Dockerfile sudah tersedia. Waktu disimpan UTC/timestamptz; tanggal bisnis/tampilan/input manual secara eksplisit Asia/Jakarta. Cache cabang menyimpan array sederhana karena deserialisasi kelas pada database cache Laravel dibatasi; token/status/hak akses tidak dibaca dari cache.

CAPI memakai Graph API v26.0, sesuai [ApiConfig SDK resmi Meta](https://github.com/facebook/facebook-php-business-sdk/blob/main/src/FacebookAds/ApiConfig.php). [Versi resmi Composer](https://getcomposer.org/versions) diperiksa untuk build. Developer docs utama Meta masih gagal diakses; Test Events aktual tetap diperlukan. Adapter WACO memakai HTTP client Laravel yang sudah tersedia, Bearer token dan endpoint resmi `/api/v1/kirim`; template tiga variabel body dan sender eksplisit diatur melalui environment, bersama URL HTTPS. Tidak ada SDK/provider abstraction tambahan. Kontrak sudah diteliti; pengiriman akun nyata belum diuji. [Riset WACO](research/WACO-API.md), [setup](../README.md), [handoff](IMPLEMENTATION-HANDOFF.md).
