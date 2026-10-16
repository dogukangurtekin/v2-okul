@extends('layouts.app')
@section('title', 'Öğrenci Mesajları')
@section('content')
<x-page-head title="Öğrenci Mesajları" sub="Öğrencilerden gelen sorular">
@if(auth()->user()->hasRole('admin'))<form method="POST" action="{{ route('messages.clear') }}" onsubmit="return confirm('Yanıtlananlar gizlensin mi?')">@csrf @method('DELETE')<button class="btn btn-ghost">Yanıtlananları temizle</button></form>@endif</x-page-head>
<div style="display:grid;gap:.8rem;max-width:760px">
@forelse($messages as $m)
<div class="card card-pad" style="{{ $m->replied_at ? 'opacity:.75' : 'border-left:4px solid var(--brand)' }}">
<div class="who" style="margin-bottom:.5rem"><x-initials :name="$m->student->user->name" :size="32" /><div><b>{{ $m->student->user->name }}</b><small>{{ $m->student->schoolClass?->label }} · {{ $m->created_at->format('d.m H:i') }}</small></div></div>
<div style="white-space:pre-line;line-height:1.6">{{ $m->body }}</div>
@if($m->reply_body)<div style="margin-top:.6rem;padding:.6rem .8rem;background:#e4eaff;border-radius:10px;font-size:.9rem;white-space:pre-line">{{ $m->reply_body }}</div>
@else<form method="POST" action="{{ route('messages.reply', $m) }}" style="display:flex;gap:.5rem;margin-top:.7rem">@csrf<input name="reply" maxlength="500" placeholder="Yanıt" class="field" required><button class="btn btn-primary">Yanıtla</button></form>@endif
<form method="POST" action="{{ route('messages.destroy', $m) }}" style="margin-top:.5rem">@csrf @method('DELETE')<button class="ibtn" style="font-size:.75rem">Gizle</button></form></div>
@empty<x-empty icon="💬" title="Mesaj yok" />@endforelse</div>
<div style="margin-top:1rem">{{ $messages->links() }}</div>
@endsection
