@extends('layouts.app')
@section('title', 'Ödev')
@section('content')
<x-page-head :title="$assignment->exists ? 'Ödevi düzenle' : 'Ödev ver'" :back="[route('assignments.index'), 'Ödevler']" />
<form method="POST" action="{{ $assignment->exists ? route('assignments.update', $assignment) : route('assignments.store') }}" class="card form-card">
@csrf @if($assignment->exists) @method('PUT') @endif
<div class="fgrid">
  <x-fg label="Ödev türü" for="type"><select name="type" id="type" class="field"><option value="course" @selected(old('type', $assignment->type) === 'course')>📖 Ders ödevi</option><option value="game" @selected(old('type', $assignment->type) === 'game')>🎮 Oyun / etkinlik ödevi</option></select></x-fg>
  <div class="fg" data-t="course"><label class="lbl" for="course_id">Ders</label><select id="course_id" name="course_id" class="field"><option value="">Ders seçin</option>@foreach($courses as $c)<option value="{{ $c->id }}" @selected(old('course_id', $assignment->course_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
  <div class="fg" data-t="game"><label class="lbl" for="game_slug">Oyun</label><select id="game_slug" name="game_slug" class="field"><option value="">Oyun seçin</option>@foreach($games as $slug => $name)<option value="{{ $slug }}" @selected(old('game_slug', $assignment->game_slug) === $slug)>{{ config("runners.$slug.emoji") }} {{ $name }}</option>@endforeach</select></div>
  <x-fg label="Başlık" for="title" full><input id="title" class="field" name="title" value="{{ old('title', $assignment->title) }}" placeholder="Örn. 3. haftanın görevi" required></x-fg>
  <x-fg label="Açıklama" for="details" full><textarea id="details" class="field" name="details" rows="3">{{ old('details', $assignment->details) }}</textarea></x-fg>
  <x-fg label="Son tarih" for="due_date"><input id="due_date" class="field" name="due_date" type="date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d')) }}"></x-fg>
  <x-fg label="Kazanılacak XP" for="xp_reward"><input id="xp_reward" class="field" name="xp_reward" type="number" min="0" value="{{ old('xp_reward', $assignment->xp_reward) }}" required></x-fg>
  <div class="fg" data-t="game"><label class="lbl" for="level_from">İlk seviye</label><input id="level_from" class="field" name="level_from" type="number" min="1" value="{{ old('level_from', $assignment->level_from) }}"></div>
  <div class="fg" data-t="game"><label class="lbl" for="level_to">Son seviye</label><input id="level_to" class="field" name="level_to" type="number" min="1" value="{{ old('level_to', $assignment->level_to) }}"><small class="help">Seviye aralığı bitince ödev otomatik tamamlanır.</small></div>
  <div class="fg full"><span class="lbl">Sınıflar</span><div class="chip-pick">@foreach($classes as $c)<label><input type="checkbox" name="classes[]" value="{{ $c->id }}" @checked(in_array($c->id, old('classes', $assignment->classes->pluck('id')->all())))> {{ $c->label }}</label>@endforeach</div></div>
</div>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('assignments.index') }}">Vazgeç</a></div>
</form>
<script>
const ty = document.getElementById('type');
const sync = () => document.querySelectorAll('[data-t]').forEach(e => e.style.display = e.dataset.t === ty.value ? '' : 'none');
ty.onchange = sync; sync();
</script>
@endsection
