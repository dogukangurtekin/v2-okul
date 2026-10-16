@extends('layouts.app')
@section('title', 'Destek')
@section('content')
<x-page-head title="Destek Talepleri" sub="Sorun ve önerilerinizi iletin" />
@if(auth()->user()->hasRole('teacher'))
<form method="POST" action="{{ route('support.store') }}" class="card form-card" style="margin-bottom:1.3rem">@csrf
<b style="display:block;margin-bottom:.9rem">✉ Yeni talep</b>
<div class="fgrid">
<x-fg label="Konu" full><input name="subject" placeholder="Konu" class="field" required maxlength="160"></x-fg>
<x-fg label="Kategori"><select name="category" class="field">@foreach(\App\Models\SupportRequest::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></x-fg>
<x-fg label="Öncelik"><select name="priority" class="field"><option value="low">Düşük</option><option value="normal" selected>Normal</option><option value="high">Yüksek</option></select></x-fg>
<x-fg label="Mesaj" full><textarea name="message" rows="4" placeholder="Sorununuzu anlatın" class="field" required maxlength="3000"></textarea></x-fg></div>
<div class="form-actions"><button class="btn btn-primary">Gönder</button></div></form>
@endif
<div class="toolbar"><a class="btn {{ $archived ? 'btn-ghost' : 'btn-soft' }} btn-sm" href="{{ route('support.index') }}">Aktif</a>@if(auth()->user()->hasRole('admin'))<a class="btn {{ $archived ? 'btn-soft' : 'btn-ghost' }} btn-sm" href="{{ route('support.index', ['archive' => 1]) }}">Arşiv</a>@endif</div>
<div class="card tbl-wrap"><table class="tbl">
<thead><tr><th>Konu</th><th>Gönderen</th><th>Kategori</th><th>Durum</th><th>Tarih</th></tr></thead><tbody>
@forelse($items as $r)
<tr style="{{ auth()->user()->hasRole('admin') && !$r->read_at ? 'font-weight:700' : '' }}"><td><a href="{{ route('support.show', $r) }}" style="color:var(--brand-d);text-decoration:none;font-weight:700">{{ $r->subject }}</a>@if($r->source === 'demo') <span class="badge badge-warn">demo</span>@endif</td>
<td>{{ $r->sender_name }}</td><td>{{ \App\Models\SupportRequest::CATEGORIES[$r->category] ?? $r->category }}</td><td><span class="badge {{ $r->status === 'closed' ? '' : 'badge-brand' }}">{{ \App\Models\SupportRequest::STATUSES[$r->status] }}</span></td><td style="color:#94a3b8">{{ $r->created_at->format('d.m.Y H:i') }}</td></tr>
@empty<tr><td colspan="5"><x-empty icon="🎧" title="Talep yok" /></td></tr>@endforelse
</tbody></table></div>
{{ $items->links() }}
@endsection
