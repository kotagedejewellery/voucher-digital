---
name: "Voucher Digital Sat-Set"
description: "Layanan butik yang bersih, profesional dan sederhana untuk pelanggan serta operator KGJ."
colors:
  canvas: "#F7F8F5"
  surface: "#FFFFFF"
  ink: "#19352C"
  muted: "#52675D"
  primary: "#174C40"
  primary-hover: "#103A30"
  control: "#75877D"
  rule: "#DDE4DD"
  gold: "#D8B66B"
  success: "#226B45"
  warning: "#80521A"
  danger: "#9C2F32"
  info: "#255E86"
  focus: "#1A65AD"
  offer-muted: "#E6EDE4"
  status-info-bg: "#ECF2F7"
  status-success-bg: "#EAF4EC"
  status-warning-bg: "#FBF0DB"
  status-danger-bg: "#FBECEB"
  status-neutral-bg: "#EFF1ED"
typography:
  display:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "clamp(44px, 4.5vw, 80px)"
    fontWeight: 600
    lineHeight: 1.06
    letterSpacing: "-.035em"
  headline:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "32px"
    fontWeight: 600
    lineHeight: 1.18
    letterSpacing: "-.025em"
  title:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "24px"
    fontWeight: 600
    lineHeight: 1.18
    letterSpacing: "-.025em"
  body:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.55
  label:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.55
  metadata:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.55
  amount:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "44px"
    fontWeight: 650
    lineHeight: 1.12
    letterSpacing: "-.03em"
  status:
    fontFamily: "system-ui, 'Segoe UI', sans-serif"
    fontSize: "12px"
    fontWeight: 600
    lineHeight: 1.5
rounded:
  control: "8px"
  panel: "16px"
  compact-panel: "12px"
  status: "6px"
spacing:
  xs: "4px"
  sm: "8px"
  field-block: "12px"
  field-inline: "14px"
  md: "16px"
  control-inline: "20px"
  field: "22px"
  lg: "24px"
  panel: "28px"
  xl: "32px"
  public-gutter: "40px"
  section: "48px"
  hero: "64px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  button-secondary-hover:
    backgroundColor: "{colors.canvas}"
  button-light:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  button-disabled:
    backgroundColor: "{colors.rule}"
    textColor: "{colors.muted}"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "12px 14px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "28px"
  offer:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    rounded: "{rounded.panel}"
    padding: "40px"
  status:
    backgroundColor: "{colors.status-info-bg}"
    textColor: "{colors.info}"
    typography: "{typography.status}"
    rounded: "{rounded.status}"
    padding: "5px 9px"
  navigation:
    textColor: "{colors.offer-muted}"
    rounded: "{rounded.control}"
    padding: "12px"
  settings-group:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.compact-panel}"
    padding: "0 28px"
  confirmation:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "32px"
    width: "min(560px, calc(100% - 32px))"
  feedback-toast:
    backgroundColor: "#EDF5EF"
    textColor: "{colors.success}"
    typography: "{typography.metadata}"
    rounded: "{rounded.control}"
    padding: "18px 20px"
    width: "min(440px, calc(100% - 32px))"
---
# Design System: Voucher Digital Sat-Set

Versi 0.6 · 14 Oktober 2026 · Sistem visual dan pola interaksi dari peningkatan seluruh halaman yang disetujui pengguna. Ini baseline aplikasi, bukan panduan merek korporat KGJ.

Token frontmatter mengikuti [CSS aplikasi](resources/css/app.css); [sidecar](.impeccable/design.json) menyimpan metadata, breakpoint, motion dan contoh komponen. [PRODUCT.md](PRODUCT.md) dan [PRD](docs/PRD.md) tetap menjadi sumber kebenaran produk. Komposisi/mode per halaman berada pada [surface brief](.impeccable/surfaces/resources-views-landing-blade-php.md).

## Overview

**Creative North Star: "Kartu layanan butik"**

Hijau dalam, putih hangat, detail emas kecil dan tipografi sistem membentuk layanan yang bersih, profesional dan sederhana. Angka rupiah kuat serta status yang jelas membantu pelanggan memahami voucher dan operator menyelesaikan tugas. Identitas yang dipin pengguna dipertahankan; komposisi baru memberi ruang layar yang lebih berguna.

Permukaan datar, garis halus dan sudut lembut menjaga hierarki. Satu tindakan utama memimpin setiap tahap; detail sekunder dapat dibuka sesuai kebutuhan. HTML membentuk kartu promosi, SVG native membentuk ikon garis, dan QR voucher dihasilkan saat runtime. QRIS merchant menggunakan gambar asli; logo, foto dan informasi cabang resmi menunggu aset KGJ.

