@extends('layouts.app')
@section('title', 'Toplu Yükleme')
@section('content')
<x-page-head title="Toplu Yükleme" sub="Excel (.xlsx) veya CSV ile öğrenci ve öğretmenleri tek seferde ekleyin." />
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1.1rem;max-width:980px" class="rise">
  <form method="POST" action="{{ route('import.upload', 'student') }}" enctype="multipart/form-data" class="card card-pad" style="display:grid;gap:.9rem;align-content:start">@csrf
    <div style="display:flex;align-items:center;gap:.8rem"><span class="stat-i" style="position:static;background:#e4eaff;color:#2447f5"><x-icon name="cap" style="width:20px;height:20px" /></span><div><b style="font-size:1.05rem">Öğrenciler</b><div style="color:var(--muted);font-size:.8rem">Sınıfa göre toplu kayıt</div></div></div>
    <p style="margin:0;color:#475569;font-size:.86rem;line-height:1.6">Sütunlar: <code>ad_soyad</code>, <code>ogrenci_no</code>, <code>sinif</code> (örn. 5-A), <code>kullanici_adi</code> (boşsa no@school.local), <code>sifre</code> (boşsa otomatik), <code>veli_adi</code>, <code>veli_telefon</code></p>
    <a class="btn btn-soft btn-sm" style="justify-self:start" href="{{ route('import.template', 'student') }}">⬇ Şablonu indir (CSV)</a>
    <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="field" style="padding:.5rem">
    <label class="chk"><input type="checkbox" name="create_classes" value="1" checked> Olmayan sınıfları oluştur</label>
    <button class="btn btn-primary">Öğrencileri yükle</button>
  </form>
  <form method="POST" action="{{ route('import.upload', 'teacher') }}" enctype="multipart/form-data" class="card card-pad" style="display:grid;gap:.9rem;align-content:start">@csrf
    <div style="display:flex;align-items:center;gap:.8rem"><span class="stat-i" style="position:static;background:#ecfeff;color:#0891b2"><x-icon name="users" style="width:20px;height:20px" /></span><div><b style="font-size:1.05rem">Öğretmenler</b><div style="color:var(--muted);font-size:.8rem">Hesapları toplu oluşturun</div></div></div>
    <p style="margin:0;color:#475569;font-size:.86rem;line-height:1.6">Sütunlar: <code>ad_soyad</code>, <code>eposta</code>, <code>brans</code>, <code>telefon</code>, <code>sifre</code> (boşsa otomatik)</p>
    <a class="btn btn-soft btn-sm" style="justify-self:start" href="{{ route('import.template', 'teacher') }}">⬇ Şablonu indir (CSV)</a>
    <input type="file" name="file" accept=".xlsx,.csv,.txt" required class="field" style="padding:.5rem">
    <button class="btn btn-primary" style="margin-top:auto">Öğretmenleri yükle</button>
  </form>
</div>
<div class="alert alert-info" style="max-width:980px;margin-top:1.1rem">.xlsx veya .csv kullanabilirsiniz (Excel'den "CSV UTF-8" kaydedin; Türkçe Excel'in noktalı virgüllü CSV'si de okunur). En fazla 1000 satır. <b>Geçici şifreler yalnızca yükleme sonucunda bir kez gösterilir.</b></div>
@endsection
