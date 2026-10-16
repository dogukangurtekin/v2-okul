@extends('layouts.app')
@section('title', 'Dersler')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Dersler</h1>
<div class="flex gap-2">
<form method="POST" action="{{ route('courses.import') }}" enctype="multipart/form-data" class="flex gap-2">@csrf
<input type="file" name="files[]" accept=".coursepkg,.json" multiple class="text-sm" required title="v2 JSON paketi veya v1 .coursepkg"><button class="bg-slate-700 text-white px-3 rounded">İçe aktar</button></form>
<a href="{{ route('courses.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Yeni ders</a></div></div>
@if($r = session('import_report'))
<div class="mb-4 max-w-3xl bg-white rounded-xl shadow p-4 text-sm space-y-2">
  <div class="font-semibold">İçe aktarma raporu</div>
  @foreach($r['courses'] as $c)<div>✅ <a class="text-indigo-600" href="{{ route('courses.edit', $c['id']) }}">{{ $c['sub'] ? '↳ ' : '' }}{{ $c['name'] }}</a> — {{ $c['slides'] }} slayt{{ $c['quizzes'] ? ', '.$c['quizzes'].' soru' : '' }}@if($c['v1_slides'] !== null) <span class="text-slate-400">(v1'de {{ $c['v1_slides'] }} slayt)</span>@endif</div>@endforeach
  @foreach($r['errors'] as $e)<div class="text-red-700">❌ {{ $e }}</div>@endforeach
  @if($r['warnings'])<details class="bg-amber-50 border border-amber-200 rounded p-2"><summary class="cursor-pointer font-medium">⚠️ {{ count($r['warnings']) }} uyarı</summary>@foreach($r['warnings'] as $w)<div class="mt-1">{{ $w }}</div>@endforeach</details>@endif
</div>
@endif
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Kod</th><th>Ad</th><th>Slayt</th><th>Sınıf</th><th>Durum</th><th></th></tr>
@foreach($courses as $c)
<tr class="border-b"><td class="p-3">{{ $c->code }}</td><td>{{ $c->name }}</td><td>{{ $c->slides_count }}</td><td>{{ $c->classes_count }}</td><td>{{ $c->is_active ? 'Aktif' : 'Pasif' }}</td>
<td class="text-right p-3 space-x-2"><a class="text-slate-600" href="{{ route('courses.preview', $c) }}">Önizle</a><a class="text-slate-600" href="{{ route('courses.export', $c) }}">İndir</a><a class="text-indigo-600" href="{{ route('courses.edit', $c) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('courses.destroy', $c) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@endforeach
</table>
<div class="mt-4">{{ $courses->links() }}</div>
@endsection
