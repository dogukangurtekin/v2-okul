@extends('layouts.app')
@section('title', 'Kullanıcı')
@section('content')
<x-page-head :title="$user->exists ? 'Kullanıcıyı düzenle' : 'Yeni kullanıcı'" :back="[route('users.index'), 'Kullanıcılar']" />
<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card form-card">@csrf @if($user->exists) @method('PUT') @endif
<div class="fgrid">
  <x-fg label="Ad soyad" for="name"><input id="name" class="field" name="name" value="{{ old('name', $user->name) }}" required></x-fg>
  <x-fg label="E-posta" for="email"><input id="email" class="field" name="email" type="email" value="{{ old('email', $user->email) }}" required></x-fg>
  <x-fg label="Rol" for="role"><select name="role" id="role" class="field"><option value="teacher" @selected($roleSlug==='teacher')>Öğretmen</option><option value="admin" @selected($roleSlug==='admin')>Yönetici</option></select></x-fg>
  <x-fg label="Şifre" for="password" :help="$user->exists ? 'Değişmeyecekse boş bırakın.' : 'En az 8 karakter.'"><input id="password" class="field" name="password" type="password" @required(!$user->exists)></x-fg>
</div>
<div id="t" class="fgrid" style="margin-top:1.05rem">
  <x-fg label="Branş" for="branch"><input id="branch" class="field" name="branch" value="{{ old('branch', $user->teacher?->branch) }}" placeholder="Bilişim"></x-fg>
  <x-fg label="Telefon" for="phone"><input id="phone" class="field" name="phone" value="{{ old('phone', $user->teacher?->phone) }}"></x-fg>
</div>
<label class="chk" style="margin-top:1.1rem"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Hesap etkin (pasif hesap giriş yapamaz)</label>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('users.index') }}">Vazgeç</a></div></form>
<script>const r=document.getElementById('role'),t=document.getElementById('t');const f=()=>t.style.display=r.value==='teacher'?'':'none';r.onchange=f;f();</script>
@endsection
