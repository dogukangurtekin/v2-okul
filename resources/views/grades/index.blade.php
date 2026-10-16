@extends('layouts.app')
@section('title', 'Notlar')
@section('content')
<x-page-head title="Notlar" sub="Sınav ve ödev notları, ağırlıklı ortalamalar"><a href="{{ route('grades.sheet', ['class' => $class?->id]) }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Not gir</a></x-page-head>
<form class="toolbar">
<select name="class" class="field" style="width:auto"><option value="">Tüm sınıflarım</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select>
<select name="term" class="field" style="width:auto">@foreach($terms as $t)<option @selected($t === $term)>{{ $t }}</option>@endforeach</select>
<select name="type" class="field" style="width:auto"><option value="">Tüm türler</option>@foreach(config('school.exam_types') as $k => $t)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $t['label'] }}</option>@endforeach</select>
<button class="btn btn-primary">Filtrele</button></form>
@if($errors->any())<div class="alert alert-warn">{{ $errors->first() }}</div>@endif
@if($class && $students->isNotEmpty())
<div class="card card-pad" style="margin-bottom:1rem;max-width:960px"><b style="display:block;margin-bottom:.7rem">Ağırlıklı ortalamalar ({{ $term }})</b>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:.5rem">@foreach($students as $s)<div style="display:flex;justify-content:space-between;gap:.5rem;border:1px solid var(--line);border-radius:10px;padding:.4rem .7rem;font-size:.88rem"><span>{{ $s->user->name }}</span><b style="color:{{ ($avg[$s->id] ?? 100) < 50 ? 'var(--bad)' : 'inherit' }}">{{ $avg[$s->id] ?? '—' }}</b></div>@endforeach</div></div>
@endif
<div class="card tbl-wrap"><table class="tbl">
<thead><tr><th>Öğrenci</th><th>Tür</th><th>Başlık / Ders</th><th>Not</th><th>Tarih</th><th></th></tr></thead><tbody>
@forelse($grades as $g)
<tr><td><div class="who"><x-initials :name="$g->student->user->name" :size="30" /><b>{{ $g->student->user->name }}</b></div></td><td><span class="badge badge-brand">{{ config('school.exam_types')[$g->exam_type]['label'] }}</span></td><td>{{ $g->title }} <span style="color:#94a3b8">{{ $g->course?->name }}</span></td><td><b>{{ rtrim(rtrim(number_format($g->score, 2, ',', ''), '0'), ',') }}</b></td><td style="color:#94a3b8">{{ $g->exam_date->format('d.m.Y') }}</td>
<td><div class="ra"><a class="ibtn" href="{{ route('grades.edit', $g) }}">Düzenle</a><x-del :action="route('grades.destroy', $g)" icon /></div></td></tr>
@empty<tr><td colspan="6"><x-empty icon="📊" title="Bu dönemde not yok" /></td></tr>@endforelse
</tbody></table></div>
{{ $grades->links() }}
@endsection
