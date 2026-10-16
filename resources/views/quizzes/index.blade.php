@extends('layouts.app')
@section('title', 'Quizler')
@section('content')
<x-page-head title="Quizler" sub="Canlı oturumlarda kullanacağınız soru setleri">
  <a href="{{ route('quizzes.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Yeni quiz</a>
</x-page-head>
<div class="card tbl-wrap"><table class="tbl">
  <thead><tr><th>Başlık</th><th>Soru</th><th></th></tr></thead>
  <tbody>
  @forelse($quizzes as $q)
    <tr><td><div class="who"><span style="font-size:1.4rem">❓</span><b>{{ $q->title }}</b></div></td><td><span class="badge badge-brand">{{ $q->questions_count }} soru</span></td>
    <td><div class="ra"><a class="ibtn" href="{{ route('quizzes.edit', $q) }}">Düzenle</a><x-del :action="route('quizzes.destroy', $q)" icon /></div></td></tr>
  @empty
    <tr><td colspan="3"><x-empty icon="❓" title="Henüz quiz yok" text="İlk quiz'inizi oluşturup canlı oturumda yarıştırın."><a href="{{ route('quizzes.create') }}" class="btn btn-primary">Yeni quiz</a></x-empty></td></tr>
  @endforelse
  </tbody></table></div>
{{ $quizzes->links() }}
@endsection
