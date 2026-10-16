@extends('layouts.app')
@section('title', 'Yükleme sonucu')
@section('content')
<x-page-head title="Yükleme sonucu" :back="[route('import.index'), 'Yeni yükleme']" />
<div class="kpi" style="max-width:560px">
  <div class="card"><b style="color:var(--ok)">{{ count($created) }}</b><span>kayıt oluşturuldu</span></div>
  <div class="card"><b style="color:{{ count($rowErrors) ? 'var(--bad)' : 'var(--muted)' }}">{{ count($rowErrors) }}</b><span>satır atlandı</span></div>
  <div class="card"><b>{{ $total }}</b><span>toplam satır</span></div>
</div>
@if(count($rowErrors))
<div class="alert alert-bad" style="max-width:820px;margin-bottom:1rem"><b>Atlanan satırlar</b>
@foreach($rowErrors as $e)<div style="margin-top:.25rem">Satır {{ $e['line'] }}: {{ $e['message'] }}</div>@endforeach</div>
@endif
@if(count($created))
<div class="alert alert-warn print:hidden" style="max-width:820px;margin-bottom:.8rem">⚠️ Şifreler bu sayfada yalnızca <b>bir kez</b> gösterilir. Yazdırın veya kaydedin; sayfadan ayrılınca tekrar görüntülenemez.</div>
<button onclick="window.print()" class="btn btn-primary print:hidden" style="margin-bottom:.8rem">🖨 Yazdır</button>
<div class="card tbl-wrap" style="max-width:820px"><table class="tbl">
  <thead><tr><th>Ad</th><th>{{ $type === 'student' ? 'No' : 'Branş' }}</th><th>Sınıf</th><th>Kullanıcı adı</th><th>Şifre</th></tr></thead>
  <tbody>@foreach($created as $c)<tr><td><b>{{ $c['name'] }}</b></td><td>{{ $c['no'] }}</td><td>{{ $c['class'] }}</td><td>{{ $c['username'] }}</td><td><code style="font-size:.95em;letter-spacing:.04em" class="font-mono">{{ $c['password'] }}</code></td></tr>@endforeach</tbody>
</table></div>
@endif
@endsection
