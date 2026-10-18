@extends('layouts.app')
@section('title', $r->subject)
@section('content')
<x-page-head :title="$r->subject" :sub="$r->sender_name.($r->guest_email ? ' · '.$r->guest_email : '').' · '.\App\Models\SupportRequest::STATUSES[$r->status].' · öncelik: '.$r->priority" :back="[route('support.index'), 'Destek']" />
<div style="max-width:760px">
<div class="card card-pad" style="white-space:pre-line;margin-bottom:1rem;line-height:1.6">{{ $r->message }}</div>
@foreach($replies as $p)
<div class="card card-pad" style="white-space:pre-line;margin-bottom:.6rem;line-height:1.6;{{ $p->internal_note ? 'background:var(--tint-warn);border-color:var(--tint-warn-bd)' : ($p->sender_user_id === $r->sender_user_id ? '' : 'background:var(--tint-brand);border-color:var(--tint-brand-bd)') }}">
<div class="help" style="margin-bottom:.3rem">{{ $p->sender->name }} · {{ $p->created_at->format('d.m H:i') }}@if($p->internal_note) · <b>dahili not</b>@endif</div>{{ $p->message }}</div>
@endforeach
@if($r->status !== 'closed' && $r->sender_user_id)
<form method="POST" action="{{ route('support.reply', $r) }}" style="margin-top:1rem;display:grid;gap:.6rem">@csrf
<textarea name="message" rows="3" class="field" placeholder="Yanıtınız" required maxlength="3000"></textarea>
@if(auth()->user()->hasRole('admin'))<label class="chk"><input type="checkbox" name="internal_note" value="1"> Dahili not (gönderen görmez)</label>@endif
<div><button class="btn btn-primary">Yanıtla</button></div></form>
@endif
<div class="page-actions" style="margin-top:1.2rem">
@if($r->status !== 'closed')<form method="POST" action="{{ route('support.close', $r) }}">@csrf<button class="btn btn-ghost btn-sm">Kapat</button></form>@endif
@if(auth()->user()->hasRole('admin'))
<form method="POST" action="{{ route('support.archive', $r) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $r->archived_at ? 'Arşivden çıkar' : 'Arşivle' }}</button></form>
<form method="POST" action="{{ route('support.destroy', $r) }}" onsubmit="return confirm('Kalıcı silinsin mi?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Sil</button></form>@endif
</div></div>
@endsection
