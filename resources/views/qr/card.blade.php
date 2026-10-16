@extends('layouts.plain')
@section('title', 'Giriş')
@section('content')
<form method="POST" action="{{ route('card.use', $token) }}" class="card form-card" style="margin:3rem auto;max-width:380px;text-align:center">@csrf
<div style="font-size:3.6rem">🎓</div><h1 style="margin:.3rem 0;font-size:1.35rem;font-weight:800">Merhaba {{ $name }}</h1><p class="help" style="margin:0 0 1.1rem">Bu kartla giriş yapmak istiyor musun?</p>
<button class="btn btn-primary" style="width:100%;justify-content:center">Giriş yap</button></form>
@endsection
