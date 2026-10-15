---
version: 1
slug: "resources-views-landing-blade-php"
primary_target: "resources/views/landing.blade.php"
related_targets: ["resources/views/payment.blade.php","resources/views/voucher.blade.php","resources/views/auth/login.blade.php","resources/views/admin/dashboard.blade.php","resources/views/admin/orders.blade.php","resources/views/admin/order.blade.php","resources/views/admin/voucher.blade.php","resources/views/admin/settings.blade.php","resources/views/livewire/orders.blade.php","resources/views/components/layout.blade.php","resources/css/app.css","resources/js/app.js"]
---

# Landing dan panel voucher

Customer: Persuade pada landing, Operate pada pembayaran/voucher. Admin/CS: Operate. Pengguna menyetujui proposal peningkatan seluruh halaman pada 14 Oktober 2026 dan tetap memilih langsung ke kode. Pertahankan seluruh kontrak transaksi, consent, hak akses, QR asli dan scope satu panel. Aset resmi belum tersedia; gunakan bentuk voucher HTML dan nama KGJ, tanpa foto/QR pembayaran rekaan.

## Direction contract

THESIS: Penawaran voucher terasa seperti layanan butik; panel operator mengutamakan keputusan pembayaran dan klaim, dengan ruang layar yang benar-benar dipakai.

OWN-WORLD: Hijau dalam, putih hangat, detail emas hemat, tipografi sistem yang tegas, bidang putih bertepi halus dan ikon garis konsisten. Data, harga dan status selalu nyata.

STORY: Customer memahami harga/potongan/tier, membayar, memeriksa status dan menunjukkan voucher. Operator segera menemukan verifikasi/scan, membaca data dan menyelesaikan satu tindakan.

FIRST VIEWPORT: Landing memakai penawaran besar di kiri dan voucher promosi hijau di kanan, CTA langsung terlihat. Admin memakai sidebar 232px dan konten fluid bergutter 32px, ringkasan padat serta akses scan/verifikasi. Pembayaran/voucher desktop dua kolom. Mobile satu kolom, menu drawer native, QR tetap utuh dan daftar pesanan ringkas.

FORM: Refinement menyeluruh dari world hijau dan font sistem yang sudah ada, dengan komposisi yang dipin proposal pengguna; bukan undian identitas baru. Catatan historis seed 55b2926b tidak memiliki artefak roll yang dapat diverifikasi dan bukan bukti undian pada tahap ini. Signature interaction: menu mobile fokus-terjaga, pengaturan membuka satu bagian, preview klaim mendahului nominal terkunci. Motion hanya feedback singkat; konten langsung terlihat.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Enhancement interaksi yang disetujui

14 Oktober 2026: pengguna memilih seluruh usulan critique resources-views, termasuk tier/lifecycle/perapihan kecil. Status pembayaran tetap tombol GET Periksa status dengan bantuan serta link yang dapat disalin; verifikasi uang manual. World/layout v0.5 dipertahankan.

Cakupan: validasi Indonesia dan field/fokus/disclosure; recovery419 ke status dari route tepercaya; dialog native merangkum input terbaru untuk verifikasi/klaim/transfer/aturan/penonaktifan; Batal/Escape tidak mengirim; toast nonblocking dan feedback clipboard/kamera/request; tier berikut dari snapshot WIB; used/expired copy; badge voucher mobile/resetfilter/heading; checkout unavailable tidak meminta data; metadata sekunder mobile di-disclose. Tidak ada integrasi order, auto-payment, retry finansial otomatis, SLA, dependensi atau raster produksi baru. Guardrail server/policy/consent/QRIS asli dipertahankan.
