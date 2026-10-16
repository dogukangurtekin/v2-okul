@extends('layouts.app')
@section('title', 'Bildirim Gönder')
@section('content')
<x-page-head title="Bildirim Gönder" sub="Seçtiğiniz sınıfların öğrencilerine anlık bildirim" />
<form method="POST" action="{{ route('notifications.send') }}" class="card form-card">@csrf
<div style="display:grid;gap:.9rem">
<x-fg label="Başlık"><input name="title" value="{{ old('title') }}" placeholder="Başlık" class="field" required maxlength="120"></x-fg>
<x-fg label="Mesaj"><textarea name="body" rows="3" placeholder="Mesaj" class="field" required maxlength="500">{{ old('body') }}</textarea></x-fg>
<div class="fg"><span class="lbl">Sınıflar</span><div class="chip-pick">@foreach($classes as $c)<label><input type="checkbox" name="class_ids[]" value="{{ $c->id }}"> {{ $c->label }}</label>@endforeach</div></div></div>
<div class="form-actions"><button class="btn btn-primary">Gönder</button></div></form>
<h2 style="font-size:1.1rem;font-weight:800;margin:1.6rem 0 .6rem">Gönderilenler</h2>
<div class="card tbl-wrap" style="max-width:640px"><table class="tbl"><tbody>@forelse($sent as $n)<tr><td><b>{{ $n->title }}</b></td><td><span class="badge badge-brand">{{ $n->recipients_count }} kişi</span></td><td style="color:#94a3b8">{{ $n->created_at->diffForHumans() }}</td></tr>@empty<tr><td><x-empty icon="🔔" title="Henüz bildirim gönderilmedi" /></td></tr>@endforelse</tbody></table></div>
@endsection
