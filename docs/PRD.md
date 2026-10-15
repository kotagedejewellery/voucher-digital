# PRD — Sistem Voucher Digital Sat-Set

Versi 0.3 · 14 Oktober 2026 · Metode pembayaran ditetapkan pengguna: **gambar QRIS KGJ + verifikasi manual admin/CS + tracking Meta dari server**.

## 1. Acuan dan keputusan final

Sumber awal: E:/AI Engineer/PT KotaGede Jewellery/Documents/Voucher Digital/Brief Sistem Voucher Sat-Set.pdf, Tim BMS, Oktober 2026. Klarifikasi/persetujuan pengguna dalam percakapan menjadi acuan ketika berbeda dari PDF atau rekomendasi v0.2.

Tujuan aplikasi adalah funnel iklan, penjualan/penerbitan, tracking dan manajemen voucher. Order cincin dan kebijakan/pembayaran DP berada pada sistem KGJ yang sudah ada, **tanpa integrasi**. Klaim dilakukan saat order/DP dikonfirmasi di sistem tersebut; CS memasukkan potongan secara manual sesudah klaim sukses.

Metode QRIS sudah dipilih, bukan proposal atau gateway yang menunggu pemilihan. Dokumen ini tidak mengklaim implementasi, scan gambar, transaksi nyata, WACO atau Meta sudah diuji. [Produk](../PRODUCT.md), [stack](TECH-STACK.md), [arsitektur](ARCHITECTURE.md), [desain](../DESIGN.md), dan [riset gateway](research/PAYMENT-OPTIONS.md) menjadi riwayat keputusan.

## 2. Pengguna dan scope MVP

Pelanggan tanpa akun/login. Admin/CS menggunakan satu panel dengan satu jenis akses Admin, akun bernama per orang, fungsi sama. CS adalah operator yang menjalankan tugas, bukan portal/role baru. Cabang dipilih saat klaim.

| Area | Fitur |
|---|---|
| Pelanggan | Landing/form, pesanan voucher, gambar QRIS/nominal, konfirmasi sudah bayar, status verifikasi, halaman voucher, QR/kode, tier/deadline, cabang/S&K/bantuan |
| Admin — Ringkasan | Checkout, menunggu verifikasi, voucher paid/terpakai, pendapatan voucher dan konversi paid→claimed |
| Admin — Pesanan & Voucher | Cari/filter/detail, verifikasi pembayaran, alasan koreksi, kirim ulang WA, scan/klaim, transfer sekali, CSV |
| Admin — Pengaturan | Unggah/ganti gambar QRIS asli, identitas merchant, cabang/kontak bantuan, versi aturan voucher |
| Integrasi | WACO untuk link transaksi; Meta Pixel dan CAPI; tidak ada payment gateway atau API bank/order |
| Runtime | Laravel/Livewire/PostgreSQL melalui Docker Compose; domain/hosting pengguna |

MVP tracking: ViewContent/InitiateCheckout, Purchase setelah pembayaran terverifikasi dan event terpisah VoucherRedeemed setelah klaim. Reminder otomatis, grafik, Wallet, Sheet dan POS/pembukuan bukan scope saat ini. Semua validasi dan klaim wajib online.

## 3. Pembayaran QRIS

Admin mengunggah **gambar QRIS merchant KGJ yang benar dan dapat digunakan untuk pembayaran voucher**. Gambar ditampilkan sebagaimana aslinya, tanpa membangun/mengubah payload QRIS atau menganggap gambar menghasilkan callback bank. Sistem meminta Rp100.000; bila nominal belum termuat pada QRIS, pelanggan memasukkannya pada aplikasi pembayaran. Kecocokan gambar/nominal/merchant dan pemakaian berulang diuji memakai aset nyata sebelum rilis.

Gambar QRIS pembayaran berbeda dari QR voucher: yang pertama adalah aset merchant, yang kedua token unik untuk klaim voucher. Pengaturan menyimpan versi gambar/identitas tujuan; pesanan menyimpan snapshot harga, aturan dan tujuan QRIS saat dibuat. Perubahan gambar tidak menghapus konteks pesanan yang sudah berjalan.

