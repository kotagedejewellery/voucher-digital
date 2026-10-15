@props(['code','title','message','login'=>false])
@php
 $returnUrl=$login?route('login'):route('landing');
 $returnLabel=$login?'Masuk admin':'Kembali ke halaman utama';
 if (in_array((int)$code,[419,429,500,503])) {
  $token=request()->route()?->parameter('token');
  if (is_string($token) && preg_match('/^[a-f0-9]{64}$/D',$token)) {
   $isVoucher=request()->routeIs('voucher.show');
   $returnUrl=route($isVoucher?'voucher.show':'payment.show',$token);
   $returnLabel=$isVoucher?'Periksa status voucher':'Periksa status pesanan';
  } elseif (request()->is('admin/*') && !$login) {
   $returnUrl=route('admin.orders'); $returnLabel='Periksa pesanan dan voucher';
  }
 }
 try { $help=\App\Models\Setting::read('support_phone'); } catch (\Throwable) { $help=null; }
@endphp
<x-layout :title="$title"><div class="wrap error-layout"><div class="error-code" aria-hidden="true">{{ $code }}</div><section><h1>{{ $title }}</h1><p>{{ $message }}</p><div class="actions"><a class="button" href="{{ $returnUrl }}">{{ $returnLabel }}<x-icon/></a>@if($help)<a class="button secondary" href="https://wa.me/{{ ltrim($help,'+') }}" rel="noreferrer">Hubungi bantuan</a>@endif</div></section></div></x-layout>
