@extends('layouts.app')
@section('title', 'Quiz')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $quiz->exists ? 'Quizi düzenle' : 'Yeni quiz' }}</h1>
<form method="POST" action="{{ $quiz->exists ? route('quizzes.update', $quiz) : route('quizzes.store') }}" class="max-w-2xl space-y-3">
@csrf @if($quiz->exists) @method('PUT') @endif
<input name="title" value="{{ old('title', $quiz->title) }}" placeholder="Quiz başlığı" class="w-full border rounded px-3 py-2 bg-white" required>
<div id="qs" class="space-y-3"></div>
<button type="button" id="add" class="border rounded px-3 py-1 bg-white">+ Soru ekle</button>
<div><button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button></div>
</form>
<template id="tpl">
<div class="bg-white p-4 rounded-xl shadow space-y-2">
  <textarea name="questions[__I__][text]" rows="2" placeholder="Soru metni" class="w-full border rounded px-3 py-1" required></textarea>
  <textarea name="questions[__I__][options]" rows="4" placeholder="Seçenekler (her satıra bir tane)" class="w-full border rounded px-3 py-1" required></textarea>
  <div class="grid grid-cols-4 gap-2 text-sm">
    <label>Doğru (0'dan)<input type="number" min="0" name="questions[__I__][correct]" value="0" class="w-full border rounded px-2 py-1"></label>
    <label>Süre (sn)<input type="number" name="questions[__I__][duration]" value="20" class="w-full border rounded px-2 py-1"></label>
    <label>XP<input type="number" name="questions[__I__][xp]" value="10" class="w-full border rounded px-2 py-1"></label>
    <label class="flex items-end gap-1"><input type="checkbox" name="questions[__I__][double]" value="1"> 2x XP</label>
  </div>
  <button type="button" class="rm text-red-600 text-sm">Soruyu kaldır</button>
</div>
</template>
<script>
const existing = @json($quiz->exists ? $quiz->questions->map(fn($q) => ['text'=>$q->text,'options'=>implode("\n",$q->options),'correct'=>$q->correct_index,'duration'=>$q->duration_sec,'xp'=>$q->xp,'double'=>$q->double_xp])->values() : []);
let n = 0; const box = document.getElementById('qs');
function add(d) { const html = document.getElementById('tpl').innerHTML.replaceAll('__I__', n++); const w = document.createElement('div'); w.innerHTML = html; const el = w.firstElementChild;
  if (d) { const f = (k) => el.querySelector(`[name$="[${k}]"]`); f('text').value=d.text; f('options').value=d.options; f('correct').value=d.correct; f('duration').value=d.duration; f('xp').value=d.xp; f('double').checked=!!d.double; }
  el.querySelector('.rm').onclick = () => el.remove(); box.appendChild(el); }
document.getElementById('add').onclick = () => add();
(existing.length ? existing : [null]).forEach(add);
</script>
@endsection
