@extends('layouts.app')
@section('title', $course->name)
@section('content')
<div class="p-head">
  <h1 style="margin:0">{{ $course->name }}</h1>
  <div class="p-tags">@if($course->category)<span class="badge">{{ $course->category }}</span>@endif @if($course->difficulty)<span class="badge badge-warn">{{ $course->difficulty }}</span>@endif @if($course->description)<span style="color:var(--muted);font-size:.9rem">{{ $course->description }}</span>@endif</div>
</div>
@if(!empty($course->curriculum['kazanimlar']))
<details class="p-goals"><summary class="cursor-pointer font-semibold">Kazanımlar</summary>
  <ul class="list-disc pl-5 mt-2">@foreach($course->curriculum['kazanimlar'] as $k)<li>{{ $k }}</li>@endforeach</ul>
  @if(!empty($course->curriculum['etkinlikler']))<div class="font-semibold mt-2">Etkinlikler</div><ul class="list-disc pl-5">@foreach($course->curriculum['etkinlikler'] as $k)<li>{{ $k }}</li>@endforeach</ul>@endif
</details>
@endif
@if($course->slides->isEmpty())<p class="text-slate-500">Bu derste henüz slayt yok.</p>@else
<style>
.rich h1,.rich h2,.rich h3,.rich h4{font-weight:700;margin:.6em 0 .3em}.rich h1{font-size:1.6rem}.rich h2{font-size:1.35rem}.rich h3{font-size:1.15rem}
.rich p{margin:.5em 0}.rich ul{list-style:disc;padding-left:1.4rem;margin:.5em 0}.rich ol{list-style:decimal;padding-left:1.4rem;margin:.5em 0}
.rich a{color:#4f46e5;text-decoration:underline}.rich img{max-width:100%;height:auto;border-radius:.5rem;margin:.5em 0}.rich blockquote{border-left:4px solid #cbd5e1;padding-left:.8rem;color:#475569}
.rich table{border-collapse:collapse}.rich td,.rich th{border:1px solid #cbd5e1;padding:.3rem .6rem}.rich pre{background:#0f172a;color:#86efac;padding:.8rem;border-radius:.5rem;overflow:auto}
</style>
<div class="p-wrap"><div class="xpbar p-bar"><i id="pbar" style="width:0"></i></div>
<div id="player" class="p-card">
@foreach($course->slides as $i => $s)
@php($c = $s->content ?? [])
<section class="slide hidden" data-i="{{ $i }}">
    @if($s->title)<h2 class="p-title">{{ $s->title }}</h2>@endif
    @if($s->type === 'image' && !empty($c['url']))<img src="{{ $c['url'] }}" class="max-w-full mb-3 rounded" alt="">@endif
    @if($s->type === 'video' && !empty($c['url']))
        @php($embed = \App\Support\VideoEmbed::embedUrl($c['url']))
        @if($embed)<iframe src="{{ $embed }}" class="w-full mb-3 rounded" style="aspect-ratio:16/9" allowfullscreen loading="lazy" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-presentation"></iframe>
        @else<video src="{{ $c['url'] }}" controls class="max-w-full mb-3"></video>@endif
    @endif
    @if($s->type === 'code')<pre class="bg-slate-900 text-green-300 p-4 rounded overflow-auto">{{ $c['body'] ?? '' }}</pre>
    @elseif($s->type === 'quiz')
        @php($mode = $c['mode'] ?? 'choice')
        <div class="quiz" data-mode="{{ $mode }}">
        <p class="p-q">{{ $c['question'] ?? '' }}</p>
        @if($mode === 'short')
            <input type="text" class="short-in field" style="margin-bottom:.7rem" placeholder="Cevabını yaz…" autocomplete="off" data-answers="{{ json_encode($c['answers'] ?? [], JSON_UNESCAPED_UNICODE) }}">
            <button type="button" class="short-ck btn btn-primary">Kontrol et</button>
        @elseif($mode === 'matching')
            @php($rights = collect($c['pairs'] ?? [])->pluck(1)->values())
            @foreach($c['pairs'] ?? [] as $pi => $pair)
                <div class="p-pair"><div class="p-left">{{ $pair[0] }}</div>
                <select class="match-in field" style="flex:1" data-ok="{{ $pair[1] }}"><option value="">Seç…</option>@foreach($rights->shuffle() as $r)<option>{{ $r }}</option>@endforeach</select></div>
            @endforeach
            <button type="button" class="match-ck btn btn-primary">Kontrol et</button>
        @else
            @foreach($c['options'] ?? [] as $oi => $opt)
                <button type="button" class="opt q-opt c{{ $oi % 6 }}" data-ok="{{ $oi === ($c['answer'] ?? 0) ? 1 : 0 }}"><i>{{ ['▲','◆','●','■','★','♥'][$oi % 6] }}</i><span>{{ $opt }}</span></button>
            @endforeach
        @endif
        <div class="quiz-msg"></div></div>
    @else
        @if(!empty($c['html']))<div class="rich">{!! $c['html'] !!}</div>@else<div class="whitespace-pre-line">{{ $c['body'] ?? '' }}</div>@endif
    @endif
    @if(!empty($c['note']))<div class="p-note"><b>📌 Yönerge:</b> {{ $c['note'] }}</div>@endif
</section>
@endforeach
<div class="p-nav">
    <button id="prev" class="btn btn-ghost">← Geri</button>
    <span id="counter" class="p-count"></span>
    <button id="next" class="btn btn-primary">İleri →</button>
</div>
@if($progress)
<form id="finish" method="POST" action="{{ route('student.courses.complete', $course) }}" class="hidden p-finish">@csrf
<button class="btn btn-primary" style="background:linear-gradient(135deg,#22c55e,#14b8a6);padding:.9rem 1.6rem;font-size:1rem">🎉 Dersi tamamla ve XP kazan</button></form>
@endif
</div></div>
<script>
(() => {
  const slides = [...document.querySelectorAll('.slide')];
  let i = {{ (int) ($progress->slide_index ?? 0) }};
  i = Math.min(i, slides.length - 1);
  const save = () => @if($progress)fetch(@json(route('student.courses.progress', $course)), {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())}, body:JSON.stringify({slide_index:i})}); @else null; @endif
  const show = () => {
    slides.forEach((s, k) => s.classList.toggle('hidden', k !== i));
    document.getElementById('counter').textContent = (i + 1) + ' / ' + slides.length;
    const pb = document.getElementById('pbar'); if (pb) pb.style.width = ((i + 1) / slides.length * 100) + '%';
    slides[i].style.animation = 'none'; void slides[i].offsetWidth; slides[i].style.animation = 'rise .45s cubic-bezier(.2,.8,.2,1) both';
    const f = document.getElementById('finish'); if (f) f.classList.toggle('hidden', i !== slides.length - 1);
  };
  document.getElementById('prev').onclick = () => { if (i > 0) { i--; show(); save(); } };
  document.getElementById('next').onclick = () => { if (i < slides.length - 1) { i++; show(); save(); } };

  const norm = s => String(s).toLocaleLowerCase('tr').replace(/\s+/g, ' ').trim();
  const say = (el, ok, msg) => { const m = el.closest('.quiz').querySelector('.quiz-msg'); m.textContent = msg; m.className = 'quiz-msg mt-2 text-sm font-medium ' + (ok ? 'text-green-700' : 'text-red-700'); };
  document.querySelectorAll('.opt').forEach(b => b.onclick = () => {
    const ok = b.dataset.ok === '1';
    if (ok) { b.parentElement.querySelectorAll('.opt').forEach(o => o !== b && o.classList.add('dim')); b.classList.add('right'); if (window.UI) UI.celebrate({}); } else { b.classList.add('dim', 'shake'); setTimeout(() => b.classList.remove('shake'), 450); }
    say(b, ok, ok ? '🎉 Doğru!' : 'Tekrar dene!');
  });
  document.querySelectorAll('.short-ck').forEach(b => b.onclick = () => {
    const inp = b.parentElement.querySelector('.short-in'), ok = JSON.parse(inp.dataset.answers).map(norm).includes(norm(inp.value));
    inp.classList.toggle('bg-green-100', ok); inp.classList.toggle('bg-red-100', !ok); say(b, ok, ok ? 'Doğru!' : 'Tekrar dene.');
  });
  document.querySelectorAll('.match-ck').forEach(b => b.onclick = () => {
    const sels = [...b.parentElement.querySelectorAll('.match-in')]; let good = 0;
    sels.forEach(s => { const ok = s.value === s.dataset.ok; if (ok) good++; s.classList.toggle('bg-green-100', ok); s.classList.toggle('bg-red-100', !ok); });
    say(b, good === sels.length, good === sels.length ? 'Hepsi doğru!' : good + ' / ' + sels.length + ' doğru, tekrar dene.');
  });
  document.addEventListener('keydown', e => { if (e.target.closest('input,textarea,select')) return; if (e.key === 'ArrowRight') document.getElementById('next').click(); if (e.key === 'ArrowLeft') document.getElementById('prev').click(); });
  show();
})();
</script>
@endif
@endsection
