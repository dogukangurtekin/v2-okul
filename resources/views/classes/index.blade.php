@extends('layouts.app')
@section('title', 'Sınıflar')
@section('content')
<x-page-head title="Sınıflar" sub="Sınıf, şube ve öğretmen atamaları">
  <a href="{{ route('classes.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Yeni sınıf</a>
</x-page-head>
<div class="card tbl-wrap">
<table class="tbl">
  <thead><tr><th>Sınıf</th><th>Kademe</th><th>Eğitim yılı</th><th>Öğretmen</th><th>Öğrenci</th><th></th></tr></thead>
  <tbody>
  @forelse($classes as $c)
    <tr>
      <td><div class="who"><span class="avatar-i" style="border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6)">{{ $c->name }}</span><b>{{ $c->label }}</b></div></td>
      <td>{{ $c->grade_level }}. kademe</td><td>{{ $c->academic_year }}</td>
      <td>@if($c->teacher){{ $c->teacher->user->name }}@else<span class="badge badge-warn">Atanmamış</span>@endif</td>
      <td><a href="{{ route('students.index', ['class' => $c->id]) }}" class="badge badge-brand" style="text-decoration:none">{{ $c->students_count }} öğrenci</a></td>
      <td><div class="ra"><a class="ibtn" href="{{ route('classes.edit', $c) }}">Düzenle</a><x-del :action="route('classes.destroy', $c)" icon /></div></td>
    </tr>
  @empty
    <tr><td colspan="6"><x-empty icon="🏫" title="Henüz sınıf yok" text="İlk sınıfı oluşturup öğrencileri ekleyin."><a href="{{ route('classes.create') }}" class="btn btn-primary">Sınıf oluştur</a></x-empty></td></tr>
  @endforelse
  </tbody>
</table>
</div>
{{ $classes->links() }}
@endsection
