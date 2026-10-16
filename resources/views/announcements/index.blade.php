@extends('layouts.app')
@section('title', 'Duyuru Yönetimi')
@section('content')
<div class="flex justify-between mb-4"><h1 class="text-2xl font-bold">Duyuru Yönetimi</h1><a href="{{ route('announcements.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">Yeni duyuru</a></div>
<table class="w-full bg-white rounded-xl shadow text-sm"><tr class="text-left border-b"><th class="p-3">Başlık</th><th>Kitle</th><th>Yayın</th><th></th></tr>
@foreach($items as $a)<tr class="border-b"><td class="p-3">{{ $a->title }}</td><td>{{ \App\Models\Announcement::AUDIENCES[$a->audience] }}</td><td>{{ $a->published_at?->format('d.m.Y H:i') }}</td>
<td class="text-right p-3 space-x-2"><a class="text-indigo-600" href="{{ route('announcements.edit', $a) }}">Düzenle</a>
<form class="inline" method="POST" action="{{ route('announcements.destroy', $a) }}" onsubmit="return confirm('Silinsin mi?')">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td></tr>@endforeach</table>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
