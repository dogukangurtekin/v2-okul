@extends('layouts.app')
@section('title', 'Canlı Takip')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Canlı Takip <span class="text-base text-slate-400">son 2 dk içinde aktif</span></h1>
<select id="cls" class="border rounded px-3 py-2 bg-white"><option value="">Tüm sınıflar</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach</select></div>
<div id="empty" class="text-slate-500 hidden">Şu an aktif öğrenci yok.</div>
<table class="w-full bg-white rounded-xl shadow text-sm"><tbody id="rows"></tbody></table>
<div class="mt-4 flex flex-wrap gap-2">@foreach($classes as $c)
<form method="POST" action="{{ route('tracking.logout.class', $c->id) }}" onsubmit="return confirm('{{ $c->label }} sınıfının oturumları kapatılsın mı?')">@csrf<button class="border rounded px-3 py-1 text-sm text-red-600 bg-white">{{ $c->label }} oturumlarını kapat</button></form>@endforeach</div>
<script>
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const CSRF = @json(csrf_token()), LOGOUT = @json(route('tracking.logout.student', '__ID__'));
async function load() {
  const q = document.getElementById('cls').value;
  const rows = await (await fetch(@json(route('tracking.data')) + (q ? '?class=' + q : ''), {headers:{Accept:'application/json'}})).json();
  document.getElementById('empty').classList.toggle('hidden', rows.length > 0);
  document.getElementById('rows').innerHTML = rows.map(r => `<tr class="border-b"><td class="p-3"><a class="text-indigo-600" href="${r.history_url}">${esc(r.name)}</a></td><td>${esc(r.class)}</td><td class="text-slate-500">${esc(r.path)}</td><td class="text-slate-400">${r.seconds_ago} sn önce</td>
    <td class="text-right p-3"><form method="POST" action="${LOGOUT.replace('__ID__', r.id)}" onsubmit="return confirm('Oturum kapatılsın mı?')"><input type="hidden" name="_token" value="${CSRF}"><button class="text-red-600">Oturumu kapat</button></form></td></tr>`).join('');
}
document.getElementById('cls').onchange = load; load(); setInterval(load, 5000);
</script>
@endsection
