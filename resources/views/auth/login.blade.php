@extends('layouts.app')
@section('title', 'Giriş')
@section('content')
<div class="min-h-screen flex items-center justify-center">
    <form method="POST" action="{{ route('login.attempt') }}" class="bg-white p-8 rounded-xl shadow w-96 space-y-4">
        @csrf
        <h1 class="text-xl font-bold">Giriş Yap</h1>
        @if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <input name="email" value="{{ old('email') }}" placeholder="E-posta" class="w-full border rounded px-3 py-2" required>
        <input name="password" type="password" placeholder="Şifre" class="w-full border rounded px-3 py-2" required>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Beni hatırla</label>
        <button class="w-full bg-indigo-600 text-white rounded py-2">Giriş</button>
    <div class="border-t pt-3 text-center">
  <button type="button" id="qrbtn" class="text-indigo-600 text-sm">📱 QR ile giriş (öğretmen onayıyla)</button>
  <div id="qrbox" class="hidden mt-3"><div id="qrcode" class="inline-block"></div><p id="qrmsg" class="text-xs text-slate-500 mt-2"></p></div>
</div>
    <div class="text-xs text-slate-500 text-center space-x-2"><a class="underline" href="{{ route('demo.form') }}">Demo talep et</a><a class="underline" href="{{ route('legal.privacy') }}">Gizlilik</a><a class="underline" href="{{ route('legal.terms') }}">Şartlar</a></div>
    </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.getElementById('qrbtn').onclick = async () => {
  const box = document.getElementById('qrbox'), msg = document.getElementById('qrmsg'), qr = document.getElementById('qrcode');
  box.classList.remove('hidden'); qr.innerHTML = ''; msg.textContent = 'Hazırlanıyor…';
  const j = await (await fetch(@json(route('qr.start')), {method:'POST', headers:{'X-CSRF-TOKEN':@json(csrf_token()),Accept:'application/json'}})).json();
  new QRCode(qr, {text: j.url, width: 180, height: 180});
  msg.textContent = 'Öğretmeninizden bu kodu okutup onaylamasını isteyin.';
  const stop = Date.now() + j.ttl * 1000;
  const tick = async () => {
    if (Date.now() > stop) { msg.textContent = 'Kodun süresi doldu. Tekrar deneyin.'; qr.innerHTML = ''; return; }
    const s = await (await fetch(@json(url('/qr/durum')) + '/' + j.token + '?poll=' + j.poll, {headers:{Accept:'application/json'}})).json();
    if (s.status === 'approved') {
      const r = await (await fetch(@json(url('/qr/tamamla')) + '/' + j.token, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token()),Accept:'application/json'}, body: JSON.stringify({poll: j.poll})})).json();
      location.href = r.redirect; return;
    }
    if (s.status === 'expired') { msg.textContent = 'Kod geçersiz. Tekrar deneyin.'; return; }
    setTimeout(tick, 2000);
  };
  tick();
};
</script>
@endsection
