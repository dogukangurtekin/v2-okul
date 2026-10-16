@extends('layouts.app')
@section('title', 'Ders')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $course->exists ? 'Dersi düzenle' : 'Yeni ders' }}</h1>
<form method="POST" enctype="multipart/form-data" action="{{ $course->exists ? route('courses.update', $course) : route('courses.store') }}" class="bg-white p-6 rounded-xl shadow max-w-2xl space-y-3">
@csrf @if($course->exists) @method('PUT') @endif
<input name="name" value="{{ old('name', $course->name) }}" placeholder="Ders adı" class="w-full border rounded px-3 py-2" required>
<input name="code" value="{{ old('code', $course->code) }}" placeholder="Kod (benzersiz)" class="w-full border rounded px-3 py-2" required>
<textarea name="description" rows="3" placeholder="Açıklama" class="w-full border rounded px-3 py-2">{{ old('description', $course->description) }}</textarea>
<div class="grid grid-cols-2 gap-2"><input name="category" value="{{ old('category', $course->category) }}" placeholder="Kategori" maxlength="80" class="border rounded px-3 py-2"><input name="difficulty" value="{{ old('difficulty', $course->difficulty) }}" placeholder="Zorluk (Kolay/Orta/Zor)" maxlength="30" class="border rounded px-3 py-2"></div>
<select name="parent_id" class="w-full border rounded px-3 py-2"><option value="">Üst ders yok</option>@foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $course->parent_id) == $p->id)>{{ $p->name }}</option>@endforeach</select>
<input name="sort_order" type="number" min="0" value="{{ old('sort_order', $course->sort_order ?? 0) }}" class="w-full border rounded px-3 py-2" placeholder="Sıra">
<input type="file" name="cover" accept="image/*">
<label class="flex gap-2 items-center"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $course->is_active))> Aktif</label>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button>
</form>

@if($course->exists)
<form method="POST" action="{{ route('courses.classes', $course) }}" class="bg-white p-6 rounded-xl shadow max-w-2xl mt-6">@csrf
<h2 class="font-bold mb-2">Atanan sınıflar</h2>
<div class="flex flex-wrap gap-3 mb-3">@foreach($classes as $c)
<label class="flex gap-1 items-center"><input type="checkbox" name="classes[]" value="{{ $c->id }}" @checked($course->classes->contains($c->id))> {{ $c->label }}</label>@endforeach</div>
<button class="bg-slate-700 text-white px-4 py-2 rounded">Sınıfları kaydet</button></form>

<div class="max-w-2xl mt-6 space-y-3">
<h2 class="font-bold">Slaytlar ({{ $course->slides->count() }})</h2>
@foreach($course->slides as $slide)
<form method="POST" action="{{ route('slides.update', [$course, $slide]) }}" class="bg-white p-4 rounded-xl shadow space-y-2">@csrf @method('PUT')
<div class="text-xs text-slate-500">#{{ $slide->position + 1 }}</div>
@include('courses.slide-fields', ['slide' => $slide])
<div class="flex gap-2"><button class="bg-slate-700 text-white px-3 py-1 rounded">Güncelle</button>
<button form="del-{{ $slide->id }}" class="text-red-600">Sil</button></div></form>
<form id="del-{{ $slide->id }}" method="POST" action="{{ route('slides.destroy', [$course, $slide]) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')</form>
@endforeach
<form method="POST" action="{{ route('slides.store', $course) }}" class="bg-indigo-50 p-4 rounded-xl space-y-2">@csrf
<div class="font-semibold">Yeni slayt</div>
@include('courses.slide-fields', ['slide' => null])
<button class="bg-indigo-600 text-white px-3 py-1 rounded">Ekle</button></form>
</div>
@endif
@endsection
