@extends('layouts.app')
@section('title', 'Giriş Kartları')
@section('content')
<div class="print:hidden">
  <x-page-head :title="$class->label.' giriş kartları'" :back="[route('cards.index'), 'Sınıflar']"><button onclick="window.print()" class="btn btn-primary">🖨 Yazdır</button></x-page-head>
  <div class="alert alert-warn" style="margin-bottom:1rem">Bu sayfa yenilenirse kartlar yeniden üretilmez; yazdırmadan ayrılmayın. {{ $days }} gün geçerli, tek kullanımlık.</div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.8rem">@foreach($cards as $c)
  <div class="card" style="padding:.9rem;text-align:center;break-inside:avoid;border-style:dashed"><div class="qr" data-url="{{ $c['url'] }}" style="display:inline-block"></div>
    <div style="font-weight:700;margin-top:.4rem">{{ $c['name'] }}</div><div style="font-size:.75rem;color:var(--muted)">No {{ $c['no'] }}</div><div style="font-size:.7rem;color:#94a3b8">{{ $c['login'] }}</div></div>@endforeach</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>document.querySelectorAll('.qr').forEach(e => new QRCode(e, {text: e.dataset.url, width: 120, height: 120}));</script>
@endsection
