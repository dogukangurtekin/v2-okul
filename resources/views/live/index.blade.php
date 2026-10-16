@extends('layouts.app')
@section('title', 'Canlı Oturumlar')
@section('content')
<h1 class="text-2xl font-bold mb-4">Canlı Oturumlar</h1>
<form method="POST" action="{{ route('live.store') }}" class="bg-white p-5 rounded-xl shadow max-w-2xl space-y-3 mb-8">@csrf
<div class="font-semibold">Yeni oturum</div>
<select name="type" id="type" class="w-full border rounded px-3 py-2">@foreach(\App\Models\LiveSession::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
<select name="school_class_id" class="w-full border rounded px-3 py-2"><option value="">Kodla herkes katılabilir</option>@foreach($classes as $c)<option value="{{ $c->id }}">Sadece {{ $c->label }}</option>@endforeach</select>
<div data-for="quiz"><select name="quiz_id" class="w-full border rounded px-3 py-2"><option value="">Quiz seçin</option>@foreach($quizzes as $q)<option value="{{ $q->id }}">{{ $q->title }}</option>@endforeach</select></div>
<div data-for="competition" class="space-y-2"><select name="game_slug" class="w-full border rounded px-3 py-2"><option value="">Oyun seçin</option>@foreach($games as $slug => $name)@if(in_array(config("runners.$slug.kind"), ['native','bundle']))<option value="{{ $slug }}">{{ $name }}</option>@endif @endforeach</select>
<div class="grid grid-cols-2 gap-2"><input name="level_from" type="number" min="1" placeholder="Seviye ↓ (1)" class="border rounded px-3 py-2"><input name="level_to" type="number" min="1" placeholder="Seviye ↑ (3)" class="border rounded px-3 py-2"></div></div>
<div data-for="typing"><textarea name="text" rows="2" placeholder="Yazılacak metin" class="w-full border rounded px-3 py-2">{{ $texts->last() }}</textarea></div>
<input name="duration" type="number" placeholder="Süre (sn) — yarışmalar için" class="w-full border rounded px-3 py-2" data-for="competition typing">
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Oluştur</button>
</form>
<script>
const sel = document.getElementById('type');
const sync = () => document.querySelectorAll('[data-for]').forEach(e => e.style.display = e.dataset.for.split(' ').includes(sel.value) ? '' : 'none');
sel.onchange = sync; sync();
</script>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Kod</th><th>Tür</th><th>Durum</th><th>Katılımcı</th><th>Sınıf</th><th></th></tr>
@foreach($sessions as $s)
<tr class="border-b"><td class="p-3 font-mono">{{ $s->join_code }}</td><td>{{ \App\Models\LiveSession::TYPES[$s->type] }}</td><td>{{ ['lobby'=>'Bekleme','live'=>'Canlı','finished'=>'Bitti'][$s->status] }}</td><td>{{ $s->participants_count }}</td><td>{{ $s->schoolClass?->label ?? 'Herkes' }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ $s->status === 'finished' ? route('live.report', $s) : route('live.show', $s) }}">{{ $s->status === 'finished' ? 'Rapor' : 'Yönet' }}</a>
<form class="inline" method="POST" action="{{ route('live.destroy', $s) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@endforeach
</table>
<div class="mt-4">{{ $sessions->links() }}</div>
@endsection
