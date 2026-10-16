@extends('layouts.app')
@section('title', 'Dersler')
@section('content')
<x-page-head title="Dersler" sub="Slayt tabanlı dersler — oluşturun, sınıflara atayın, v1 paketlerini içe aktarın">
  <label class="btn btn-ghost" style="cursor:pointer" for="imp-file"><x-icon name="upload" style="width:16px;height:16px" />İçe aktar</label>
  <a href="{{ route('courses.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Yeni ders</a>
</x-page-head>
<form id="imp" method="POST" action="{{ route('courses.import') }}" enctype="multipart/form-data" hidden>@csrf
  <input id="imp-file" type="file" name="files[]" accept=".coursepkg,.json" multiple onchange="this.form.submit()" title="v2 JSON paketi veya v1 .coursepkg"></form>
@if($r = session('import_report'))
<div class="card card-pad" style="margin-bottom:1.1rem;max-width:860px;font-size:.9rem;display:grid;gap:.5rem">
  <b>İçe aktarma raporu</b>
  @foreach($r['courses'] as $c)<div>✅ <a class="text-indigo-600" href="{{ route('courses.edit', $c['id']) }}">{{ $c['sub'] ? '↳ ' : '' }}{{ $c['name'] }}</a> — {{ $c['slides'] }} slayt{{ $c['quizzes'] ? ', '.$c['quizzes'].' soru' : '' }}@if($c['v1_slides'] !== null) <span style="color:var(--muted)">(v1'de {{ $c['v1_slides'] }} slayt)</span>@endif</div>@endforeach
  @foreach($r['errors'] as $e)<div class="alert alert-bad">❌ {{ $e }}</div>@endforeach
  @if($r['warnings'])<details class="alert alert-warn"><summary style="cursor:pointer;font-weight:700">⚠️ {{ count($r['warnings']) }} uyarı</summary>@foreach($r['warnings'] as $w)<div style="margin-top:.35rem">{{ $w }}</div>@endforeach</details>@endif
</div>
@endif
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:1rem" class="rise">
@forelse($courses as $c)
  <div class="card" style="overflow:hidden;display:flex;flex-direction:column">
    <div class="s-banner g{{ $c->id % 6 }}" style="height:84px;font-size:2.6rem">@if($c->cover_path)<img src="{{ asset('storage/'.$c->cover_path) }}" alt="" loading="lazy">@else<span>{{ ['🧠', '💻', '🎨', '🔬', '🚀', '🌈', '🧩', '🤖'][$c->id % 8] }}</span>@endif
      <span class="badge {{ $c->is_active ? 'badge-ok' : 'badge-bad' }}" style="position:absolute;top:.6rem;right:.6rem">{{ $c->is_active ? 'Aktif' : 'Pasif' }}</span></div>
    <div style="padding:1rem 1.15rem;flex:1;display:flex;flex-direction:column;gap:.55rem">
      <div><b style="font-size:1.02rem;line-height:1.3">{{ $c->name }}</b><div style="color:var(--muted);font-size:.76rem;margin-top:.15rem">{{ $c->code }}</div></div>
      <div style="display:flex;gap:.35rem;flex-wrap:wrap"><span class="badge">{{ $c->slides_count }} slayt</span><span class="badge badge-brand">{{ $c->classes_count }} sınıf</span>@if($c->category)<span class="badge">{{ $c->category }}</span>@endif @if($c->difficulty)<span class="badge badge-warn">{{ $c->difficulty }}</span>@endif</div>
      <div class="ra" style="margin-top:auto;display:flex;gap:.3rem;justify-content:flex-end;padding-top:.4rem">
        <a class="ibtn" href="{{ route('courses.preview', $c) }}">Önizle</a><a class="ibtn" href="{{ route('courses.export', $c) }}">İndir</a><a class="ibtn" href="{{ route('courses.edit', $c) }}">Düzenle</a><x-del :action="route('courses.destroy', $c)" icon />
      </div>
    </div>
  </div>
@empty
  <div class="card" style="grid-column:1/-1"><x-empty icon="📚" title="Henüz ders yok" text="Yeni ders oluşturun ya da v1'den .coursepkg dosyası içe aktarın."><a href="{{ route('courses.create') }}" class="btn btn-primary">Ders oluştur</a></x-empty></div>
@endforelse
</div>
{{ $courses->links() }}
@endsection
