@extends('layouts.app')
@section('title', 'Sınıf atama')
@section('content')
<x-page-head :title="$user->name.' — sınıf atama'" sub="İşaretli sınıfların öğretmeni bu kişi olur. Başka öğretmene ait bir sınıfı işaretlerseniz devralır." :back="[route('users.index'), 'Kullanıcılar']" />
<form method="POST" action="{{ route('users.classes.save', $user) }}" class="card form-card" style="margin-bottom:1rem">@csrf
<div class="chip-pick" style="flex-direction:column;align-items:stretch">@foreach($classes as $c)
<label style="justify-content:space-between;border-radius:12px;{{ $c->teacher_id && $c->teacher_id !== $teacher->id ? 'background:var(--tint-warn);border-color:var(--tint-warn-bd)' : '' }}">
  <span><input type="checkbox" name="classes[]" value="{{ $c->id }}" @checked($c->teacher_id === $teacher->id)> <b>{{ $c->label }}</b></span>
  <span style="font-weight:500;font-size:.78rem;color:var(--muted)">{{ $c->students_count }} öğrenci{{ $c->teacher_id && $c->teacher_id !== $teacher->id ? ' · şu an: '.$c->teacher?->user?->name : '' }}</span></label>@endforeach</div>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button></div></form>
<form method="POST" action="{{ route('users.classes.grade', $user) }}" class="card form-card" style="display:flex;gap:.7rem;align-items:center;flex-wrap:wrap">@csrf
<b style="font-size:.9rem">Kademeye göre hepsini ata:</b><input type="number" name="grade_level" min="1" max="12" class="field" style="width:100px" placeholder="örn. 6" required><button class="btn btn-ghost">Ata</button></form>
@endsection
