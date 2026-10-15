<x-layout title="Masuk admin"><div class="wrap login-layout"><section class="login-brand"><h2>Kelola voucher. Layani dengan pasti.</h2><p>Satu panel untuk memeriksa pembayaran, mengelola voucher, dan mengonfirmasi klaim di cabang.</p><p class="login-detail">Kotagede Jewellery<br>Voucher Digital Sat-Set</p></section>
<section class="login-form"><h1>Masuk admin</h1><p>Gunakan akun operator Anda untuk memverifikasi pembayaran dan mengelola voucher.</p><form method="post" action="{{ route('login') }}">@csrf
 <div class="field"><label for="email">Email operator</label><input id="email" type="email" name="email" required autocomplete="username" value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror></div>
 <div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
 <button class="button full" type="submit">Masuk<x-icon/></button>
</form><p class="hint">Akun dibuat oleh pengelola sistem. Tidak ada registrasi publik.</p></section></div></x-layout>