Alur:
1. Pelanggan mengisi nama/WA wajib; cabang/tanggal nikah opsional; persetujuan layanan wajib dan marketing opsional.
2. Pesanan voucher dibuat dengan token akses acak, harga Rp100.000 dan snapshot aturan. Halaman menampilkan nominal, gambar QRIS, unduh gambar dan bantuan.
3. Sesudah membayar, pelanggan menekan **Saya sudah bayar**; referensi/keterangan pembayaran dapat diberikan sebagai bantuan pencocokan. Status menjadi **Menunggu verifikasi**. Tombol ini tidak menetapkan PAID.
4. Admin/CS memeriksa uang diterima pada riwayat transaksi merchant/mutasi yang sesuai. Referensi pelanggan/screenshot saja tidak menjadi bukti final.
5. Admin memasukkan referensi transaksi merchant yang unik dan waktu bayar aktual dari sumber tersebut, lalu mengonfirmasi transaksi Rp100.000 cocok dengan pesanan.
6. Server menyimpan persetujuan, paid_at, voucher dan job WA/Meta atomik. Voucher aktif, link dikirim, Purchase dikirim dari server sesuai consent.

Nominal yang dicocokkan adalah nilai pembayaran pelanggan pada detail transaksi merchant, bukan mengasumsikan satu transfer settlement bank sama dengan satu checkout. Satu referensi penerimaan pada merchant yang sama hanya dapat digunakan untuk satu pesanan. Tidak ada pembentukan nominal kode unik atau aktivasi otomatis dari gambar.

Status pesanan: MENUNGGU_PEMBAYARAN → MENUNGGU_VERIFIKASI → PAID. Bila belum cocok, admin mencatat **PERLU_PERBAIKAN** beserta alasan; pelanggan dapat memberi konfirmasi ulang pada pesanan yang sama. PAID tidak diturunkan oleh konfirmasi ulang. Pembayaran berbeda nominal/duplikat belum disetujui sampai diselesaikan melalui bantuan.

Tidak perlu fitur upload bukti pelanggan pada MVP; bantuan WA dan referensi/keterangan cukup. Waktu dan identitas penerimaan yang dicatat admin menjadi bukti audit. Verifikasi dilakukan rutin; tidak menjanjikan aktivasi instan atau SLA yang belum tersedia.

## 4. Aturan voucher dan klaim

Harga Rp100.000; semua paket cincin dengan transaksi sebelum potongan minimal Rp8.000.000; satu voucher untuk satu order/sepasang cincin; tanpa promo lain, refund atau pencairan voucher; lima cabang tanpa janji temu.

| Hari voucher saat klaim | Potongan |
|---|---:|
| 1–30 | Rp800.000 |
| 31–60 | Rp500.000 |
| 61–90 | Rp300.000 |
| 91–365 | Rp100.000 |
| >365 | Kedaluwarsa |

Hari pembelian adalah hari pertama. purchased_at berasal dari waktu pembayaran QRIS yang benar-benar diterima dan diverifikasi, **bukan waktu klik sudah bayar atau waktu approve**. Umur = selisih tanggal kalender klaim dan pembayaran di WIB/Asia/Jakarta + 1. Server menolak waktu pembayaran di masa depan atau sesudah verifikasi; waktu kejadian dan waktu verifikasi disimpan terpisah.

Kebijakan/nominal DP, kuitansi DP, perhitungan net/pelunasan, perubahan dan pembatalan order merupakan tanggung jawab sistem order lama. Aplikasi voucher tidak membuat kebijakan DP atau ledger cincin.

CS memeriksa minimum belanja/promo dan konfirmasi order/DP pada sistem order, scan/input kode voucher, pilih cabang, isi nomor order, lalu konfirmasi syarat. Scan/preview tidak memutasi status. **Klaim voucher** mengecek ulang status/umur/policy, menyimpan klaim sekali, mengunci potongan dan menandai TERPAKAI. Data klaim minimum: voucher, cabang, nomor order, CS, waktu WIB, umur, potongan dan konfirmasi kelayakan.

Nomor order trim/uppercase; kombinasi cabang+nomor order unik untuk satu klaim. Minimum belanja dan status order dipastikan CS secara manual; aplikasi tidak mengklaim telah memverifikasinya melalui API.

CS memfinalisasi potongan di sistem order sesudah klaim sukses. Bila input order gagal, lanjutkan pada order yang sama dengan referensi klaim; voucher tetap terpakai. Klaim dilakukan dalam satu alur saat order/DP dikonfirmasi; tanpa backdate otomatis. Nilai berubah sejak preview → minta konfirmasi baru. Order cincin batal tidak otomatis mengaktifkan kembali voucher.

