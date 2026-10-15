<x-layout :pixel="true" :price="$policy->price" :available="$qris!==null">
<div class="wrap">
 <section class="hero" aria-labelledby="offer-title">
  <div class="hero-copy"><h1 id="offer-title" class="display">Langkah kecil untuk <span>cincin pilihan Anda.</span></h1>
   <p>Beli voucher <strong>Rp{{ number_format($policy->price,0,',','.') }}</strong>, dapatkan potongan hingga <strong>Rp{{ number_format($policy->tiers[0]['discount'],0,',','.') }}</strong> untuk sepasang cincin pilihan Anda.</p>
   <p>Minimum transaksi Rp{{ number_format($policy->minimum_order,0,',','.') }} sebelum potongan. Berlaku semua paket; nilai mengikuti usia voucher saat order/DP dikonfirmasi di cabang.</p>
   <a class="button" href="#beli">{{ $qris?'Beli voucher Rp'.number_format($policy->price,0,',','.'):'Lihat informasi pembayaran' }}<x-icon/></a><p class="hint">{{ $qris?'Pembayaran QRIS · Aktivasi setelah verifikasi admin/CS':'Pembelian belum tersedia · Pembayaran sedang disiapkan' }}</p>
  </div>
  <div class="offer"><div class="offer-top"><h2>Voucher Sat-Set</h2><x-icon name="voucher" class="icon-large"/></div>
   <div class="offer-main"><p>Potongan hingga</p><div class="amount">Rp{{ number_format($policy->tiers[0]['discount'],0,',','.') }}</div><p>Untuk sepasang cincin, semua paket.</p></div>
   <div class="offer-bottom"><div><p>Harga voucher</p><strong>Rp{{ number_format($policy->price,0,',','.') }}</strong></div><div><p>Masa berlaku</p><strong>{{ $policy->valid_days }} hari</strong></div></div>
   <p class="hint">Nilai potongan mengikuti usia voucher saat klaim.</p>
  </div>
 </section>
 <section aria-labelledby="tier-title"><div class="section-title"><h2 id="tier-title">Lebih awal digunakan, lebih besar potongannya.</h2><p>Hari pembayaran adalah hari pertama · Kalender WIB</p></div>
  <div class="tiers">@php($start=1)@foreach($policy->tiers as $tier)<div class="tier"><h3>Hari {{ $start }}–{{ $tier['end'] }}</h3><strong>Rp{{ number_format($tier['discount'],0,',','.') }}</strong></div>@php($start=$tier['end']+1)@endforeach</div>
 </section>
 <section id="beli" class="buy-section" aria-labelledby="buy-title">
  <div class="buy-intro"><h2 id="buy-title">Mulai dari voucher. Lanjutkan di cabang.</h2><p>Isi identitas, bayar melalui QRIS, lalu konfirmasi pembayaran. Setelah uang masuk diperiksa, voucher dapat dibuka dari halaman pesanan dan dibawa ke cabang.</p>
   <ol id="cara-pakai" class="journey"><li><x-icon name="voucher"/><div><h3>Beli voucher</h3><p>Bayar melalui QRIS dan tunggu verifikasi pembayaran.</p></div></li><li><x-icon name="overview"/><div><h3>Pilih cincin di cabang</h3><p>Datang langsung untuk memilih cincin; tanpa janji temu.</p></div></li><li><x-icon name="scan"/><div><h3>Gunakan potongan Anda</h3><p>Tunjukkan voucher saat order/DP dikonfirmasi. CS memeriksa syarat lalu mengunci potongan.</p></div></li></ol>
   @unless($qris)<div class="notice" role="status">Pembayaran sedang disiapkan. Silakan kembali setelah QRIS tersedia.@if($support) <a href="https://wa.me/{{ ltrim($support,'+') }}" rel="noreferrer">Hubungi bantuan</a>. @endif</div>@endunless
  </div>
  @if($qris)<form class="panel checkout-panel" method="post" action="{{ route('checkout') }}">@csrf<h3>Pesan voucher Anda</h3>
   <div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" autocomplete="name" required maxlength="100" value="{{ old('name') }}" @error('name') aria-invalid="true" @enderror></div>
   <div class="field"><label for="phone">Nomor WhatsApp</label><input id="phone" name="phone" type="tel" autocomplete="tel" required maxlength="30" value="{{ old('phone') }}" aria-describedby="phone-hint" @error('phone') aria-invalid="true" @enderror><p id="phone-hint" class="hint">Gunakan nomor aktif milik Anda, misalnya 081234567890.</p></div>
   <div class="field-row"><div class="field"><label for="preferred_branch_id">Cabang pilihan <span class="optional">(opsional)</span></label><select id="preferred_branch_id" name="preferred_branch_id"><option value="">Belum menentukan cabang</option>@foreach($branches as $branch)<option value="{{ $branch['id'] }}" @selected(old('preferred_branch_id')==$branch['id'])>{{ $branch['name'] }}</option>@endforeach</select></div><div class="field"><label for="wedding_date">Tanggal pernikahan <span class="optional">(opsional)</span></label><input id="wedding_date" type="date" name="wedding_date" min="{{ now('Asia/Jakarta')->toDateString() }}" value="{{ old('wedding_date') }}"></div></div>
   <label class="check"><input type="checkbox" name="service_consent" value="1" required @checked(old('service_consent'))><span>Saya menyetujui <a href="#syarat">syarat voucher</a> dan penggunaan nama/WhatsApp untuk layanan voucher.</span></label>
   <label class="check"><input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent'))><span>Saya bersedia menerima informasi promosi dan mengizinkan pengukuran iklan Meta. <span class="optional">Opsional.</span></span></label>
   @foreach(['utm_source','utm_medium','utm_campaign','utm_content','utm_term','fbclid'] as $key)<input type="hidden" name="{{ $key }}" value="{{ old($key,substr((string)request($key),0,$key==='fbclid'?512:150)) }}">@endforeach
   <button type="submit" class="button full" @disabled(!$qris)>Lanjut bayar Rp{{ number_format($policy->price,0,',','.') }}<x-icon/></button><p class="hint">Pembayaran diverifikasi secara manual. Voucher tidak aktif hanya dari klik “Saya sudah bayar”.</p>
  </form>@else<section class="panel checkout-panel"><h2>Pembelian belum tersedia.</h2><p>QRIS pembayaran sedang disiapkan. Anda dapat membaca aturan voucher dan informasi cabang terlebih dahulu.</p>@if($support)<a class="button secondary" href="https://wa.me/{{ ltrim($support,'+') }}" rel="noreferrer">Hubungi bantuan</a>@endif</section>@endif
 </section>
 <div class="terms-layout section"><section id="syarat"><h2>Syarat yang perlu Anda ketahui.</h2><ul>
  <li>Satu voucher untuk satu order/sepasang cincin minimal Rp{{ number_format($policy->minimum_order,0,',','.') }} sebelum potongan, tanpa digabung promo lain.</li>
  <li>Voucher tidak dapat diuangkan atau dikembalikan. Berlaku {{ $policy->valid_days }} hari kalender dari tanggal pembayaran aktual yang diverifikasi admin.</li>
  <li>Potongan mengikuti usia voucher dalam WIB, dan terkunci saat klaim oleh CS setelah order/DP dikonfirmasi di sistem order cabang.</li>
  <li>Voucher dapat dipindahtangankan sekali melalui admin setelah persetujuan pemilik dari nomor WhatsApp terdaftar. Link/kode lama dicabut.</li>
  <li>Kebijakan DP dan pembatalan order mengikuti sistem order cabang. Voucher yang sudah diklaim tetap terpakai.</li>
 </ul><details><summary>Penggunaan data pribadi</summary><p>Nama dan WhatsApp digunakan untuk mengelola voucher. Persetujuan marketing bersifat pilihan. Bila disetujui, konteks iklan dan identitas yang di-hash dapat digunakan untuk pengukuran Meta. Data layanan dianonimkan 90 hari setelah voucher terpakai/kedaluwarsa, atau pesanan belum dibayar tidak aktif, selama tidak ada kasus bantuan terbuka. Catatan angka transaksi tetap disimpan.</p></details></section>
 <section><h2>Temui kami di cabang.</h2>@forelse($branches as $branch)<div class="branch-item"><h3>{{ $branch['name'] }}</h3><p>{{ $branch['address'] }}</p></div>@empty<p class="meta">Informasi cabang sedang dilengkapi.</p>@endforelse @if($support)<a class="button secondary mt-6" href="https://wa.me/{{ ltrim($support,'+') }}" rel="noreferrer">Hubungi bantuan WhatsApp<x-icon/></a>@endif</section></div>
</div></x-layout>
