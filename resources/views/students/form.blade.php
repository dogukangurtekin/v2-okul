@extends('layouts.app')
@section('title', 'Öğrenci')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $student->exists ? 'Öğrenciyi düzenle' : 'Yeni öğrenci' }}</h1>
<form method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}" class="bg-white p-6 rounded-xl shadow max-w-lg space-y-3">
@csrf @if($student->exists) @method('PUT') @endif
<input name="name" value="{{ old('name', $student->user?->name) }}" placeholder="Ad Soyad" class="w-full border rounded px-3 py-2" required>
<input name="email" value="{{ old('email', $student->user?->email) }}" placeholder="E-posta / kullanıcı adı" class="w-full border rounded px-3 py-2" required>
<input name="password" type="password" placeholder="{{ $student->exists ? 'Şifre (değişmeyecekse boş bırakın)' : 'Şifre' }}" class="w-full border rounded px-3 py-2" @required(!$student->exists)>
<input name="student_no" value="{{ old('student_no', $student->student_no) }}" placeholder="Öğrenci no" class="w-full border rounded px-3 py-2" required>
<select name="school_class_id" class="w-full border rounded px-3 py-2"><option value="">Sınıf yok</option>@foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id', $student->school_class_id) == $c->id)>{{ $c->label }}</option>@endforeach</select>
<input name="parent_name" value="{{ old('parent_name', $student->parent_name) }}" placeholder="Veli adı" class="w-full border rounded px-3 py-2">
<input name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}" placeholder="Veli telefon" class="w-full border rounded px-3 py-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Kaydet</button>
</form>
@endsection
