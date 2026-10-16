@extends('layouts.app')
@section('title', 'QR giriş onayı')
@section('content')
<x-page-head title="QR giriş onayı" sub="Bir cihazın öğrenci hesabına girmesine izin verin" />
<div class="alert alert-warn" style="max-width:600px">
<b>Giriş isteyen cihaz:</b> {{ $t->request_ip }} · {{ \Illuminate\Support\Str::limit($t->request_ua, 90) }}<br>
Yalnızca bu cihaz <b>şu an karşınızdaki öğrencinin</b> cihazıysa onaylayın. Kod {{ $t->expires_at->diffForHumans() }} sona erer.</div>
<form method="POST" action="{{ route('qr.approve.do', $token) }}" class="card form-card" style="margin-top:1rem">@csrf
<x-fg label="Öğrenci"><select name="student_id" class="field" required><option value="">Öğrenci seçin</option>
@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->user->name }} — {{ $s->schoolClass?->label }} ({{ $s->student_no }})</option>@endforeach</select></x-fg>
<div class="form-actions"><button class="btn btn-primary">Girişi onayla</button></div></form>
@endsection
