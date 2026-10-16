@extends('layouts.app')
@section('title', 'Yükleme sonucu')
@section('content')
<h1 class="text-2xl font-bold mb-2">Yükleme sonucu</h1>
<p class="mb-4"><b class="text-green-700">{{ count($created) }}</b> kayıt oluşturuldu, <b class="text-red-700">{{ count($rowErrors) }}</b> satır atlandı (toplam {{ $total }}).</p>
@if(count($rowErrors))
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 max-w-3xl"><div class="font-semibold mb-1">Atlanan satırlar</div>
@foreach($rowErrors as $e)<div class="text-sm">Satır {{ $e['line'] }}: {{ $e['message'] }}</div>@endforeach</div>
@endif
@if(count($created))
<div class="bg-amber-50 border border-amber-200 rounded p-3 mb-3 max-w-3xl text-sm print:hidden">⚠️ Şifreler bu sayfada yalnızca <b>bir kez</b> gösterilir. Yazdırın veya kaydedin; sayfadan ayrılınca tekrar görüntülenemez.</div>
<button onclick="window.print()" class="mb-3 bg-slate-700 text-white px-3 py-1 rounded print:hidden">Yazdır</button>
<table class="w-full max-w-3xl bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-2">Ad</th><th>{{ $type === 'student' ? 'No' : 'Branş' }}</th><th>Sınıf</th><th>Kullanıcı adı</th><th>Şifre</th></tr>
@foreach($created as $c)<tr class="border-b"><td class="p-2">{{ $c['name'] }}</td><td>{{ $c['no'] }}</td><td>{{ $c['class'] }}</td><td>{{ $c['username'] }}</td><td class="font-mono">{{ $c['password'] }}</td></tr>@endforeach
</table>
@endif
<a class="inline-block mt-4 text-indigo-600 print:hidden" href="{{ route('import.index') }}">← Yeni yükleme</a>
<style>@media print { aside, .print\:hidden { display:none !important } }</style>
@endsection
