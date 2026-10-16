@extends('layouts.app')
@section('title', 'Ders')
@section('content')
<x-page-head :title="$course->exists ? $course->name : 'Yeni ders'" :sub="$course->exists ? 'Ders bilgileri, sınıf ataması ve slaytlar' : null" :back="[route('courses.index'), 'Dersler']">
  @if($course->exists)<a href="{{ route('courses.preview', $course) }}" class="btn btn-ghost">👁 Önizle</a><a href="{{ route('courses.export', $course) }}" class="btn btn-ghost">⬇ İndir</a>@endif
</x-page-head>

<div style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:1.1rem;align-items:start" class="cf-grid">
<form method="POST" enctype="multipart/form-data" action="{{ $course->exists ? route('courses.update', $course) : route('courses.store') }}" class="card form-card" style="max-width:none">
@csrf @if($course->exists) @method('PUT') @endif
<div class="fgrid">
  <x-fg label="Ders adı" for="name" full><input id="name" class="field" name="name" value="{{ old('name', $course->name) }}" required></x-fg>
  <x-fg label="Kod" for="code" help="Benzersiz olmalı (örn. SCR101)"><input id="code" class="field" name="code" value="{{ old('code', $course->code) }}" required></x-fg>
  <x-fg label="Sıra" for="sort_order"><input id="sort_order" class="field" name="sort_order" type="number" min="0" value="{{ old('sort_order', $course->sort_order ?? 0) }}"></x-fg>
  <x-fg label="Açıklama" for="description" full><textarea id="description" class="field" name="description" rows="3">{{ old('description', $course->description) }}</textarea></x-fg>
  <x-fg label="Kategori" for="category"><input id="category" class="field" name="category" value="{{ old('category', $course->category) }}" maxlength="80"></x-fg>
  <x-fg label="Zorluk" for="difficulty"><input id="difficulty" class="field" name="difficulty" value="{{ old('difficulty', $course->difficulty) }}" maxlength="30" placeholder="Kolay / Orta / Zor"></x-fg>
  <x-fg label="Üst ders" for="parent_id"><select id="parent_id" class="field" name="parent_id"><option value="">Üst ders yok</option>@foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $course->parent_id) == $p->id)>{{ $p->name }}</option>@endforeach</select></x-fg>
  <x-fg label="Kapak görseli" for="cover"><input id="cover" type="file" name="cover" accept="image/*" class="field" style="padding:.5rem"></x-fg>
</div>
<label class="chk" style="margin-top:1.1rem"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $course->is_active))> Ders aktif (öğrencilere görünür)</label>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('courses.index') }}">Vazgeç</a></div>
</form>

@if($course->exists)
<div style="display:grid;gap:1.1rem">
<form method="POST" action="{{ route('courses.classes', $course) }}" class="card card-pad">@csrf
  <b style="display:block;margin-bottom:.2rem">Atanan sınıflar</b><p class="help" style="margin:0 0 .8rem">İşaretli sınıfların öğrencileri bu dersi görür.</p>
  <div class="chip-pick">@foreach($classes as $c)<label><input type="checkbox" name="classes[]" value="{{ $c->id }}" @checked($course->classes->contains($c->id))> {{ $c->label }}</label>@endforeach</div>
  <div class="form-actions"><button class="btn btn-soft btn-sm">Sınıfları kaydet</button></div>
</form>
@if($course->curriculum && ($course->curriculum['kazanimlar'] ?? []))
<div class="card card-pad"><b style="display:block;margin-bottom:.5rem">🎯 Kazanımlar</b><ul style="margin:0;padding-left:1.1rem;list-style:disc;font-size:.88rem;line-height:1.7;color:#475569">@foreach($course->curriculum['kazanimlar'] as $k)<li>{{ $k }}</li>@endforeach</ul></div>
@endif
</div>
@endif
</div>

@if($course->exists)
<div style="margin-top:1.6rem;max-width:1000px">
  <div class="page-head" style="margin-bottom:.8rem"><div><h2 style="font-size:1.2rem;font-weight:800;margin:0">Slaytlar <span class="badge">{{ $course->slides->count() }}</span></h2><p class="page-sub">Bir slayta tıklayıp düzenleyin.</p></div></div>
  <div style="display:grid;gap:.6rem">
  @foreach($course->slides as $slide)
    <details class="card" style="overflow:hidden"><summary style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1.1rem;cursor:pointer;list-style:none">
      <span class="avatar-i" style="width:30px;height:30px;font-size:.78rem;border-radius:9px;background:#2447f5">{{ $slide->position + 1 }}</span>
      <b style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $slide->title ?: '(başlıksız)' }}</b>
      <span class="badge badge-brand">{{ \App\Models\CourseSlide::TYPES[$slide->type] ?? $slide->type }}</span>@if($slide->type === 'quiz')<span class="badge">{{ ['choice' => 'seçmeli', 'short' => 'kısa cevap', 'matching' => 'eşleştirme'][$slide->content['mode'] ?? 'choice'] }}</span>@endif</summary>
      <form method="POST" action="{{ route('slides.update', [$course, $slide]) }}" style="padding:0 1.1rem 1.1rem;border-top:1px solid var(--line);padding-top:1rem">@csrf @method('PUT')
        @include('courses.slide-fields', ['slide' => $slide])
        <div class="form-actions" style="margin-top:1rem"><button class="btn btn-primary btn-sm">Güncelle</button><button form="del-{{ $slide->id }}" class="btn btn-danger btn-sm">Sil</button></div></form>
      <form id="del-{{ $slide->id }}" method="POST" action="{{ route('slides.destroy', [$course, $slide]) }}" onsubmit="return confirm('Slayt silinsin mi?')">@csrf @method('DELETE')</form>
    </details>
  @endforeach
  <details class="card" style="border:2px dashed #c7d2fe;background:#fafaff" @if($course->slides->isEmpty() || $errors->any()) open @endif><summary style="padding:.9rem 1.1rem;cursor:pointer;font-weight:700;color:var(--brand-d);list-style:none">＋ Yeni slayt ekle</summary>
    <form method="POST" action="{{ route('slides.store', $course) }}" style="padding:1rem 1.1rem 1.1rem">@csrf
      @include('courses.slide-fields', ['slide' => null])
      <div class="form-actions"><button class="btn btn-primary btn-sm">Slaytı ekle</button></div></form></details>
  </div>
</div>
<script>
// Slayt türüne göre yalnızca ilgili alanları göster (gizli alanlar yine de gönderilir; sunucu türe göre ayıklar)
document.querySelectorAll('[data-sf]').forEach(sf => {
  const type = sf.querySelector('[data-type]'), mode = sf.querySelector('[data-mode]');
  const apply = () => {
    sf.querySelectorAll('[data-for]').forEach(el => el.style.display = el.dataset.for.split(' ').includes(type.value) ? '' : 'none');
    sf.querySelectorAll('[data-mode-for]').forEach(el => el.style.display = (type.value === 'quiz' && el.dataset.modeFor === mode.value) ? '' : 'none');
  };
  type.onchange = apply; mode.onchange = apply; apply();
});
</script>
<style>@media (max-width: 960px) { .cf-grid { grid-template-columns: minmax(0,1fr) !important; } } summary::-webkit-details-marker { display: none; }</style>
@endif
@endsection
