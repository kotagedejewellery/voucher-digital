@props(['name'=>'arrow'])
@php($path=match($name) {
 'overview'=>'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
 'voucher'=>'M4 5h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4z M15 5v3 M15 11v2 M15 16v3',
 'settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z',
 'scan'=>'M8 3H3v5 M16 3h5v5 M3 16v5h5 M21 16v5h-5 M7 7h3v3H7z M14 7h3v3h-3z M7 14h3v3H7z M14 14h3v3h-3z',
 'menu'=>'M4 6h16 M4 12h16 M4 18h16','close'=>'m6 6 12 12 M18 6 6 18',
 'logout'=>'M10 4H4v16h6 M13 8l4 4-4 4 M8 12h12','check'=>'m5 12 4 4L19 6',
 'clock'=>'M12 7v5l3 2 M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18',
 'lock'=>'M6 10h12v11H6z M8 10V7a4 4 0 0 1 8 0v3 M12 14v3',
 'arrow-left'=>'M20 12H4 m6-6-6 6 6 6',default=>'M4 12h16 m-6-6 6 6-6 6',
})
<svg {{ $attributes->merge(['class'=>'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $path }}"/></svg>
