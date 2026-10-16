@extends('layouts.plain')
@section('title', 'Demo Talebi')
@section('content')
<form method="POST" action="{{ route('demo.store') }}" class="card form-card" style="margin:2rem auto;max-width:460px">@csrf
<h1 style="margin:0 0 .3rem;font-size:1.4rem;font-weight:800">Demo talep et</h1><p class="help" style="margin:0 0 1rem">Platformu okulunuzda denemek için bize yazın.</p>
@if(session('ok'))<div class="alert alert-ok">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-bad">{{ $errors->first() }}</div>@endif
<div style="display:grid;gap:.8rem">
<input name="name" value="{{ old('name') }}" placeholder="Ad Soyad / Kurum" class="field" required>
<input name="email" type="email" value="{{ old('email') }}" placeholder="E-posta" class="field" required>
<textarea name="message" rows="4" placeholder="Mesajınız" class="field" required>{{ old('message') }}</textarea>
<input name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
<button class="btn btn-primary" style="justify-content:center">Gönder</button>
<p class="help" style="margin:0">Göndererek <a style="text-decoration:underline" href="{{ route('legal.privacy') }}">gizlilik politikasını</a> kabul etmiş olursunuz.</p></div></form>
@endsection
