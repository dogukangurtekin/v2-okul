@extends('layouts.app')
@section('title', 'Quizler')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Quizler</h1><a href="{{ route('quizzes.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Yeni quiz</a></div>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Başlık</th><th>Soru</th><th></th></tr>
@foreach($quizzes as $q)
<tr class="border-b"><td class="p-3">{{ $q->title }}</td><td>{{ $q->questions_count }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ route('quizzes.edit', $q) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('quizzes.destroy', $q) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>
@endforeach
</table>
<div class="mt-4">{{ $quizzes->links() }}</div>
@endsection
