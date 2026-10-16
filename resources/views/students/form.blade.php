@extends('layouts.app')
@section('title', 'Öğrenci')
@section('content')
<x-page-head :title="$student->exists ? 'Öğrenciyi düzenle' : 'Yeni öğrenci'" :back="[route('students.index'), 'Öğrenciler']" />
<form method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}" class="card form-card">
@csrf @if($student->exists) @method('PUT') @endif
<div class="fgrid">
  <x-fg label="Ad soyad" for="name"><input id="name" class="field" name="name" value="{{ old('name', $student->user?->name) }}" required></x-fg>
  <x-fg label="Öğrenci numarası" for="student_no"><input id="student_no" class="field" name="student_no" value="{{ old('student_no', $student->student_no) }}" required></x-fg>
  <x-fg label="Kullanıcı adı / e-posta" for="email" help="Öğrenci girişte bunu kullanır."><input id="email" class="field" name="email" value="{{ old('email', $student->user?->email) }}" required></x-fg>
  <x-fg label="Şifre" for="password" :help="$student->exists ? 'Değişmeyecekse boş bırakın.' : 'En az 6 karakter.'"><input id="password" class="field" name="password" type="password" @required(!$student->exists)></x-fg>
  <x-fg label="Sınıf" for="school_class_id" full><select id="school_class_id" class="field" name="school_class_id"><option value="">Sınıf yok</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $student->school_class_id) == $c->id)>{{ $c->label }}</option>@endforeach</select></x-fg>
  <x-fg label="Veli adı" for="parent_name"><input id="parent_name" class="field" name="parent_name" value="{{ old('parent_name', $student->parent_name) }}"></x-fg>
  <x-fg label="Veli telefonu" for="parent_phone" help="Karne bağlantısı bu numaraya gönderilir (örn. 0532 111 22 33)."><input id="parent_phone" class="field" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}"></x-fg>
</div>
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('students.index') }}">Vazgeç</a></div>
</form>
@endsection
