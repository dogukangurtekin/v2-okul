@php($cssV = @filemtime(public_path('css/app.css')) ?: 1)
<!DOCTYPE html>
<html lang="tr"><head>@include('layouts.partials.head')</head>
<body class="guest-wrap" style="display:grid;place-items:center;text-align:center;padding:1.5rem">
<div style="max-width:440px"><div style="font-size:4.5rem;animation:bob 3s ease-in-out infinite">🛠️</div>
<h1 style="margin:.4rem 0">Küçük bir bakım molası</h1>
<p style="color:var(--muted);line-height:1.6">{{ $message ?: 'Kısa süre sonra yeniden hizmetinizdeyiz.' }}</p>
<a class="btn btn-ghost" style="margin-top:1rem" href="{{ route('login') }}">Yönetici girişi</a></div></body></html>
