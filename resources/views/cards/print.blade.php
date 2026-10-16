@extends('layouts.app')
@section('title', 'Giriş Kartları')
@section('content')
<div class="print:hidden mb-4"><h1 class="text-2xl font-bold">{{ $class->label }} giriş kartları</h1>
<button onclick="window.print()" class="bg-slate-700 text-white px-3 py-1 rounded mt-2">Yazdır</button>
<p class="text-sm text-amber-700 mt-2">Bu sayfa yenilenirse kartlar yeniden üretilmez; yazdırmadan ayrılmayın. {{ $days }} gün geçerli, tek kullanımlık.</p></div>
<div class="grid grid-cols-3 gap-3">@foreach($cards as $i => $c)
<div class="bg-white border rounded-lg p-3 text-center break-inside-avoid"><div class="qr inline-block" data-url="{{ $c['url'] }}"></div>
<div class="font-semibold mt-1">{{ $c['name'] }}</div><div class="text-xs text-slate-500">No {{ $c['no'] }}</div><div class="text-xs text-slate-400">{{ $c['login'] }}</div></div>@endforeach</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>document.querySelectorAll('.qr').forEach(e => new QRCode(e, {text: e.dataset.url, width: 120, height: 120}));</script>
<style>@media print { aside, .print\:hidden { display:none !important } main { padding:0 } }</style>
@endsection
