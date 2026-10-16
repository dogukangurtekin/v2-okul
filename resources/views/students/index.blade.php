@extends('layouts.app')
@section('title', 'Öğrenciler')
@section('content')
@php($admin = auth()->user()->hasRole('admin'))
<x-page-head title="Öğrenciler" sub="{{ $students->total() }} öğrenci">
  @if($admin)<a href="{{ route('import.index') }}" class="btn btn-ghost"><x-icon name="upload" style="width:16px;height:16px" />Toplu yükle</a>@endif
  <a href="{{ route('students.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Yeni öğrenci</a>
</x-page-head>
<form class="toolbar"><input name="q" value="{{ request('q') }}" placeholder="Ad veya numara ara…" class="field grow">
  <select name="class" class="field"><option value="">Tüm sınıflar</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class') == $c->id)>{{ $c->label }}</option>@endforeach</select>
  <button class="btn btn-ghost">Filtrele</button>@if(request()->hasAny(['q', 'class']))<a href="{{ route('students.index') }}" class="btn btn-ghost">Temizle</a>@endif</form>
<div class="card tbl-wrap">
<table class="tbl">
  <thead><tr>@if($admin)<th style="width:40px"><input type="checkbox" aria-label="Tümünü seç" onclick="document.querySelectorAll('.sel').forEach(c=>c.checked=this.checked)"></th>@endif<th>Öğrenci</th><th>No</th><th>Sınıf</th><th>XP</th><th></th></tr></thead>
  <tbody>
  @forelse($students as $s)
    <tr>@if($admin)<td><input type="checkbox" class="sel" form="bulk" name="ids[]" value="{{ $s->id }}" aria-label="Seç"></td>@endif
      <td><div class="who"><x-initials :name="$s->user->name" /><div><b>{{ $s->user->name }}</b><small>{{ $s->user->email }}</small></div></div></td>
      <td>{{ $s->student_no }}</td>
      <td>@if($s->schoolClass)<span class="badge badge-brand">{{ $s->schoolClass->label }}</span>@else<span class="badge">—</span>@endif</td>
      <td><span class="badge badge-warn">{{ $s->xp_total }} XP</span></td>
      <td><div class="ra"><a class="ibtn" href="{{ route('reports.show', $s) }}">Karne</a><a class="ibtn" href="{{ route('students.edit', $s) }}">Düzenle</a><x-del :action="route('students.destroy', $s)" icon /></div></td>
    </tr>
  @empty
    <tr><td colspan="{{ $admin ? 6 : 5 }}"><x-empty icon="🎓" title="Öğrenci bulunamadı" text="Filtreleri değiştirin ya da yeni öğrenci ekleyin."><a href="{{ route('students.create') }}" class="btn btn-primary">Öğrenci ekle</a></x-empty></td></tr>
  @endforelse
  </tbody>
</table>
</div>
@if($admin)<form id="bulk" method="POST" action="{{ route('students.destroy.selected') }}" onsubmit="return confirm('Seçili öğrenciler silinsin mi?')" style="margin-top:.8rem">@csrf<button class="btn btn-danger btn-sm">Seçilileri sil</button></form>@endif
{{ $students->links() }}
@endsection
