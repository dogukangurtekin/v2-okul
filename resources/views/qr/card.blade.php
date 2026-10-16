@extends('layouts.plain')
@section('title', 'Giriş')
@section('content')
<form method="POST" action="{{ route('card.use', $token) }}" class="max-w-sm mx-auto bg-white p-8 rounded-xl shadow text-center space-y-4">@csrf
<div class="text-5xl">🎓</div><h1 class="text-xl font-bold">Merhaba {{ $name }}</h1><p class="text-slate-500">Bu kartla giriş yapmak istiyor musun?</p>
<button class="w-full bg-indigo-600 text-white rounded py-2">Giriş yap</button></form>
@endsection
