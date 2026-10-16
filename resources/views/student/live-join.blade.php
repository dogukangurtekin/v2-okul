@extends('layouts.app')
@section('title', 'Canlı Katıl')
@section('content')
<h1 class="text-2xl font-bold mb-4">Canlı Oturuma Katıl</h1>
<form method="POST" action="{{ route('student.live.join') }}" class="bg-white p-6 rounded-xl shadow max-w-sm space-y-3">@csrf
<input name="code" value="{{ old('code') }}" maxlength="8" placeholder="KOD" class="w-full border rounded px-3 py-3 text-center text-2xl font-mono uppercase tracking-widest" required>
<button class="w-full bg-indigo-600 text-white rounded py-2">Katıl</button></form>
@if($open->isNotEmpty())
<h2 class="font-bold mt-6 mb-2">Sınıfınıza açık oturumlar</h2>
@foreach($open as $s)<a class="block bg-white rounded-xl shadow p-3 mb-2 max-w-sm" href="{{ route('student.live.joinid', $s) }}">{{ \App\Models\LiveSession::TYPES[$s->type] }} · {{ $s->join_code }}</a>@endforeach
@endif
@endsection