**Key Characteristics:**

- Putih hangat dengan tindakan hijau dalam dan emas yang hemat.
- Angka rupiah tegas; status selalu memiliki teks.
- Layout desktop fluid, kolom mobile ringkas dan permukaan datar.
- Navigasi operator tetap di desktop dan drawer native di mobile.
- QRIS pembayaran utuh dan QR voucher memiliki fungsi berbeda.

### Bukti dan batas validasi

[Finish review enhancement](.impeccable/review/enhancement/finish-review.md) memberikan disposition **ship** untuk bukti UI lokal dan implementasi yang disampel, dengan nol temuan material. [Laporan browser enhancement](.impeccable/review/enhancement/browser-report.json) mencatat 131 pemeriksaan pada 34 state, lebar 320/390/768/1024/1440/1920 px tanpa overflow halaman atau error browser. Reviewer memvalidasi 167 PNG (131 capture, dua alias desktop/mobile dan 34 galeri), membuka seluruh galeri serta 21 gambar asli. Handoff implementasi melaporkan assertion pembatalan/Escape konfirmasi, input yang tetap tersimpan, checkout → verifikasi → klaim fixture, pemulihan daftar dan feedback clipboard; PHPUnit: 22 test, 202 assertion. Reviewer tidak menjalankan ulang browser atau suite.

Transaksi nonkosong dan gambar pembayaran adalah fixture sintetis pada database QA terisolasi, tanpa worker pengiriman atau uang nyata. Artifact detector enhancement berupa JSON lengkap `[]` hanya untuk `resources/views`; CSS/JS tidak diperiksa detector. Lifecycle expired dan pemulihan BFCache diperiksa dari sumber, bukan capture. Pemetaan error setiap record pengaturan dan setiap cabang validasi finansial belum diuji independen. Recovery419 memakai respons HTTP aktual tanpa token/cross-site yang dirender melalui fixture route browser; ini belum membuktikan expiry alami di setiap browser. Kamera dan clipboard dimock. Target tetap WCAG 2.2 AA; audit WCAG/screen reader penuh, kamera fisik, QRIS aktual, WACO/Meta live dan UAT operasional belum dilakukan. Verdict lokal bukan sertifikasi produksi. Tidak ada raster UI produksi baru; gambar merchant QRIS tetap asli.

## Colors

Palet terang bernuansa hangat memberi kontras kepada hijau dalam; nilai normatif ada di frontmatter.

### Primary

- **Hijau dalam** (`primary`): tindakan utama, tautan, sidebar dan kartu voucher.
- **Hijau tekan** (`primary-hover`): feedback hover tindakan utama dan tautan.

### Secondary

- **Metal hangat** (`gold`): garis tipis di kartu promosi; aksen kecil, bukan informasi utama.

### Neutral

- **Putih hangat** (`canvas`) dan **putih permukaan** (`surface`): dasar halaman dan panel/form.
- **Tinta hijau gelap** (`ink`) dan **teks pendamping** (`muted`): isi utama dan metadata.
- **Garis kontrol** (`control`) dan **garis pemisah** (`rule`): batas input/aksi sekunder dan struktur panel; rule juga digunakan pada disabled.
- **Teks bidang hijau** (`offer-muted`): keterangan pada offer, voucher dan navigasi operator.

### Status dan fokus

Status memakai pasangan teks/latar: info dengan status-info-bg, success dengan status-success-bg, warning dengan status-warning-bg, danger dengan status-danger-bg, serta muted dengan status-neutral-bg. Notice memiliki bidang pucat tersendiri untuk informasi, sukses dan error. Fokus memakai outline biru terpisah (`focus`).

**The Status Text Rule.** Setiap status memiliki teks yang dapat dibaca tanpa membedakan warna.

## Typography

**Display Font / Body Font:** system-ui, Segoe UI, sans-serif. Satu keluarga tanpa unduhan font; bobot 400/500/600/650 membedakan isi, detail, judul dan nominal. Tabel/nominal memakai tabular numerals; paragraf maksimum 72ch, pengantar hero 56ch. Nama/kode panjang wrap pada area yang tersedia.

### Hierarchy

