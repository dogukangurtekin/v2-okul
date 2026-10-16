<div class="toasts" aria-live="polite">
    @if(session('ok'))<div class="toast ok"><span class="t-i">✅</span><div>{{ session('ok') }}</div><button class="t-x" aria-label="Kapat">×</button></div>@endif
    @if($errors->any() && ! View::hasSection('inline-errors'))<div class="toast err"><span class="t-i">⚠️</span><div>@foreach($errors->all() as $e)@if($loop->index < 3)<div>{{ $e }}</div>@endif @endforeach</div><button class="t-x" aria-label="Kapat">×</button></div>@endif
</div>
<div id="cookie" class="cookie" hidden><span>Oturum çerezleri dışında izleme çerezi kullanmıyoruz. <a href="{{ route('legal.cookies') }}">Çerez politikası</a></span><button id="cookie-ok" type="button">Tamam</button></div>
@if(session('celebrate'))<script>window.addEventListener('load', function () { if (window.UI) UI.celebrate({}); });</script>@endif
