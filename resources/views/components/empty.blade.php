@props(['icon' => '🗂️', 'title', 'text' => null])
<div class="empty"><div class="empty-i">{{ $icon }}</div><b style="color:var(--ink);display:block">{{ $title }}</b>@if($text)<p style="margin:.3rem 0 0;font-size:.9rem">{{ $text }}</p>@endif @if(!$slot->isEmpty())<div style="margin-top:1rem">{{ $slot }}</div>@endif</div>