Display dan nominal dasar mengikuti frontmatter. Display menjadi 48px hingga 1023px, 42px hingga 767px, dan 36px hingga 359px. Judul halaman menjadi 28px pada mobile; subjudul dasar 18px. Body pelanggan 16px, body admin 15px; input mewarisi ukuran body pada desktop, sedangkan input/select/textarea memakai 16px hingga 767px. Label/tombol 14px, badge 12px dan metadata tabel 12px.

Nominal hero memakai clamp(44px, 4.5vw, 72px); ringkasan pembayaran/voucher 64px desktop, 48px hingga 1199px dan 42px hingga 359px. Nominal umum menjadi 36px pada mobile. Judul ringkasan customer 42px desktop/32px mobile. Kode voucher 24px/bobot 600/letter-spacing .055em dan dapat wrap.

**The Money Rule.** Rupiah memakai pemisah ribuan titik; waktu transaksi dan deadline diberi label WIB.

## Layout

Konten publik fluid tanpa batas lebar global: gutter 40px per sisi, 48px mulai 1600px, dan 16px hingga 767px. Admin mulai 1024px memakai sidebar tetap 232px; area utama mengisi ruang tersisa dengan gutter 32px. Hingga 1023px sidebar diganti menu; gutter admin tetap 32px sampai batas mobile 767px. Halaman utama berjarak vertikal 32px/48px desktop dan 24px/32px mobile.

Grid memakai kolom minmax(0, fr) agar teks/field dapat menyusut. Gap umum 28px, pasangan customer 48px, hero 64px (96px mulai 1600px). Hingga 1199px gap diperpendek, grid dashboard dan form pengaturan menumpuk. Hingga 1023px tier menjadi dua kolom. Hingga 767px hero, checkout, pembayaran, voucher, detail dan error menjadi satu kolom; field menumpuk, daftar pesanan memakai kartu dan category pengaturan scroll lokal.

Komposisi yang dibangun: landing hero penawaran/voucher dua kolom, empat tier dan perjalanan tiga langkah menuju form; pembayaran/voucher dua kolom ringkasan serta tindakan; login sebagai folio otorisasi tunggal yang ringkas, dengan masthead privat, tulang buku hijau, pengantar pucat, motif segel kecil, lalu form sebagai fokus. Pada desktop normal, folio dan footer berada dalam satu viewport tanpa scroll yang tidak perlu; layar pendek dan mobile tetap dapat scroll secara alami agar field dan target sentuh tidak dipangkas. Login menyampaikan batas percobaan secara jelas, menawarkan bantuan WhatsApp bila nomor bantuan telah dikonfigurasi, dan memberi kontrol tampil/sembunyikan kata sandi yang berlabel. Pada mobile, identitas login tetap hadir di atas lembar otorisasi dan tidak disembunyikan. Urutan voucher mobile ialah kartu nilai → QR/kode → petunjuk/syarat. Pola halaman ini ditetapkan dalam surface brief, bukan kewajiban semua halaman baru.

Panel padding 28px desktop/22px mobile. Offer 40px desktop, 48px mulai 1600px, 28px hingga 1199px dan 22px hingga 359px. Input/tombol utama minimal 48px; CTA hero 54px desktop/50px mobile, tautan publik dan label checkbox minimal 44px. Tabel desktop dapat scroll lokal; pagination tetap server-side.

## Elevation & Depth

