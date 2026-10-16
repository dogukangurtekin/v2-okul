@extends('layouts.app')
@section('title', $name)
@section('content')
<x-page-head :title="$cfg['emoji'].' '.$name" :back="[route('games.index'), 'Etkinlikler']" />
<iframe src="{{ route('block-builder.index') }}" class="card" style="width:100%;height:80vh;border:0;background:#0a1738" allow="fullscreen"></iframe>
@endsection
