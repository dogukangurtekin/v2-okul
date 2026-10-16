@extends('layouts.app')
@section('title', 'Kullanıcılar')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Kullanıcılar <span class="text-base text-slate-400">yönetici ve öğretmenler</span></h1><a href="{{ route('users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Yeni kullanıcı</a></div>
<form class="flex gap-2 mb-4"><input name="q" value="{{ request('q') }}" placeholder="Ad veya e-posta" class="border rounded px-3 py-2">
<select name="role" class="border rounded px-3 py-2 bg-white"><option value="">Tüm roller</option><option value="admin" @selected(request('role')==='admin')>Yönetici</option><option value="teacher" @selected(request('role')==='teacher')>Öğretmen</option></select><button class="bg-slate-700 text-white px-4 rounded">Ara</button></form>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Ad</th><th>E-posta</th><th>Rol</th><th>Branş</th><th>Durum</th><th></th></tr>
@foreach($users as $u)
<tr class="border-b"><td class="p-3">{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->role->name }}</td><td>{{ $u->teacher?->branch }}</td><td>{{ $u->is_active ? 'Etkin' : 'Pasif' }}</td>
<td class="text-right p-3 space-x-2 whitespace-nowrap">
@if($u->teacher)<a class="text-slate-600" href="{{ route('users.classes', $u) }}">Sınıflar</a>@endif
<a class="text-indigo-600" href="{{ route('users.edit', $u) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('users.password', $u) }}" onsubmit="return confirm('Geçici şifre üretilsin mi? Açık oturumları kapanır.')">@csrf<button class="text-amber-600">Şifre sıfırla</button></form>
@if($u->id !== auth()->id())<form class="inline" method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>@endif</td></tr>
@endforeach
</table>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
