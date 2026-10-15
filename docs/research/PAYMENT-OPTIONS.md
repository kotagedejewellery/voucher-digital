# Riset pilihan pembayaran voucher

> Keputusan proyek terbaru: pengguna menetapkan gambar QRIS merchant KGJ dan verifikasi manual admin/CS, dengan Meta tracking dari server. Acuan implementasi adalah [PRD v0.3](../PRD.md). Kajian alternatif di bawah dipertahankan sebagai riwayat riset, bukan daftar provider yang masih harus dipilih atau diintegrasikan.

Tanggal penelusuran: **14 Oktober 2026**. Status: **proposal untuk diskusi**, bukan keputusan provider atau perubahan PRD. Semua rujukan eksternal berasal dari penyedia, pemilik proyek, atau Bank Indonesia. Pendaftaran merchant, transaksi nyata, biaya kontraktual, dan settlement belum diuji.

## Kesimpulan

Belum ditemukan opsi yang sekaligus terbukti **open source, menerima pembayaran rupiah melalui QRIS/VA di Indonesia, dan menghapus pendaftaran/verifikasi merchant**. Open source menjelaskan lisensi perangkat lunak; penerimaan uang tetap memerlukan layanan bank/processor. Contohnya Hyperswitch secara eksplisit meminta pendaftaran PSP dan kredensial PSP. [Dokumentasi connectors Hyperswitch](https://docs.hyperswitch.io/other-features/connectors.md)

Pengguna mengonfirmasi KGJ sudah memiliki **rekening bisnis dan QRIS merchant**. Status operasional belum diuji. Rekomendasi untuk menghindari pendaftaran gateway baru: pertimbangkan **verifikasi pembayaran manual** menggunakan fasilitas yang ada, setelah kecocokan QRIS untuk pembayaran jarak jauh ini dipastikan. Jika voucher harus diterbitkan otomatis, gunakan **satu penyedia langsung**. Ini proposal untuk diskusi, bukan penetapan metode pembayaran baru.

## Open source yang benar, tetapi tidak menyelesaikan onboarding

| Pilihan | Lisensi dan fungsi | Kesesuaian untuk voucher KGJ |
|---|---|---|
| Hyperswitch | Apache 2.0; platform pembayaran/orchestration, tersedia self-host dan setup Docker. | Tetap memerlukan akun PSP dan kredensialnya. Menambah layanan operasional untuk proyek satu voucher; tidak direkomendasikan untuk MVP sederhana. |
| Payum | MIT; library PHP yang mengintegrasikan layanan pembayaran. | Bukan processor yang menerima/settle uang sendiri. Tidak ada bukti pada sumber yang ditelaah bahwa memasangnya menghasilkan QRIS/VA lokal siap produksi tanpa kontrak processor. Tidak diperlukan untuk satu provider. |

Lisensi dan fungsi: [repo resmi Hyperswitch](https://github.com/juspay/hyperswitch), [repo resmi Payum](https://github.com/Payum/Payum). Kewajiban akun PSP: [connectors Hyperswitch](https://docs.hyperswitch.io/other-features/connectors.md). Penilaian kesesuaian MVP adalah inferensi dari fungsi kedua proyek dan scope KGJ. Dukungan channel Indonesia per connector belum diaudit satu per satu.

Library/SDK yang terbuka di GitHub **tidak membuktikan platform pemrosesan uangnya open source**. Pada sumber resmi penyedia berikut belum ditemukan lisensi yang memperbolehkan self-host seluruh platform. Karena itu, penyedia tersebut diperlakukan sebagai **layanan komersial**, bukan pilihan open source.

## Dua alternatif komersial untuk dievaluasi

### 1. Duitku — kandidat pertama untuk evaluasi pembayaran otomatis

Duitku mendukung QRIS dan VA serta callback server dengan signature. Sandbox tersedia untuk integrasi; produksi memerlukan verifikasi identitas dan website. [FAQ Duitku](https://www.duitku.com/frequently-asked-questions/), [dokumentasi akun](https://docs.duitku.com/en/account/), [API callback](https://docs.duitku.com/pop/en/)

Daftar dokumen perusahaan pada halaman resmi: **KTP direktur, NPWP, NIB, SIUP, akta beserta SK, foto lokasi, dan rekening**. Merchant personal tetap memerlukan KTP, NPWP/kartu pelajar sesuai ketentuan, dan rekening; ketentuan personal bukan jalan pintas untuk transaksi badan usaha KGJ. [Panduan merchant Duitku](https://www.duitku.com/wordpress-payment-gateway/)

Alasan masuk shortlist: dokumentasi memisahkan sandbox, produksi, dan callback dengan jelas. **Tidak ada bukti bahwa prosesnya lebih ringan daripada Midtrans/Xendit untuk PT.** Pastikan penjualan voucher diskon diterima, dokumen terbaru yang diwajibkan untuk KGJ, channel QRIS/VA yang aktif, biaya, dan settlement sebelum memilih. Ini langkah evaluasi yang diusulkan.

### 2. TriPay — kandidat pembanding dengan callback dan nominal tetap

API resmi menyediakan QRIS/VA, sandbox/production, callback, serta tipe **closed payment**: nominal ditentukan merchant dan kode pembayaran digunakan sekali. Ini sesuai transaksi voucher Rp100.000; kesesuaian tersebut adalah inferensi kebutuhan KGJ. [API Developer Guide TriPay](https://tripay.co.id/developer)

Panduan pendaftaran pada subdomain resmi **docs-internal** mencantumkan: akun personal menggunakan KTP; akun bisnis membutuhkan **KTP penanggung jawab, NPWP penanggung jawab, SIUP atau NIB, akta pendirian, serta NPWP perusahaan**. Ada verifikasi identitas/wajah melalui Privy dan tanda tangan PKS. Karena sumber berada pada subdomain internal yang dapat diakses publik, syarat final perlu dikonfirmasi melalui proses merchant resmi. [Panduan akun TriPay](https://docs-internal.tripay.co.id/en/guide/account/registration)

Panduan merchant tersebut juga mencantumkan pembatasan domain, callback pada domain yang sama, dan whitelist IP. Domain yang disebut tidak diterima termasuk `my.id`, `biz.id`, `xyz`, Vercel, Netlify, Blogspot, dan WordPress. **Jangan mengatur domain/hosting berdasarkan daftar ini sebelum provider dipilih dan ketentuannya dikonfirmasi.** [Panduan merchant TriPay](https://docs-internal.tripay.co.id/en/guide/merchant/registration)

Dengan syarat di atas, TriPay belum dapat diklaim lebih mudah untuk PT.

## Pilihan lain yang tidak masuk shortlist

**iPaymu:** API menyediakan QRIS/VA dan callback ke `notifyUrl`. FAQ situs mencantumkan **minimal lima produk**. Karena KGJ hanya menjual satu voucher, tidak diutamakan sebelum provider mengonfirmasi pengecualian. [Direct Payment iPaymu](https://docs.ipaymu.com/en/docs/payment/direct-payment), [FAQ verifikasi](https://ipaymu.com/id/faq-account-and-verification/)

**Mayar:** platform commerce dengan payment link dan webhook `payment.received`; situs menyatakan Mayar bukan bank/payment gateway. Pembuatan akun sederhana belum berarti merchant PT siap menerima uang. Panduan mensyaratkan verifikasi sebelum menjual. Dokumen PT: **KTP pemilik/direktur, NPWP perusahaan, NIB, akta pengangkatan direktur terbaru, SK Kemenkumham pengangkatan direktur, dan bukti rekening**; izin industri lain opsional. Belum ada dasar untuk mengklaim onboarding PT lebih ringan. Dapat dievaluasi bila checkout/payment link siap pakai lebih penting daripada integrasi gateway langsung. [Mayar](https://mayar.id/), [Account Setup](https://docs.mayar.id/accountsetup), [Business Verification](https://docs.mayar.id/businessverify), [Webhook](https://docs.mayar.id/integration/webhook)

## Alternatif paling sederhana tanpa gateway baru

**KGJ sudah memiliki rekening bisnis dan QRIS merchant, sesuai konfirmasi pengguna.** Proposal: pelanggan membuat pesanan voucher, membayar, admin mencocokkan pembayaran pada mutasi/notifikasi resmi merchant, lalu mengaktifkan voucher. QRIS statis membuat pelanggan memasukkan nominal sendiri; QRIS dinamis memuat nominal yang ditentukan merchant. Merchant menerima notifikasi pembayaran. [Penjelasan QRIS Bank Indonesia](https://www.bi.go.id/en/fungsi-utama/sistem-pembayaran/ritel/kanal-layanan/QRIS/default.aspx)

Alur manual di atas adalah proposal operasional. Voucher tidak boleh diaktifkan hanya berdasarkan screenshot pelanggan. Waktu aktivasi bergantung admin; pembayaran bersamaan bernominal sama perlu dicocokkan dengan referensi transaksi agar satu pembayaran tidak dipakai dua pesanan. Tidak diusulkan scraper mutasi, modifikasi QR pihak ketiga, atau aktivasi otomatis dari gambar QR.

## Arah pemilihan

1. **Ingin voucher otomatis dan funnel iklan berjalan tanpa menunggu admin:** evaluasi Duitku, bandingkan TriPay, gunakan satu gateway langsung. Sebelum keputusan, konfirmasi penerimaan model voucher KGJ, syarat badan usaha, biaya, settlement, serta API channel yang benar-benar tersedia. Belum ada dasar untuk menjanjikan provider mana paling cepat/mudah aktif.
2. **Ingin menghindari pendaftaran gateway baru dan menerima aktivasi manual:** manfaatkan rekening bisnis/QRIS resmi KGJ yang sudah dimiliki setelah status operasional dan kecocokannya dipastikan. Tracking pembelian baru dicatat setelah admin memverifikasi pembayaran.
3. **Open source wajib mencakup gateway self-host:** Hyperswitch/Payum memenuhi sisi lisensi software, tetapi tidak memenuhi tujuan mempermudah registrasi pembayaran lokal. Jangan menambahnya untuk MVP ini.

Payment gateway hanya untuk **pembelian voucher**. Penjualan cincin, DP, dan pemberian diskon di sistem order lama tetap dilakukan oleh CS/admin di cabang; tidak ada integrasi order yang diusulkan. Pengguna menegaskan claim terjadi setelah order/DP dikonfirmasi pada sistem lama. Batas scope ini berasal dari arahan pengguna pada diskusi 14 Oktober 2026.

## Belum diperiksa

Belum dilakukan pendaftaran, kontak sales/support, transaksi sandbox/live, verifikasi izin per channel, audit seluruh SDK, perbandingan biaya, atau uji settlement. Kecepatan aktivasi, pengecualian website satu produk, dan kelayakan voucher KGJ masih harus dikonfirmasi provider; tidak ada kredensial yang dikumpulkan.
