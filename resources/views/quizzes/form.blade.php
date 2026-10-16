@extends('layouts.app')
@section('title', 'Quiz')
@section('content')
<x-page-head :title="$quiz->exists ? 'Quizi düzenle' : 'Yeni quiz'" :back="[route('quizzes.index'), 'Quizler']" />
<form method="POST" action="{{ $quiz->exists ? route('quizzes.update', $quiz) : route('quizzes.store') }}" style="max-width:760px;display:grid;gap:1rem">
@csrf @if($quiz->exists) @method('PUT') @endif
<div class="card form-card" style="max-width:none"><x-fg label="Quiz başlığı" for="qt"><input id="qt" name="title" value="{{ old('title', $quiz->title) }}" placeholder="Örn. Döngüler tekrar" class="field" required></x-fg></div>
<div id="qs" style="display:grid;gap:1rem"></div>
<div class="form-actions" style="margin:0"><button type="button" id="add" class="btn btn-soft">＋ Soru ekle</button><button class="btn btn-primary">Kaydet</button></div>
</form>
<template id="tpl">
<div class="card card-pad" style="display:grid;gap:.8rem">
  <textarea name="questions[__I__][text]" rows="2" placeholder="Soru metni" class="field" required></textarea>
  <textarea name="questions[__I__][options]" rows="4" placeholder="Seçenekler (her satıra bir tane)" class="field" required></textarea>
  <div class="fgrid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
    <label class="fg"><span class="lbl">Doğru (0'dan)</span><input type="number" min="0" name="questions[__I__][correct]" value="0" class="field"></label>
    <label class="fg"><span class="lbl">Süre (sn)</span><input type="number" name="questions[__I__][duration]" value="20" class="field"></label>
    <label class="fg"><span class="lbl">XP</span><input type="number" name="questions[__I__][xp]" value="10" class="field"></label>
    <label class="chk" style="align-self:end;padding-bottom:.6rem"><input type="checkbox" name="questions[__I__][double]" value="1"> 2x XP</label>
  </div>
  <div><button type="button" class="rm btn btn-danger btn-sm">Soruyu kaldır</button></div>
</div>
</template>
<script>
const existing = @json($existing);
let n = 0; const box = document.getElementById('qs');
function add(d) { const html = document.getElementById('tpl').innerHTML.replaceAll('__I__', n++); const w = document.createElement('div'); w.innerHTML = html; const el = w.firstElementChild;
  if (d) { const f = (k) => el.querySelector(`[name$="[${k}]"]`); f('text').value=d.text; f('options').value=d.options; f('correct').value=d.correct; f('duration').value=d.duration; f('xp').value=d.xp; f('double').checked=!!d.double; }
  el.querySelector('.rm').onclick = () => el.remove(); box.appendChild(el); }
document.getElementById('add').onclick = () => add();
(existing.length ? existing : [null]).forEach(add);
</script>
@endsection
