@extends('layouts.app')
@section('title', 'Destek')
@section('content')
<h1 class="text-2xl font-bold mb-4">Destek Talepleri</h1>
@if(auth()->user()->hasRole('teacher'))
<form method="POST" action="{{ route('support.store') }}" class="bg-white p-5 rounded-xl shadow max-w-xl space-y-3 mb-6">@csrf
<div class="font-semibold">Yeni talep</div>
<input name="subject" placeholder="Konu" class="w-full border rounded px-3 py-2" required maxlength="160">
<div class="flex gap-2"><select name="category" class="border rounded px-3 py-2">@foreach(\App\Models\SupportRequest::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
<select name="priority" class="border rounded px-3 py-2"><option value="low">Düşük</option><option value="normal" selected>Normal</option><option value="high">Yüksek</option></select></div>
<textarea name="message" rows="4" placeholder="Sorununuzu anlatın" class="w-full border rounded px-3 py-2" required maxlength="3000"></textarea>
<button class="bg-indigo-600 text-white px-4 py-2 rounded">Gönder</button></form>
@endif
<div class="mb-3 text-sm space-x-3"><a class="{{ $archived ? '' : 'font-bold' }}" href="{{ route('support.index') }}">Aktif</a>@if(auth()->user()->hasRole('admin'))<a class="{{ $archived ? 'font-bold' : '' }}" href="{{ route('support.index', ['archive' => 1]) }}">Arşiv</a>@endif</div>
<table class="w-full bg-white rounded-xl shadow text-sm">
<tr class="text-left border-b"><th class="p-3">Konu</th><th>Gönderen</th><th>Kategori</th><th>Durum</th><th>Tarih</th></tr>
@forelse($items as $r)
<tr class="border-b {{ auth()->user()->hasRole('admin') && !$r->read_at ? 'font-semibold' : '' }}"><td class="p-3"><a class="text-indigo-600" href="{{ route('support.show', $r) }}">{{ $r->subject }}</a>@if($r->source === 'demo') <span class="text-xs bg-amber-100 px-1 rounded">demo</span>@endif</td>
<td>{{ $r->sender_name }}</td><td>{{ \App\Models\SupportRequest::CATEGORIES[$r->category] ?? $r->category }}</td><td>{{ \App\Models\SupportRequest::STATUSES[$r->status] }}</td><td class="text-slate-400">{{ $r->created_at->format('d.m.Y H:i') }}</td></tr>
@empty<tr><td class="p-3 text-slate-500" colspan="5">Talep yok.</td></tr>@endforelse
</table>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
