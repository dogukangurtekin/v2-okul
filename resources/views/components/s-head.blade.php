@props(['icon' => '✨', 'title', 'sub' => null])
<div class="s-head"><span class="s-head-i">{{ $icon }}</span><div><h1>{{ $title }}</h1>@if($sub)<p>{{ $sub }}</p>@endif</div></div>
