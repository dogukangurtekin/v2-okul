@extends('layouts.app')
@section('title', 'Duyuru')
@section('content')
<x-page-head :title="$a->exists ? 'Duyuruyu düzenle' : 'Yeni duyuru'" :back="[route('announcements.index'), 'Duyuru yönetimi']" />
<form method="POST" action="{{ $a->exists ? route('announcements.update', $a) : route('announcements.store') }}" class="card form-card">@csrf @if($a->exists) @method('PUT') @endif
<div class="fgrid">
<x-fg label="Başlık" full><input name="title" value="{{ old('title', $a->title) }}" placeholder="Başlık" class="field" required></x-fg>
<x-fg label="Duyuru metni" full><textarea name="content" rows="6" class="field" required>{{ old('content', $a->content) }}</textarea></x-fg>
<x-fg label="Hedef kitle"><select name="audience" class="field">@foreach(\App\Models\Announcement::AUDIENCES as $k => $v)<option value="{{ $k }}" @selected(old('audience', $a->audience) === $k)>{{ $v }}</option>@endforeach</select></x-fg>
<x-fg label="Yayın zamanı" help="Boş bırakırsanız hemen yayınlanır."><input type="datetime-local" name="published_at" value="{{ old('published_at', $a->published_at?->format('Y-m-d\TH:i')) }}" class="field"></x-fg>
</div>
@unless($a->exists)<label class="chk" style="margin-top:1rem"><input type="checkbox" name="notify" value="1" checked> Hedef kitleye bildirim de gönder</label>@endunless
<div class="form-actions"><button class="btn btn-primary">Kaydet</button><a class="btn btn-ghost" href="{{ route('announcements.index') }}">Vazgeç</a></div></form>
@endsection
