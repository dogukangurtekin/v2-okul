@props(['label', 'for' => null, 'help' => null, 'full' => false])
<div class="fg {{ $full ? 'full' : '' }}"><label class="lbl" @if($for) for="{{ $for }}" @endif>{{ $label }}</label>{{ $slot }}@if($help)<small class="help">{{ $help }}</small>@endif</div>
