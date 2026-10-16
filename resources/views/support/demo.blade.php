@extends('layouts.plain')
@section('title', 'Demo Talebi')
@section('content')
<form method="POST" action="{{ route('demo.store') }}" class="max-w-md mx-auto bg-white p-8 rounded-xl shadow space-y-3">@csrf
<h1 class="text-xl font-bold">Demo talep et</h1>
@if(session('ok'))<p class="p-2 bg-green-100 text-green-800 rounded">{{ session('ok') }}</p>@endif
@if($errors->any())<p class="p-2 bg-red-100 text-red-800 rounded">{{ $errors->first() }}</p>@endif
<input name="name" value="{{ old('name') }}" placeholder="Ad Soyad / Kurum" class="w-full border rounded px-3 py-2" required>
<input name="email" type="email" value="{{ old('email') }}" placeholder="E-posta" class="w-full border rounded px-3 py-2" required>
<textarea name="message" rows="4" placeholder="Mesajınız" class="w-full border rounded px-3 py-2" required>{{ old('message') }}</textarea>
<input name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
<button class="w-full bg-indigo-600 text-white rounded py-2">Gönder</button>
<p class="text-xs text-slate-500">Göndererek <a class="underline" href="{{ route('legal.privacy') }}">gizlilik politikasını</a> kabul etmiş olursunuz.</p></form>
@endsection
