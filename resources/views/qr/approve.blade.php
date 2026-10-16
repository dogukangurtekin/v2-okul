@extends('layouts.app')
@section('title', 'QR giriş onayı')
@section('content')
<h1 class="text-2xl font-bold mb-2">QR giriş onayı</h1>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 max-w-xl mb-4 text-sm">
<b>Giriş isteyen cihaz:</b> {{ $t->request_ip }} · {{ \Illuminate\Support\Str::limit($t->request_ua, 90) }}<br>
Yalnızca bu cihaz <b>şu an karşınızdaki öğrencinin</b> cihazıysa onaylayın. Kod {{ $t->expires_at->diffForHumans() }} sona erer.</div>
<form method="POST" action="{{ route('qr.approve.do', $token) }}" class="bg-white p-5 rounded-xl shadow max-w-xl space-y-3">@csrf
<select name="student_id" class="w-full border rounded px-3 py-2" required><option value="">Öğrenci seçin</option>
@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->user->name }} — {{ $s->schoolClass?->label }} ({{ $s->student_no }})</option>@endforeach</select>
<button class="bg-green-600 text-white px-4 py-2 rounded">Girişi onayla</button></form>
@endsection
