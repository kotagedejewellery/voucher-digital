# Product — Voucher Digital Sat-Set

<!-- impeccable:product-schema 1 -->

14 Oktober 2026 · Baseline v0.3. Pengguna menetapkan **QRIS merchant existing berupa gambar, verifikasi manual admin/CS, dan Meta tracking dari server**. Ini menggantikan pemilihan gateway dan rancangan pengelolaan DP sebelumnya.

## Platform

web

## Stack

Laravel 13/PHP 8.4/Livewire 4/Blade/Tailwind 4/PostgreSQL 17/PHPUnit + Docker Compose. Satu aplikasi/database dan worker WA/Meta/CSV. [Stack](docs/TECH-STACK.md) memuat rincian. Domain/hosting/biaya/provisioning pengguna. Aplikasi dan Compose lokal sudah dibuat; hasil/batas aktual ada di docs/IMPLEMENTATION-HANDOFF.md.

## Users

Pelanggan membeli dari landing page dan membuka voucher WhatsApp tanpa akun. Admin/CS memakai satu panel dengan akun bernama per orang, satu jenis akses Admin/fungsi sama; cabang dipilih saat klaim. CS adalah operator, bukan portal tambahan.

## Product Purpose

Iklan/funnel, penjualan/penerbitan, tracking dan manajemen voucher, dengan pembayaran QRIS yang diverifikasi manual serta klaim sekali. Order cincin/DP berada pada sistem KGJ yang sudah ada, **tanpa integrasi**.

## Positioning

Voucher Rp100.000 untuk sepasang cincin minimal Rp8.000.000 sebelum potongan; lima cabang tanpa janji temu. Hari1–30 Rp800.000;31–60 Rp500.000;61–90 Rp300.000;91–365 Rp100.000;setelah hari 365 expired. Kalender WIB, pembayaran hari pertama; nilai terkunci saat klaim pada konfirmasi order/DP existing.

## Operating Context

Landing → pesanan voucher → gambar QRIS/nominal → customer bayar/konfirmasi → admin memeriksa transaksi merchant → approve → voucher/link WACO dan CAPI Purchase → cabang → CS scan/klaim → potongan diinput manual ke order lama. Gambar QRIS pembayaran dan token QR voucher memiliki fungsi berbeda.

Gambar QRIS diunggah lewat pengaturan; referensi penerimaan unik dan waktu bayar aktual dicatat saat verifikasi. paid_at berbeda dari verified_at; usia dan Purchase event_time memakai paid_at. Customer sudah bayar bukan bukti PAID. Tanpa callback/API bank/gateway baru.

## Capabilities and Constraints

- Satu pembayaran verified satu voucher; satu penerimaan merchant tidak untuk dua pesanan; approve/retry idempotent.
- DP/pelunasan/net dan pembatalan order dikelola sistem lama. Klaim cukup voucher, cabang, nomor order, CS/waktu, umur/potongan dan konfirmasi syarat manual.
- Scan tidak menghabiskan voucher; Klaim atomik dan sekali. CS memfinalisasi potongan sesudah hasil klaim sukses. Tanpa reaktivasi otomatis/backdate.
- Transfer sekali oleh admin setelah persetujuan WA pemilik; ganti token/kode, usia/policy tetap; riwayat terpisah.
- Aturan/tujuan pembayaran yang ditampilkan checkout melekat pada pesanan/voucher; perubahan untuk checkout baru.
- Satu panel: Ringkasan, Pesanan & Voucher, Pengaturan. CSV, kirim ulang, verifikasi uang, klaim dan transfer berada di panel sama.
- Meta: Pixel landing/checkout, CAPI Purchase Rp100.000 setelah approve dan VoucherRedeemed terpisah; data matching/consent customer dari checkout. Event gagal tidak menghilangkan transaksi.
- Nama/WA wajib; cabang/tanggal nikah opsional; consent layanan wajib dan marketing opsional. [PRD](docs/PRD.md) sumber kebijakan/acceptance criteria.

## Brand Commitments

Kotagede Jewellery, Voucher Digital Sat-Set. Terang/hijau dalam/aksen metal hangat/font sistem baseline aplikasi, bukan klaim panduan korporat. QRIS adalah gambar merchant asli dari pengguna; tidak digenerate/diubah secara AI. Logo/foto/data cabang nyata belum diberikan.

## Evidence on Hand

PDF 14 halaman di E:/AI Engineer/PT KotaGede Jewellery/Documents/Voucher Digital/Brief Sistem Voucher Sat-Set.pdf; rekening usaha/QRIS dimiliki KGJ menurut pengguna; metode manual disetujui. Gambar/riwayat QRIS nyata dan akses WACO/Meta belum tersedia. Aplikasi, test PostgreSQL dan screenshot fixture lokal sudah tersedia; lihat handoff. UAT operasional dan test integrasi nyata belum dilakukan. Target PDF MVP 30 Oktober 2026/soft launch 2 November belum diuji kelayakannya.

## Product Principles

1. Satu alur pelanggan dan satu admin menyelesaikan tugas voucher.
2. Keputusan finansial mengikuti uang masuk yang diperiksa operator, bukan klik pelanggan.
3. Perhitungan/akses/claim/transfer dan retry divalidasi server.
4. Janji checkout tetap berlaku pada voucher lama.
5. Order/DP tetap pada sistem existing; tambahan fitur berdasarkan kebutuhan nyata.

## Accessibility & Inclusion

Target WCAG 2.2 AA, label/fokus/keyboard/status teks, input manual alternatif kamera. QRIS ditampilkan utuh/unduhan asli, transaksi diuji pada aplikasi pembayaran/perangkat yang digunakan; jangan menjanjikan seluruh aplikasi bisa memindai gambar dari HP yang sama tanpa uji.
