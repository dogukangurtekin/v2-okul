@props(['name', 'size' => 34])
@php($i = mb_strtoupper(mb_substr(collect(explode(' ', trim($name)))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''), 0, 2)))
<span class="avatar-i" style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * .36) }}px;background:linear-gradient(135deg,hsl({{ crc32($name) % 360 }} 62% 38%),hsl({{ (crc32($name) + 50) % 360 }} 62% 44%))">{{ $i }}</span>
