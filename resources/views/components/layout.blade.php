@props(['title'=>'Voucher Sat-Set','admin'=>false,'auth'=>false,'pixel'=>false,'event'=>'ViewContent','price'=>100000,'consented'=>false,'available'=>true])
<!doctype html><html lang="id"><head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="referrer" content="no-referrer"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#174C40">
 <title>{{ $title }} · Kotagede Jewellery</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 @unless(request()->routeIs('landing'))<meta name="robots" content="noindex,nofollow">@endunless
 @vite(['resources/css/app.css','resources/js/app.js'])
 @if($admin && request()->routeIs('admin.orders')) @livewireStyles @endif
</head><body class="{{ $admin?'admin-body':($auth?'auth-body':'customer-body') }}" @if($pixel) data-meta-pixel="{{ config('voucher.meta.pixel_id') }}" data-event="{{ $event }}" data-price="{{ $price }}" data-consented="{{ $consented?'1':'0' }}" @endif>
<a class="skip button" href="#main">Lewati ke konten</a>
@if($admin)
 <aside class="admin-sidebar"><a class="brand sidebar-brand" href="{{ route('admin.dashboard') }}"><img class="brand-logo" src="{{ asset('images/kgj-logo.png') }}" width="156" height="96" alt="Kotagede Jewellery"><small>Voucher Digital Sat-Set</small></a><x-admin-navigation/></aside>
 <header class="admin-topbar"><div class="admin-topbar-title"><button class="button secondary menu-button" type="button" data-open-menu aria-haspopup="dialog" aria-controls="admin-menu"><x-icon name="menu"/><span class="sr-only">Buka menu admin</span></button><span>{{ $title }}</span></div><span class="operator-name">{{ auth()->user()->name }}</span></header>
 <dialog id="admin-menu" class="mobile-drawer" aria-labelledby="menu-title"><div class="drawer-heading"><h2 id="menu-title">Menu admin</h2><button class="button secondary" type="button" data-close-menu aria-label="Tutup menu admin"><x-icon name="close"/></button></div><x-admin-navigation/></dialog>
@elseif($auth)
 <header class="auth-masthead"><div class="auth-masthead-inner"><a class="auth-brand" href="{{ route('landing') }}"><img src="{{ asset('images/kgj-logo.png') }}" width="104" height="64" alt="Kotagede Jewellery"></a><span class="auth-access-number">Akses privat Admin/CS</span></div></header>
@else
 <header class="header"><div class="wrap header-inner"><a class="brand" href="{{ route('landing') }}"><img class="brand-logo" src="{{ asset('images/kgj-logo.png') }}" width="104" height="64" alt="Kotagede Jewellery"></a>
 @if(request()->routeIs('landing'))<nav class="public-nav" aria-label="Navigasi customer"><a class="public-nav-link" href="#cara-pakai">Cara pakai</a><a class="public-nav-link" href="#syarat">Syarat voucher</a><a class="button" href="#beli">{{ $available?'Beli voucher':'Info pembayaran' }}</a></nav>@else<a class="back-link" href="{{ route('landing') }}"><x-icon name="arrow-left"/><span>Halaman utama</span></a>@endif
 </div></header>
@endif
<main id="main" tabindex="-1">
 @if(!$auth && isset($errors) && $errors->any())<div class="wrap notice error" role="alert" tabindex="-1" data-errors data-section="{{ old('_section') }}" data-record="{{ old('id') }}"><strong>Periksa kembali data Anda.</strong><ul>@foreach($errors->messages() as $field=>$messages)<li data-error-field="{{ $field }}"><a href="#{{ $field }}">{{ $messages[0] }}</a></li>@endforeach</ul></div>@endif
 @if(session('success'))<div class="{{ session('feedback')==='toast'?'feedback-toast':'wrap' }} notice success" role="status"><x-icon name="check"/><span>{{ session('success') }}</span>@if(session('feedback')==='toast')<button type="button" data-dismiss-feedback aria-label="Tutup pesan"><x-icon name="close"/></button>@endif</div>@endif
 {{ $slot }}
</main>
<x-feedback/><footer class="{{ $auth?'auth-footer':'wrap footer' }}"><span>Kotagede Jewellery</span><span>Voucher Digital Sat-Set @if($admin) · Panel Admin/CS @endif</span></footer>
@if($admin && request()->routeIs('admin.orders')) @livewireScripts @endif
</body></html>
