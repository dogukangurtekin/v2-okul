@props(['title', 'sub' => null, 'back' => null])
<div class="page-head">
  <div>@if($back)<a href="{{ $back[0] }}" class="crumb">← {{ $back[1] }}</a>@endif<h1>{{ $title }}</h1>@if($sub)<p class="page-sub">{{ $sub }}</p>@endif</div>
  @if(!$slot->isEmpty())<div class="page-actions">{{ $slot }}</div>@endif
</div>
