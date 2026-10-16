@extends('layouts.app')
@section('title', 'Duyuru Yönetimi')
@section('content')
<x-page-head title="Duyuru Yönetimi" sub="Öğrenci, öğretmen ya da herkese duyuru yayınlayın"><a href="{{ route('announcements.create') }}" class="btn btn-primary"><x-icon name="plus" style="width:16px;height:16px" />Yeni duyuru</a></x-page-head>
<div class="card tbl-wrap"><table class="tbl"><thead><tr><th>Başlık</th><th>Kitle</th><th>Yayın</th><th></th></tr></thead><tbody>
@forelse($items as $a)<tr><td><b>{{ $a->title }}</b></td><td><span class="badge badge-brand">{{ \App\Models\Announcement::AUDIENCES[$a->audience] }}</span></td><td style="color:#94a3b8">{{ $a->published_at?->format('d.m.Y H:i') }}</td>
<td><div class="ra"><a class="ibtn" href="{{ route('announcements.edit', $a) }}">Düzenle</a><x-del :action="route('announcements.destroy', $a)" icon /></div></td></tr>
@empty<tr><td colspan="4"><x-empty icon="📢" title="Henüz duyuru yok" /></td></tr>@endforelse</tbody></table></div>
{{ $items->links() }}
@endsection
