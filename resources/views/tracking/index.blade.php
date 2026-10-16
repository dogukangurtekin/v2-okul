@extends('layouts.app')
@section('title', 'Canlı Takip')
@section('content')
<x-page-head title="Canlı Takip" sub="Son 2 dakika içinde aktif öğrenciler (5 sn'de bir yenilenir)">
  <select id="cls" class="field" style="width:auto"><option value="">Tüm sınıflar</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach</select>
</x-page-head>
<div id="empty" class="hidden"><x-empty icon="📡" title="Şu an aktif öğrenci yok" text="Öğrenciler platformu kullanmaya başlayınca burada görünecek." /></div>
<div class="card tbl-wrap"><table class="tbl"><tbody id="rows"></tbody></table></div>
<div class="page-actions" style="margin-top:1rem">@foreach($classes as $c)
<form method="POST" action="{{ route('tracking.logout.class', $c->id) }}" onsubmit="return confirm('{{ $c->label }} sınıfının oturumları kapatılsın mı?')">@csrf<button class="btn btn-danger btn-sm">{{ $c->label }} oturumlarını kapat</button></form>@endforeach</div>
<script>
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const CSRF = @json(csrf_token()), LOGOUT = @json(route('tracking.logout.student', '__ID__'));
async function load() {
  const q = document.getElementById('cls').value;
  const rows = await (await fetch(@json(route('tracking.data')) + (q ? '?class=' + q : ''), {headers:{Accept:'application/json'}})).json();
  document.getElementById('empty').classList.toggle('hidden', rows.length > 0);
  document.getElementById('rows').innerHTML = rows.map(r => `<tr><td><div class="who"><span class="avatar-i" style="width:32px;height:32px;font-size:.8rem;background:#10b981">${esc((r.name||'?').charAt(0))}</span><a href="${r.history_url}" style="font-weight:700;color:var(--ink);text-decoration:none">${esc(r.name)}</a></div></td><td><span class="badge badge-brand">${esc(r.class)}</span></td><td style="color:var(--muted)">${esc(r.path)}</td><td style="color:#94a3b8">${r.seconds_ago} sn önce</td>
    <td style="text-align:right"><form method="POST" action="${LOGOUT.replace('__ID__', r.id)}" onsubmit="return confirm('Oturum kapatılsın mı?')"><input type="hidden" name="_token" value="${CSRF}"><button class="btn btn-danger btn-sm">Oturumu kapat</button></form></td></tr>`).join('');
}
document.getElementById('cls').onchange = load; load(); setInterval(load, 5000);
</script>
@endsection
