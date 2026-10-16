@extends('layouts.app')
@section('title', 'Duyurular')
@section('content')
<h1 class="text-2xl font-bold mb-4">Duyurular</h1>
<div class="space-y-3 max-w-2xl">@forelse($items as $a)
<div class="bg-white rounded-xl shadow p-4"><div class="font-semibold">{{ $a->title }}</div><div class="text-xs text-slate-400 mb-2">{{ $a->published_at->format('d.m.Y H:i') }} · {{ $a->author?->name }}</div><div class="whitespace-pre-line">{{ $a->content }}</div></div>
@empty<p class="text-slate-500">Duyuru yok.</p>@endforelse</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
