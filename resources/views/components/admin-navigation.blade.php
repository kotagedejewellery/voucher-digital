<nav class="admin-navigation" aria-label="Navigasi admin">
 <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-icon name="overview"/>Ringkasan</a>
 <a href="{{ route('admin.orders') }}" @if(request()->routeIs('admin.orders','admin.order','admin.voucher')) aria-current="page" @endif><x-icon name="voucher"/>Pesanan & Voucher</a>
 <a href="{{ route('admin.voucher-history') }}" @if(request()->routeIs('admin.voucher-history')) aria-current="page" @endif><x-icon name="clock"/>Riwayat Voucher</a>
 <a href="{{ route('admin.settings') }}" @if(request()->routeIs('admin.settings')) aria-current="page" @endif><x-icon name="settings"/>Pengaturan</a>
</nav>
<div class="sidebar-bottom"><p>Operator<br><strong>{{ auth()->user()->name }}</strong></p><form method="post" action="{{ route('logout') }}" data-confirm-title="Keluar dari panel?" data-confirm-message="Anda akan keluar dari panel admin/CS. Pastikan perubahan formulir telah disimpan sebelum melanjutkan." data-confirm-button="Keluar">@csrf<button class="button sidebar-logout" type="submit"><x-icon name="logout"/>Keluar</button></form></div>
