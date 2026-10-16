@extends('layouts.app')
@section('title', 'Toplu Yükleme')
@section('content')
<h1 class="text-2xl font-bold mb-4">Toplu Yükleme</h1>
@if($errors->any())<div class="p-3 bg-red-100 text-red-800 rounded mb-4">{{ $errors->first() }}</div>@endif
<div class="grid grid-cols-2 gap-6 max-w-4xl">
<form method="POST" action="{{ route('import.upload', 'student') }}" enctype="multipart/form-data" class="bg-white p-5 rounded-xl shadow space-y-3">@csrf
<div class="font-semibold">Öğrenciler</div>
<p class="text-sm text-slate-500">Sütunlar: ad_soyad, ogrenci_no, sinif (örn. 5-A), kullanici_adi (boşsa no@school.local), sifre (boşsa otomatik), veli_adi, veli_telefon.</p>
<a class="text-indigo-600 text-sm" href="{{ route('import.template', 'student') }}">Şablonu indir (CSV)</a>
<input type="file" name="file" accept=".xlsx,.csv,.txt" required>
<label class="flex gap-2 items-center text-sm"><input type="checkbox" name="create_classes" value="1" checked> Olmayan sınıfları oluştur</label>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Yükle</button></form>
<form method="POST" action="{{ route('import.upload', 'teacher') }}" enctype="multipart/form-data" class="bg-white p-5 rounded-xl shadow space-y-3">@csrf
<div class="font-semibold">Öğretmenler</div>
<p class="text-sm text-slate-500">Sütunlar: ad_soyad, eposta, brans, telefon, sifre (boşsa otomatik).</p>
<a class="text-indigo-600 text-sm" href="{{ route('import.template', 'teacher') }}">Şablonu indir (CSV)</a>
<input type="file" name="file" accept=".xlsx,.csv,.txt" required>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Yükle</button></form>
</div>
<p class="text-sm text-slate-500 mt-4 max-w-4xl">.xlsx veya .csv (Excel'den "CSV UTF-8" kaydedilebilir; Türkçe Excel'in noktalı virgüllü CSV'si de okunur). En fazla 1000 satır. Geçici şifreler yalnızca yükleme sonucunda bir kez gösterilir.</p>
@endsection
