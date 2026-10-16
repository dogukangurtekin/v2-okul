@extends('layouts.app')
@section('title', 'Canlı Oturumlar')
@section('content')
<x-page-head title="Canlı Oturumlar" sub="Quiz, oyun yarışması ve klavye yarışı başlatın" />
<form method="POST" action="{{ route('live.store') }}" class="card form-card" style="margin-bottom:1.4rem">@csrf
<b style="display:block;margin-bottom:.9rem">⚡ Yeni oturum</b>
<div class="fgrid">
  <x-fg label="Oturum türü"><select name="type" id="type" class="field">@foreach(\App\Models\LiveSession::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></x-fg>
  <x-fg label="Katılım"><select name="school_class_id" class="field"><option value="">Kodla herkes katılabilir</option>@foreach($classes as $c)<option value="{{ $c->id }}">Sadece {{ $c->label }}</option>@endforeach</select></x-fg>
  <div class="fg full" data-for="quiz"><label class="lbl">Quiz</label><select name="quiz_id" class="field"><option value="">Quiz seçin</option>@foreach($quizzes as $q)<option value="{{ $q->id }}">{{ $q->title }}</option>@endforeach</select></div>
  <div class="fg full" data-for="competition"><label class="lbl">Oyun</label><select name="game_slug" class="field"><option value="">Oyun seçin</option>@foreach($games as $slug => $name)@if(in_array(config("runners.$slug.kind"), ['native','bundle']))<option value="{{ $slug }}">{{ $name }}</option>@endif @endforeach</select></div>
  <div class="fg" data-for="competition"><label class="lbl">İlk seviye</label><input name="level_from" type="number" min="1" placeholder="1" class="field"></div>
  <div class="fg" data-for="competition"><label class="lbl">Son seviye</label><input name="level_to" type="number" min="1" placeholder="3" class="field"></div>
  <div class="fg full" data-for="typing"><label class="lbl">Yazılacak metin</label><textarea name="text" rows="2" placeholder="Yazılacak metin" class="field">{{ $texts->last() }}</textarea></div>
  <div class="fg" data-for="competition typing"><label class="lbl">Süre (sn)</label><input name="duration" type="number" placeholder="Süre (sn) — yarışmalar için" class="field"></div>
</div>
<div class="form-actions"><button class="btn btn-primary">Oturumu oluştur</button></div>
</form>
<script>
const sel = document.getElementById('type');
const sync = () => document.querySelectorAll('[data-for]').forEach(e => e.style.display = e.dataset.for.split(' ').includes(sel.value) ? '' : 'none');
sel.onchange = sync; sync();
</script>
<div class="card tbl-wrap"><table class="tbl">
<thead><tr><th>Kod</th><th>Tür</th><th>Durum</th><th>Katılımcı</th><th>Sınıf</th><th></th></tr></thead>
<tbody>
@forelse($sessions as $s)
<tr><td><code style="font-weight:800;letter-spacing:.12em;font-size:1rem">{{ $s->join_code }}</code></td><td>{{ \App\Models\LiveSession::TYPES[$s->type] }}</td>
<td><span class="badge {{ ['lobby'=>'badge-warn','live'=>'badge-ok','finished'=>''][$s->status] }}">{{ ['lobby'=>'Bekleme','live'=>'● Canlı','finished'=>'Bitti'][$s->status] }}</span></td><td>{{ $s->participants_count }}</td><td>{{ $s->schoolClass?->label ?? 'Herkes' }}</td>
<td><div class="ra"><a class="ibtn" href="{{ $s->status === 'finished' ? route('live.report', $s) : route('live.show', $s) }}">{{ $s->status === 'finished' ? 'Rapor' : 'Yönet' }}</a><x-del :action="route('live.destroy', $s)" icon /></div></td></tr>
@empty
<tr><td colspan="6"><x-empty icon="⚡" title="Henüz oturum yok" text="Yukarıdan ilk canlı oturumunuzu oluşturun." /></td></tr>
@endforelse
</tbody></table></div>
{{ $sessions->links() }}
@endsection
