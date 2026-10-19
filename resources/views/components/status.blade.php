@props(['state'])
@php
 $label=match($state) {'PAID'=>'Pembayaran terverifikasi','MENUNGGU_PEMBAYARAN'=>'Menunggu pembayaran','MENUNGGU_VERIFIKASI'=>'Menunggu verifikasi','PERLU_PERBAIKAN'=>'Perlu perbaikan','AKTIF'=>'Aktif','TERPAKAI'=>'Terpakai','KEDALUWARSA'=>'Kedaluwarsa','PENDING'=>'Dalam antrean','SENDING'=>'Sedang diproses','SENT'=>'Diterima layanan','MANUAL_SENT'=>'Dikirim manual','FAILED'=>'Gagal','SKIPPED'=>'Dilewati','UNCERTAIN'=>'Hasil belum pasti','READY'=>'Siap diunduh',default=>str_replace('_',' ',$state)};
 $tone=match($state) {'PAID','AKTIF','SENT','MANUAL_SENT','READY'=>'paid','FAILED','UNCERTAIN','PERLU_PERBAIKAN'=>'failed','TERPAKAI','KEDALUWARSA','SKIPPED'=>'neutral',default=>'pending'};
@endphp
<span {{ $attributes->merge(['class'=>'status '.$tone]) }}>{{ $label }}</span>
