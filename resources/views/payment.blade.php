<x-layout title="Pembayaran voucher"><div class="wrap customer-workspace">
 <section class="customer-summary"><ol class="payment-steps" aria-label="Tahap pembelian"><li><x-icon name="check"/>Pesanan</li><li class="{{ $order->status==='PAID'?'':'current' }}">Pembayaran</li><li class="{{ $order->status==='PAID'?'current':'' }}">Voucher</li></ol>
  <h1>{{ match($order->status) {'PAID'=>'Pembayaran terverifikasi.','MENUNGGU_VERIFIKASI'=>'Pembayaran sedang diperiksa.','PERLU_PERBAIKAN'=>'Periksa konfirmasi pembayaran.',default=>'Bayar voucher Anda.'} }}</h1><x-status :state="$order->status"/>
  @if(in_array($order->status,['PAID','MENUNGGU_VERIFIKASI']))<details class="order-details"><summary>Rincian pesanan dan nominal</summary>@endif
  <div class="amount">Rp{{ number_format($order->price,0,',','.') }}</div><p>Voucher Digital Sat-Set untuk {{ $order->customer_name }}.</p>
  <dl class="detail"><dt>Tujuan pembayaran</dt><dd>{{ $order->qris->merchant_name }}</dd><dt>Minimum transaksi cincin</dt><dd>Rp{{ number_format($order->policy->minimum_order,0,',','.') }} sebelum potongan</dd><dt>Masa berlaku voucher</dt><dd>{{ $order->policy->valid_days }} hari dari tanggal pembayaran aktual yang diverifikasi admin, kalender WIB</dd></dl>
  @if(in_array($order->status,['PAID','MENUNGGU_VERIFIKASI']))</details>@endif
  <div class="payment-reference">Nomor pesanan<strong>{{ $order->reference }}</strong></div><p class="hint">Simpan link pesanan ini untuk memeriksa status. Voucher aktif setelah admin/CS memverifikasi uang masuk.</p>
  <button class="button secondary mt-4" type="button" data-copy="{{ route('payment.show',$order->access_token) }}" data-copy-label="Link pesanan tersalin. Jaga link ini tetap pribadi.">Salin link pesanan</button><p id="copy-status" class="hint"></p>
  @if($support)<a class="back-link mt-6" href="https://wa.me/{{ ltrim($support,'+') }}?text={{ rawurlencode('Mohon bantuan pesanan voucher '.$order->reference) }}" rel="noreferrer">Hubungi bantuan WhatsApp<x-icon/></a>@endif
 </section>
 <section class="panel payment-panel {{ in_array($order->status,['PAID','MENUNGGU_VERIFIKASI'])?'status-panel':'' }}">
  @if($order->status==='PAID')<x-icon name="check" class="icon-large"/><h2>Voucher Anda sudah diterbitkan.</h2>
   @if($voucherUrl)<p>Pembayaran sudah diverifikasi. Buka voucher untuk melihat potongan, kode, dan QR yang dapat ditunjukkan di cabang.</p><a class="button full" href="{{ $voucherUrl }}">Buka voucher saya<x-icon/></a>@else<p>Voucher telah dipindahtangankan. Link pemilik lama tidak berlaku.</p>@endif
  @else
   @if($order->status==='MENUNGGU_VERIFIKASI')<x-icon name="clock" class="icon-large"/><h2>Konfirmasi Anda diterima.</h2><p role="status">Admin/CS memeriksa uang masuk sebelum voucher aktif. Jangan membayar ulang. Tekan Periksa status setelah verifikasi; link ini tetap dapat dibuka kembali.</p><a class="button full" href="{{ request()->url() }}">Periksa status terbaru<x-icon/></a><details class="section"><summary>Lihat rincian pembayaran dan QRIS</summary>@else<h2>QRIS pembayaran voucher</h2><p class="meta">Periksa merchant dan nominal sebelum membayar.</p>@endif
   <img class="qr" src="{{ route('qris.show',$order->qris) }}" alt="QRIS pembayaran merchant {{ $order->qris->merchant_name }}" width="320" height="320" @if($order->status==='MENUNGGU_VERIFIKASI') loading="lazy" @endif>
   <a class="button secondary full" href="{{ route('qris.show',[$order->qris,'download'=>1]) }}">Unduh gambar QRIS asli</a><p class="hint">Jika QRIS tidak memuat nominal, masukkan persis Rp{{ number_format($order->price,0,',','.') }}. Pemindaian dari galeri bergantung pada aplikasi pembayaran Anda.</p>
   @if($order->status==='MENUNGGU_VERIFIKASI')</details>@else
    <div class="section">@if($order->status==='PERLU_PERBAIKAN')<div class="notice error" role="alert"><strong>Perlu perbaikan</strong><p>{{ $order->correction_reason }}</p></div>@endif
     <form method="post" action="{{ route('payment.confirm',$order->access_token) }}">@csrf<div class="field"><label for="note">Referensi/keterangan pembayaran <span class="optional">(opsional)</span></label><textarea id="note" name="note" maxlength="500" rows="3">{{ old('note',$order->customer_note) }}</textarea><p class="hint">Bantu admin mencocokkan pembayaran Anda. Tidak perlu mengunggah bukti.</p></div><button class="button full" type="submit">Saya sudah bayar<x-icon/></button></form>
    </div>
   @endif
  @endif
 </section>
</div></x-layout>
