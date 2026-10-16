@extends('layouts.app')
@section('title', $name)
@section('content')
<h1 class="text-2xl font-bold">{{ $cfg['emoji'] }} {{ $name }}</h1>
<p class="text-slate-500 mt-2">Bu oyun henüz v2'ye taşınmadı. v1 paketini <code>public/runner-assets/{{ $slug }}/index.html</code> olarak kopyalarsanız burada otomatik açılır.</p>
@endsection