Transfer sekali melalui admin saat aktif/belum expired, setelah persetujuan dari WA pemilik terdaftar. Ganti pemilik/WA, token QR **dan kode pendek**, catat riwayat, kirim link baru. Link/kode lama invalid; tanggal/policy tetap. Lifecycle AKTIF/TERPAKAI/KEDALUWARSA; transfer sebagai riwayat.

Harga/tier/syarat yang ditampilkan checkout melekat pada pesanan/voucher. Perubahan versi hanya untuk checkout baru; tidak mengubah janji kepada pembeli lama.

## 5. Meta tracking

| Aktivitas | Event/sumber |
|---|---|
| Landing | ViewContent melalui Pixel sesuai consent |
| Checkout | InitiateCheckout; campaign/context disimpan pada checkout |
| Pembayaran verified | Purchase IDR100.000 melalui CAPI dari server |
| Klaim sukses | VoucherRedeemed terpisah melalui CAPI sesuai consent/identitas pemilik aktual |
| Voucher dibuka/scan | Catatan internal minimum; tidak memasang Pixel pada bearer link voucher |

Data customer/campaign/matching Meta disimpan **sejak checkout** sesuai persetujuan, lalu dipakai saat approve. Data browser admin tidak dipakai sebagai identitas pembeli. UTM membantu laporan internal; parameter klik/browser Meta dan data customer yang dinormalisasi/hash membantu matching sesuai kontrak API.

Purchase hanya setelah verifikasi uang masuk, event_time waktu bayar aktual, event_id stabil per pembayaran. Kirim melalui worker walaupun customer sudah menutup browser. Baseline Purchase hanya CAPI; bila nanti Pixel Purchase juga ditambahkan, nama/ID harus sama agar dedup sesuai aturan Meta. Retry/approve ulang tidak membuat business event baru.

VoucherRedeemed bukan Purchase cincin lunas; nilai potongan tidak dilaporkan sebagai revenue cincin. Untuk transfer, tidak mencampur identitas/cookie pembeli lama dengan penerima; consent/data matching klaim harus valid. Bila consent tidak ada, status SKIPPED ditampilkan dan laporan internal tetap mencatat transaksi.

Status Meta pending/sent/failed/skipped terlihat di admin; retry mempertahankan event_id dan event_time. Waktu lama yang ditolak API tidak diganti menjadi waktu fiktif. Verifikasi rutin membantu freshness; penerimaan event tidak menjamin seluruh transaksi teratribusi ke iklan. Setup dan hasil diuji di Events Manager.

## 6. Register keputusan final

| ID | Keputusan |
|---|---|
| D-01 | QRIS KGJ berupa gambar merchant existing; verifikasi uang masuk manual; tanpa gateway baru |
| D-02 | Domain/hosting diatur pengguna |
| D-03 | Pembatalan order di sistem lama; voucher yang sudah diklaim tetap TERPAKAI |
| D-04 | DP mengikuti sistem order existing; aplikasi tidak menentukan persentase/nominal DP |
| D-05 | Transfer sekali oleh admin, verifikasi WA pemilik, ganti token/kode |
| D-06 | Transfer sebagai riwayat, lifecycle tetap tiga status |
| D-07 | Browser HP/laptop yang tersedia; input manual dan kamera opsional |
| D-08 | WACO link transaksi; credentials/template aktual masih dibutuhkan |
| D-09 | Nama/WA wajib; cabang/tanggal nikah opsional; consent layanan wajib, marketing opsional |
| D-10 | Minimum Rp8 juta bruto/tanpa promo dikonfirmasi CS; order unik per cabang |
| D-11 | Usia dari paid_at QRIS terverifikasi; nilai dikunci saat klaim pada konfirmasi order/DP, WIB |
| D-12 | Versi aturan checkout melekat; perubahan untuk checkout baru |
| D-13 | Satu panel, verifikasi pembayaran, QRIS image setting, klaim/transfer/CSV dan tracking Meta MVP |
| D-14 | Laravel 13/PHP 8.4/Livewire 4/PostgreSQL 17/Tailwind 4/PHPUnit dan Docker Compose |
| D-15 | Purchase Rp100.000 CAPI setelah approve; VoucherRedeemed terpisah; tanpa revenue cincin otomatis |
| D-16 | Terang/hijau dalam/aksen metal hangat/font sistem; bukan klaim brand korporat |

