@extends('layouts.app')
@section('title', 'Not girişi')
@section('content')
<x-page-head title="Not Girişi" sub="Bir sınıf seçip toplu not girin"><a class="btn btn-ghost" href="{{ route('grades.index') }}">Notlar →</a></x-page-head>
<form class="toolbar"><select name="class" class="field" style="width:auto" onchange="this.form.submit()" required><option value="">Sınıf seçin</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected($class?->id === $c->id)>{{ $c->label }}</option>@endforeach</select></form>
@if($class)
<form method="POST" action="{{ route('grades.store') }}" style="max-width:860px;display:grid;gap:1rem">@csrf
<input type="hidden" name="school_class_id" value="{{ $class->id }}">
<div class="card form-card" style="max-width:none"><div class="fgrid">
<x-fg label="Sınav türü"><select name="exam_type" class="field">@foreach(config('school.exam_types') as $k => $t)<option value="{{ $k }}">{{ $t['label'] }} (ağırlık {{ $t['weight'] }})</option>@endforeach</select></x-fg>
<x-fg label="Ders"><select name="course_id" class="field"><option value="">Ders (isteğe bağlı)</option>@foreach($courses as $co)<option value="{{ $co->id }}">{{ $co->name }}</option>@endforeach</select></x-fg>
<x-fg label="Başlık"><input name="title" maxlength="120" placeholder="Başlık (örn. 1. Yazılı)" class="field"></x-fg>
<x-fg label="Sınav tarihi"><input type="date" name="exam_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="field" required></x-fg>
<x-fg label="Dönem" full><input name="term" value="{{ $term }}" class="field" required></x-fg></div></div>
<div class="card tbl-wrap"><table class="tbl"><tbody>
@foreach($students as $s)<tr><td><div class="who"><x-initials :name="$s->user->name" :size="30" /><div><b>{{ $s->user->name }}</b><small>{{ $s->student_no }}</small></div></div></td><td style="width:140px"><input name="scores[{{ $s->id }}]" inputmode="decimal" placeholder="0–100" class="field"></td></tr>@endforeach
</tbody></table></div>
<p class="help">Boş bırakılan öğrenciye not eklenmez. Virgül veya nokta kullanabilirsiniz.</p>
<div class="form-actions" style="margin:0"><button class="btn btn-primary">Kaydet</button></div></form>
@endif
@endsection
