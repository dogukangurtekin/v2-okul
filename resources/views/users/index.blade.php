@extends('layouts.app')
@section('title', 'Kullanıcılar')
@section('content')
<x-page-head title="Kullanıcılar" sub="Yönetici ve öğretmen hesapları">
  <a href="{{ route('import.index') }}" class="btn btn-ghost"><x-icon name="upload" style="width:16px;height:16px" />Toplu yükle</a>
  <a href="{{ route('users.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Yeni kullanıcı</a>
</x-page-head>
<form class="toolbar"><input name="q" value="{{ request('q') }}" placeholder="Ad veya e-posta ara…" class="field grow">
  <select name="role" class="field"><option value="">Tüm roller</option><option value="admin" @selected(request('role')==='admin')>Yönetici</option><option value="teacher" @selected(request('role')==='teacher')>Öğretmen</option></select><button class="btn btn-ghost">Filtrele</button></form>
<div class="card tbl-wrap">
<table class="tbl">
  <thead><tr><th>Kullanıcı</th><th>Rol</th><th>Branş</th><th>Durum</th><th></th></tr></thead>
  <tbody>
  @foreach($users as $u)
    <tr>
      <td><div class="who"><x-initials :name="$u->name" /><div><b>{{ $u->name }}</b><small>{{ $u->email }}</small></div></div></td>
      <td><span class="badge {{ $u->role->slug === 'admin' ? 'badge-brand' : '' }}">{{ $u->role->name }}</span></td>
      <td>{{ $u->teacher?->branch ?: '—' }}</td>
      <td><span class="badge {{ $u->is_active ? 'badge-ok' : 'badge-bad' }}">{{ $u->is_active ? 'Etkin' : 'Pasif' }}</span></td>
      <td><div class="ra">
        @if($u->teacher)<a class="ibtn" href="{{ route('users.classes', $u) }}">Sınıflar</a>@endif
        <a class="ibtn" href="{{ route('users.edit', $u) }}">Düzenle</a>
        <x-del :action="route('users.password', $u)" method="POST" confirm="Geçici şifre üretilsin mi? Açık oturumları kapanır." label="Şifre sıfırla" icon />
        @if($u->id !== auth()->id())<x-del :action="route('users.destroy', $u)" icon />@endif
      </div></td>
    </tr>
  @endforeach
  </tbody>
</table>
</div>
{{ $users->links() }}
@endsection
