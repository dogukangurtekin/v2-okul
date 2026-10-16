@extends('layouts.app')
@section('title', 'Sınıf Panosu')
@section('content')
<h1 class="text-2xl font-bold mb-4">Sınıf Panosu</h1>
<form method="POST" action="{{ route('student.board.post') }}" class="flex flex-wrap gap-2 mb-6">@csrf
@foreach($messages as $k => $text)<button name="message_key" value="{{ $k }}" class="bg-white border rounded-full px-4 py-2 hover:bg-indigo-50">{{ $text }}</button>@endforeach
</form>
<div class="space-y-2 max-w-xl">
@foreach($posts as $p)<div class="bg-white rounded-xl shadow p-3"><b>{{ $p->student->avatar?->emoji ?? '🙂' }} {{ $p->student->user->name }}</b>: {{ config('board.'.$p->message_key) }} <span class="text-xs text-slate-400">{{ $p->created_at->diffForHumans() }}</span></div>@endforeach
</div>
@endsection