Tidak ada shadow. Kontras bidang, garis, jarak dan susunan memberi kedalaman; drawer dan dialog konfirmasi memakai backdrop gelap transparan (#142D26B8) untuk memisahkan modal dari konten. Toast berada di atas halaman tanpa menghalangi interaksi atau mengambil fokus.

**The Flat Surface Rule.** Pemisahan informasi memakai nada permukaan, garis dan jarak sebelum menambah efek kedalaman.

## Shapes

Kontrol memakai radius control; panel, voucher dan bidang brand memakai panel; tier, metric strip dan pengaturan memakai compact-panel; badge memakai status. Panel berubah ke compact-panel pada mobile. Stroke umum 1px, garis emas offer 1px dan pemisah bawah offer dashed. Ikon SVG memakai viewBox 24×24, stroke 1.7, ujung/sambungan bulat, ukuran 20px atau 32px; ikon informatif ditemani label teks.

QRIS menggunakan object-fit contain, lebar hingga 320px dan rasio asli. SVG QR voucher hingga 280px dengan bidang putih/inset 20px dan quiet zone dari generator. Keduanya tidak dicrop, diberi filter atau overlay.

## Components

### Buttons

Tindakan ringkas dengan warna/padding frontmatter, minimal 48px, bobot 600 dan gap ikon 10px. Sekunder putih/stroke control, hover canvas; varian light tetap putih dengan teks hijau di bidang hijau. Disabled memakai rule/muted/stroke rule dan cursor not-allowed. Focus-visible outline 3px/gap 3px digunakan pada kontrol interaktif.

Feedback background-color dan border-color berlangsung 180ms ease-out; anchor scroll halus. Reduced-motion meniadakan transisi tombol dan memakai scroll auto. Drawer dan dialog tidak memiliki animasi entrance; konten langsung terlihat. Submit biasa menonaktifkan tombol submit, menampilkan “Memproses…” dan aria-busy serta menahan submit duplikat. Offline mengarahkan pengguna memeriksa status sebelum mengirim ulang. Pemulihan BFCache mengembalikan tombol semula dan mengingatkan pemeriksaan status. Livewire memiliki state sendiri; guardrail server tetap menentukan duplikasi dan keabsahan transaksi.

### Chips

Badge status memakai warna berpasangan di bagian Colors, padding 5px/9px, font status dan radius status. Teks menjadi sumber makna; badge bukan tombol/filter. Notice error memakai role alert dan sukses role status; teks clipboard/kamera mendukung hasil atau langkah manual.

### Cards / Containers

Panel putih/border rule tanpa shadow; table-panel menghapus inset agar filter/tabel/kartu pesanan berbagi tepi. Metric strip empat kolom desktop dan dua mobile. Empty state memakai ikon garis, judul dan penjelasan faktual. Offer HTML hijau membingkai nominal dengan garis emas tipis dan pemisah dashed; tidak menyerupai QR yang dapat dibayar.

### Inputs / Fields

Input/select/textarea putih, font body pada desktop dan 16px pada mobile, inset 12px/14px, border control 1px, radius control, caret primary dan fokus global. Label terlihat, field opsional berlabel, hint dekat field. Error Indonesia menetap dekat field dengan teks 14px/danger dan border invalid, dihubungkan melalui aria-invalid/aria-describedby; error dihapus ketika field valid. Field pertama yang invalid menerima fokus; disclosure leluhurnya dibuka. Ringkasan error server memakai role alert dan tautan ke field; pengaturan mencocokkan bagian serta record agar nama field berulang tidak salah sasaran. Checkbox native 20px berada dalam label minimal 44px.

Input manual tersedia sebagai alternatif scanner; scanner dimuat saat dipakai melalui code splitting. Feedback membedakan kamera sedang dibuka, izin/dukungan tidak tersedia, pemindaian gagal dan scan berhasil menuju preview. Kamera dapat dihentikan dan dilepas saat halaman ditinggalkan; scan tetap belum melakukan klaim.

### Navigation

Sidebar desktop hijau memakai Ringkasan, Pesanan & Voucher, Pengaturan. Tautan 48px, padding 12px, ikon garis dan radius control; hover/aria-current memakai bidang putih kehijauan (#F2F5EE), teks primary dan bobot aktif 650. Operator/logout di bawah. Identitas berupa nama tanpa logo rekaan; skip link muncul saat fokus menuju main.

Drawer mobile menggunakan dialog native showModal/close, label/tombol tutup, lebar min(340px, 90vw), tinggi 100dvh. Native modal menangani containment fokus/Escape; klik backdrop di kanan drawer dan perpindahan ke desktop menutupnya. Navigasi publik desktop memuat bantuan/syarat; mobile mempertahankan CTA ringkas.

### Settings / Disclosure

Pengaturan memakai details/summary native dengan grup bernama dan navigasi category. JavaScript membuka bagian terpilih, menutup bagian lain, menyelaraskan aria-current dan hash URL, termasuk fragment awal/perubahan hash. Error membuka bagian/record terkait serta mempertahankan input. Category tetap di desktop dan scroll lokal di mobile; summary 76px desktop/64px mobile. Disclosure lain menyimpan rincian QRIS, campaign, persetujuan promosi, riwayat dan aksi sekunder tanpa menyembunyikan keputusan utama. Rincian pesanan/nominal pembayaran yang sudah menunggu verifikasi atau terverifikasi dapat dibuka sesuai kebutuhan.

### Confirmation / Feedback

Dialog native bersama merangkum verifikasi pembayaran, klaim, transfer sekali, aturan baru dan penonaktifan operator. Ringkasan memakai nilai input saat ini, label cabang terpilih dan konteks tindakan. Fokus awal berada pada Batal; Batal/Escape menutup tanpa mengirim, sedangkan konfirmasi mengirim kembali form melalui validasi normal. Fallback browser memakai konfirmasi native bila showModal tidak tersedia. Dialog memakai token confirmation, border rule dan scroll vertikal hingga tinggi viewport dikurangi 32px; hingga 767px padding 24px, radius compact-panel dan tombol bertumpuk penuh lebar. Native modal menjaga containment fokus.

Toast rutin memakai token feedback-toast, border sukses (#AFCAB9), role status dan tombol tutup berlabel minimal 44px. Posisi kanan/bawah 24px desktop dan 16px mobile; pesan panjang wrap, tanpa mengambil fokus. Toast runtime memakai aria-live polite/atomic dan waktu 6 detik; jika fokus masih di dalam toast saat waktu habis, pesan tetap tersedia sampai ditutup atau diganti. Toast sukses dari redirect server tersedia sampai ditutup. Error field serta hasil transaksi penting tetap berada di halaman. Clipboard gagal memberi petunjuk salin manual yang menetap; keberhasilan berikutnya membersihkan pesan gagal.

**The Correctable Error Rule.** Error yang perlu diperbaiki tetap dekat data dan membuka jalur fokus; toast hanya melengkapi feedback tindakan.

### Order Results / Recovery

Daftar memiliki heading hasil pencarian, jumlah sesuai filter, reset filter dan empty state yang menjelaskan langkah berikutnya. Pencarian memakai debounce 400ms; perubahan pencarian/status serta reset kembali ke halaman pertama, dengan pagination server. Kartu mobile menampilkan status voucher di samping kode. Saat request berjalan, hasil lama menjadi inert dan status memuat terlihat. Jika update gagal, notice menjelaskan hasil yang belum sesuai pilihan terbaru; hasil lama tetap inert hingga refresh baca berhasil. Retry memperbarui daftar, sedangkan sesi/akses berubah mengarahkan muat ulang. Tindakan finansial tidak dicoba ulang otomatis.

Recovery halaman error menawarkan pemeriksaan status melalui route/token tepercaya dan bantuan yang tersedia. Link pemulihan tidak mengambil URL kembali arbitrer dari pengguna dan tidak mengirim ulang POST finansial.

### Payment / Voucher / Claim

Pembayaran menampilkan nominal/merchant/QRIS asli dan konfirmasi; menunggu/verified tampil eksplisit. Menunggu verifikasi mengingatkan jangan membayar ulang, menyediakan pemeriksaan status GET serta link privat yang dapat disalin. Checkout yang belum tersedia menjelaskan kondisi dekat CTA dan tidak meminta data pelanggan.

Voucher aktif menampilkan potongan hari ini, tanggal/nilai tier berikutnya bila ada, masa berlaku dan QR runtime/kode. Nilai/tanggal mengikuti snapshot aturan pesanan serta kalender pembayaran WIB. Voucher terpakai menampilkan potongan terkunci, cabang/waktu penggunaan dan kode riwayat; expired menjelaskan masa berlaku berakhir tanpa perpanjangan otomatis. Keduanya tidak menampilkan QR aktif atau ajakan klaim/transfer baru. Preview klaim mendahului konfirmasi; hasil potongan terkunci dapat disalin dan tetap terpakai bila input order existing tertunda. PRODUCT/PRD tetap menentukan seluruh aturan transaksi.

## Do's and Don'ts

### Do:

- **Do** gunakan putih hangat, hijau dalam, font sistem dan aksen emas kecil yang disetujui.
- **Do** pakai ruang desktop fluid dan susun keputusan mobile dalam satu kolom yang jelas.
- **Do** tampilkan nominal, status teks, label, fokus visible dan alternatif input manual dekat tindakan.
- **Do** pertahankan QRIS merchant utuh dan bedakan QR voucher dari QRIS pembayaran.
- **Do** gunakan pagination server, disclosure dan lazy scanner sesuai kebutuhan nyata.

### Don't:

- **Don't** menambahkan identitas/foto/QRIS rekaan atau mengubah payload gambar merchant.
- **Don't** menyampaikan status dengan warna saja atau menghilangkan fokus keyboard.
- **Don't** menyamakan klik pelanggan dengan pembayaran verified atau scan dengan klaim.
- **Don't** memasukkan aturan bisnis baru atau detail credential/tracking ke alur pelanggan.
- **Don't** mengklaim fixture, screenshot atau output detector parsial sebagai sertifikasi aksesibilitas atau transaksi live.
