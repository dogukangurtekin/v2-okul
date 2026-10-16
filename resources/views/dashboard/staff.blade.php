@extends('layouts.app')
@section('title', 'Panel')
@section('content')
<h1 class="text-2xl font-bold mb-4">Panel</h1>
<div class="grid grid-cols-3 gap-4">
@foreach($stats as $label => $n)
    <div class="bg-white rounded-xl shadow p-5"><div class="text-slate-500">{{ $label }}</div><div class="text-3xl font-bold">{{ $n }}</div></div>
@endforeach
</div>
@php($ann = \App\Models\Announcement::visibleTo(auth()->user())->limit(3)->get())
@if($ann->isNotEmpty())<div class="mt-6 max-w-3xl space-y-2"><div class="font-semibold">📢 Duyurular</div>@foreach($ann as $x)<div class="bg-amber-50 border border-amber-200 rounded-lg p-3"><b>{{ $x->title }}</b><div class="text-sm whitespace-pre-line">{{ \Illuminate\Support\Str::limit($x->content, 160) }}</div></div>@endforeach</div>@endif
@endsection
