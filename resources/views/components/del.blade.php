@props(['action', 'confirm' => 'Silinsin mi?', 'label' => 'Sil', 'icon' => false, 'method' => 'DELETE'])
<form method="POST" action="{{ $action }}" style="display:inline" onsubmit="return confirm(@js($confirm))">@csrf @method($method)
  <button class="{{ $icon ? 'ibtn danger' : 'btn btn-danger btn-sm' }}" title="{{ $label }}">{{ $label }}</button></form>
