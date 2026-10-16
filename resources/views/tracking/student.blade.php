@extends('layouts.app')
@section('title', 'Etkinlik geçmişi')
@section('content')
<x-page-head :title="$student->user->name" :sub="$student->schoolClass?->label.' · Toplam süre '.intdiv($student->total_seconds, 60).' dk'" :back="[route('tracking.index'), 'Canlı takip']"><a class="btn btn-ghost" href="{{ route('reports.show', $student) }}">Karne</a></x-page-head>
<div class="card tbl-wrap" style="max-width:760px"><table class="tbl"><tbody>
@forelse($logs as $l)<tr><td style="color:#94a3b8;white-space:nowrap">{{ $l->created_at->format('d.m H:i:s') }}</td><td>{{ $l->route_name }}</td><td style="color:var(--muted)">/{{ $l->label }}</td></tr>
@empty<tr><td><x-empty icon="🕘" title="Kayıt yok" /></td></tr>@endforelse
</tbody></table></div>
@endsection
