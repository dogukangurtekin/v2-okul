@extends('layouts.app')
@section('title', 'Duyurular')
@section('content')
<x-page-head title="Duyurular" sub="Okuldan son haberler" />
<div style="display:grid;gap:.8rem;max-width:760px">@forelse($items as $a)
<div class="card card-pad"><div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap"><b style="font-size:1.05rem">📢 {{ $a->title }}</b><span class="help">{{ $a->published_at->format('d.m.Y H:i') }} · {{ $a->author?->name }}</span></div><div style="white-space:pre-line;margin-top:.6rem;color:#334155;line-height:1.6">{{ $a->content }}</div></div>
@empty<x-empty icon="📢" title="Duyuru yok" />@endforelse</div>
<div style="margin-top:1rem">{{ $items->links() }}</div>
@endsection