## 7. Acceptance criteria dan rencana test

Aplikasi dan test suite lokal sudah tersedia. Tabel ini tetap gerbang penerimaan; bukti serta batas aktual ada di [handoff](IMPLEMENTATION-HANDOFF.md).

| ID | Kriteria wajib |
|---|---|
| AC-01 | QRIS asli/nominal/merchant terlihat dan dapat diunduh; penggantian menjaga snapshot pesanan lama; kamera/aplikasi pembayaran nyata diuji |
| AC-02 | Customer click/konfirmasi/refresh tidak mengubah PAID; order publik memerlukan token akses |
| AC-03 | Approve hanya admin aktif dengan referensi penerimaan unik, paid_at sah dan konfirmasi nominal tepat |
| AC-04 | Dua approval/retry satu order menghasilkan satu voucher dan satu business event Purchase; referensi sama pada dua order ditolak |
| AC-05 | Voucher/approval/audit/job rollback bersama atau commit bersama; crash tidak menghilangkan pengiriman |
| AC-06 | Usia menggunakan paid_at, bukan verified_at; batas hari 1/30/31/60/61/90/91/365/366 dan tengah malam WIB benar |
| AC-07 | Klaim hanya sesudah kelayakan manual/order-ref/cabang lengkap; satu voucher/order; dua koneksi DB konkuren hanya satu sukses |
| AC-08 | Quote berubah meminta konfirmasi baru; response hilang memeriksa hasil request sebelumnya; scan sendiri tidak menghabiskan voucher |
| AC-09 | Transfer kedua/expired/terpakai ditolak; transfer versus klaim serial; token/kode lama dicabut |
| AC-10 | Meta memakai data checkout customer, nilai100000/IDR, waktu bayar, ID stabil, consent/dedup; failure tidak membatalkan voucher |
| AC-11 | WA gagal/kirim ulang tidak membuat voucher baru; gunakan token/revision aktif |
| AC-12 | CSV/filter/pagination server; uang voucher dan potongan terpisah; file privat, formula injection ditangani |
| AC-13 | Login/CSRF/rate limit/akun nonaktif, akses token/PII dan upload QRIS invalid diuji |
| AC-14 | Alur mobile/keyboard/fokus/kontras/fallback kamera dan Compose/build/health/restart/volume/restore diuji |

## 8. Operasi, data dan handoff

Dashboard internal: checkout, pending verification, paid/pendapatan voucher, klaim, tier/umur/cabang dan cohort/campaign yang diketahui. Penjualan berdasarkan paid_at, klaim confirmed_at WIB; scan dihitung per voucher unik untuk konversi. Tidak ada KPI DP/net order/revenue cincin atau integrasi mengambil data order.

Akun operator bernama, tanpa registrasi publik; session dua jam idle, seluruh admin memiliki fungsi sama. Hanya operator tepercaya diberi akses. Koreksi memakai catatan/audit; paid_at dan klaim tidak diedit diam-diam setelah voucher terbit.

S&K memuat aturan voucher, minimum transaksi, perhitungan WIB/paid_at, aktivasi setelah verifikasi manual, sekali pakai/transfer, tanpa refund/promo, serta bantuan resmi. DP mengikuti order lama. Consent marketing terpisah. PII baseline anonimkan 90 hari setelah terpakai/expired tanpa kasus bantuan aktif; checkout belum dibayar anonimkan 90 hari setelah aktivitas terakhir; angka/ref ledger tetap tanpa PII. Bukan kebijakan retensi pembukuan KGJ. Ekspor privat kedaluwarsa 24 jam.

Data nyata yang dibutuhkan saat implementasi: gambar QRIS asli/identitas merchant, contoh riwayat/reference/timestamp untuk pencocokan, data lima cabang/kontak, WACO/template, Meta Pixel/Dataset/access token via environment, domain/hosting pengguna. Tidak perlu merchant gateway baru. QRIS/WACO/Meta belum diuji.

Target PDF MVP 30 Oktober 2026/UAT 31 Oktober–1 November/soft launch 2 November adalah target sumber, bukan hasil estimasi. Implementasi inti dan Dockerfile/Compose kini tersedia secara lokal; belum ada deployment domain/hosting pengguna atau uji integrasi nyata. Lihat [handoff](IMPLEMENTATION-HANDOFF.md).
