@extends('layouts.app')
@section('title', 'Kullanıcı')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $user->exists ? 'Kullanıcıyı düzenle' : 'Yeni kullanıcı' }}</h1>
<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="bg-white p-6 rounded-xl shadow max-w-lg space-y-3">@csrf @if($user->exists) @method('PUT') @endif
<input name="name" value="{{ old('name', $user->name) }}" placeholder="Ad Soyad" class="w-full border rounded px-3 py-2" required>
<input name="email" type="email" value="{{ old('email', $user->email) }}" placeholder="E-posta" class="w-full border rounded px-3 py-2" required>
<select name="role" id="role" class="w-full border rounded px-3 py-2"><option value="teacher" @selected($roleSlug==='teacher')>Öğretmen</option><option value="admin" @selected($roleSlug==='admin')>Yönetici</option></select>
<input name="password" type="password" placeholder="{{ $user->exists ? 'Yeni şifre (değişmeyecekse boş)' : 'Şifre (en az 8)' }}" class="w-full border rounded px-3 py-2" @required(!$user->exists)>
<div id="t" class="space-y-3"><input name="branch" value="{{ old('branch', $user->teacher?->branch) }}" placeholder="Branş" class="w-full border rounded px-3 py-2"><input name="phone" value="{{ old('phone', $user->teacher?->phone) }}" placeholder="Telefon" class="w-full border rounded px-3 py-2"></div>
<label class="flex gap-2 items-center"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Etkin</label>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button></form>
<script>const r=document.getElementById('role'),t=document.getElementById('t');const f=()=>t.style.display=r.value==='teacher'?'':'none';r.onchange=f;f();</script>
@endsection
