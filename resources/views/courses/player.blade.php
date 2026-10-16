@extends('layouts.app')
@section('title', $course->name)
@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $course->name }}</h1>
<div class="mb-4 text-sm text-slate-500 flex flex-wrap gap-2 items-center">
  @if($course->category)<span class="bg-slate-200 rounded px-2 py-0.5">{{ $course->category }}</span>@endif
  @if($course->difficulty)<span class="bg-amber-100 rounded px-2 py-0.5">{{ $course->difficulty }}</span>@endif
  @if($course->description)<span>{{ $course->description }}</span>@endif
</div>
@if(!empty($course->curriculum['kazanimlar']))
<details class="mb-4 max-w-3xl bg-indigo-50 rounded-xl p-3 text-sm"><summary class="cursor-pointer font-semibold">Kazanımlar</summary>
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
<div id="player" class="bg-white rounded-xl shadow p-6 max-w-3xl">
@foreach($course->slides as $i => $s)
@php($c = $s->content ?? [])
<section class="slide hidden" data-i="{{ $i }}">
    @if($s->title)<h2 class="text-xl font-semibold mb-3">{{ $s->title }}</h2>@endif
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
        <p class="font-medium mb-3">{{ $c['question'] ?? '' }}</p>
        @if($mode === 'short')
            <input type="text" class="short-in w-full border rounded px-3 py-2 mb-2" placeholder="Cevabını yaz…" autocomplete="off" data-answers="{{ json_encode($c['answers'] ?? [], JSON_UNESCAPED_UNICODE) }}">
            <button type="button" class="short-ck bg-indigo-600 text-white px-3 py-1 rounded">Kontrol et</button>
        @elseif($mode === 'matching')
            @php($rights = collect($c['pairs'] ?? [])->pluck(1)->values())
            @foreach($c['pairs'] ?? [] as $pi => $pair)
                <div class="flex gap-2 items-center mb-2"><div class="flex-1 border rounded px-3 py-2 bg-slate-50">{{ $pair[0] }}</div>
                <select class="match-in flex-1 border rounded px-2 py-2" data-ok="{{ $pair[1] }}"><option value="">Seç…</option>@foreach($rights->shuffle() as $r)<option>{{ $r }}</option>@endforeach</select></div>
            @endforeach
            <button type="button" class="match-ck bg-indigo-600 text-white px-3 py-1 rounded">Kontrol et</button>
        @else
            @foreach($c['options'] ?? [] as $oi => $opt)
                <button type="button" class="opt block w-full text-left border rounded px-3 py-2 mb-2" data-ok="{{ $oi === ($c['answer'] ?? 0) ? 1 : 0 }}">{{ $opt }}</button>
            @endforeach
        @endif
        <div class="quiz-msg mt-2 text-sm font-medium"></div></div>
    @else
        @if(!empty($c['html']))<div class="rich">{!! $c['html'] !!}</div>@else<div class="whitespace-pre-line">{{ $c['body'] ?? '' }}</div>@endif
    @endif
    @if(!empty($c['note']))<div class="mt-3 text-sm bg-amber-50 border border-amber-200 rounded p-2"><b>Yönerge:</b> {{ $c['note'] }}</div>@endif
</section>
@endforeach
<div class="flex justify-between mt-6">
    <button id="prev" class="px-4 py-2 border rounded">Geri</button>
    <span id="counter" class="self-center text-sm text-slate-500"></span>
    <button id="next" class="px-4 py-2 bg-indigo-600 text-white rounded">İleri</button>
</div>
@if($progress)
<form id="finish" method="POST" action="{{ route('student.courses.complete', $course) }}" class="hidden mt-4 text-right">@csrf
<button class="bg-green-600 text-white px-4 py-2 rounded">Dersi tamamla</button></form>
@endif
</div>
<script>
(() => {
  const slides = [...document.querySelectorAll('.slide')];
  let i = {{ (int) ($progress->slide_index ?? 0) }};
  i = Math.min(i, slides.length - 1);
  const save = () => @if($progress)fetch(@json(route('student.courses.progress', $course)), {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())}, body:JSON.stringify({slide_index:i})}); @else null; @endif
  const show = () => {
    slides.forEach((s, k) => s.classList.toggle('hidden', k !== i));
    document.getElementById('counter').textContent = (i + 1) + ' / ' + slides.length;
    const f = document.getElementById('finish'); if (f) f.classList.toggle('hidden', i !== slides.length - 1);
  };
  document.getElementById('prev').onclick = () => { if (i > 0) { i--; show(); save(); } };
  document.getElementById('next').onclick = () => { if (i < slides.length - 1) { i++; show(); save(); } };

  const norm = s => String(s).toLocaleLowerCase('tr').replace(/\s+/g, ' ').trim();
  const say = (el, ok, msg) => { const m = el.closest('.quiz').querySelector('.quiz-msg'); m.textContent = msg; m.className = 'quiz-msg mt-2 text-sm font-medium ' + (ok ? 'text-green-700' : 'text-red-700'); };
  document.querySelectorAll('.opt').forEach(b => b.onclick = () => { b.classList.add(b.dataset.ok === '1' ? 'bg-green-200' : 'bg-red-200'); say(b, b.dataset.ok === '1', b.dataset.ok === '1' ? 'Doğru!' : 'Tekrar dene.'); });
  document.querySelectorAll('.short-ck').forEach(b => b.onclick = () => {
    const inp = b.parentElement.querySelector('.short-in'), ok = JSON.parse(inp.dataset.answers).map(norm).includes(norm(inp.value));
    inp.classList.toggle('bg-green-100', ok); inp.classList.toggle('bg-red-100', !ok); say(b, ok, ok ? 'Doğru!' : 'Tekrar dene.');
  });
  document.querySelectorAll('.match-ck').forEach(b => b.onclick = () => {
    const sels = [...b.parentElement.querySelectorAll('.match-in')]; let good = 0;
    sels.forEach(s => { const ok = s.value === s.dataset.ok; if (ok) good++; s.classList.toggle('bg-green-100', ok); s.classList.toggle('bg-red-100', !ok); });
    say(b, good === sels.length, good === sels.length ? 'Hepsi doğru!' : good + ' / ' + sels.length + ' doğru, tekrar dene.');
  });
  show();
})();
</script>
@endif
@endsection
