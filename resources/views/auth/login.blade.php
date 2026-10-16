@extends('layouts.app')
@section('title', 'Giriş')
@section('inline-errors', '1')
@section('content')
<div class="auth">
  <section class="auth-hero">
    <div class="orb o1"></div><div class="orb o2"></div><div class="orb o3"></div>
    <span class="auth-emoji" style="top:18%;right:12%">🚀</span><span class="auth-emoji" style="top:46%;right:30%;animation-delay:-1.5s">🎮</span>
    <span class="auth-emoji" style="bottom:20%;right:10%;animation-delay:-2.5s">⭐</span><span class="auth-emoji" style="top:12%;left:46%;animation-delay:-3s;font-size:2rem">💡</span>
    <div style="display:flex;align-items:center;gap:.7rem"><span class="sb-logo" style="width:44px;height:44px;font-size:.95rem">&lt;/&gt;</span><b style="font-size:1.25rem;letter-spacing:-.02em">Bilişim Kod</b></div>
    <div>
      <h2>Kodlamayı<br>oyun gibi öğren.</h2>
      <p>Dersleri bitir, görevleri çöz, XP topla, rozetleri aç, arkadaşlarınla yarış. Öğretmenler için de sınıf yönetimi tek yerde.</p>
      <div class="auth-feats"><span>🎮 Kodlama oyunları</span><span>🏅 Rozet ve seviyeler</span><span>⚡ Canlı yarışmalar</span><span>📊 Öğretmen paneli</span></div>
    </div>
    <small style="opacity:.7">© {{ date('Y') }} Bilişim Kod</small>
  </section>
  <section class="auth-form">
    <div class="auth-box">
      <h1>Tekrar hoş geldin 👋</h1>
      <p style="color:var(--muted);margin:0 0 .6rem">Hesabınla giriş yap.</p>
      <form method="POST" action="{{ route('login.attempt') }}">@csrf
        <label class="lbl" for="email">E-posta veya kullanıcı adı</label>
        <input id="email" class="field" name="email" value="{{ old('email') }}" autocomplete="username" autofocus required>
        <label class="lbl" for="password">Şifre</label>
        <input id="password" class="field" name="password" type="password" autocomplete="current-password" required>
        <label style="display:flex;align-items:center;gap:.5rem;margin:.9rem 0 1.1rem;font-size:.88rem;color:#475569"><input type="checkbox" name="remember"> Beni hatırla</label>
        @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
        <button class="btn btn-primary" style="width:100%;padding:.95rem;font-size:1rem;margin-top:.8rem">Giriş yap <x-icon name="arrow" style="width:18px;height:18px" /></button>
      </form>
      <div style="margin:1.4rem 0 .4rem;display:flex;align-items:center;gap:.8rem;color:#94a3b8;font-size:.8rem"><span style="flex:1;height:1px;background:var(--line)"></span>veya<span style="flex:1;height:1px;background:var(--line)"></span></div>
      <button type="button" id="qrbtn" class="btn btn-ghost" style="width:100%">📱 QR ile giriş <span style="font-weight:500;color:var(--muted)">(öğretmen onayıyla)</span></button>
      <div id="qrbox" class="hidden" style="text-align:center;margin-top:1rem"><div id="qrcode" style="display:inline-block;padding:10px;background:#fff;border-radius:16px;border:1px solid var(--line)"></div><p id="qrmsg" style="font-size:.8rem;color:var(--muted);margin-top:.6rem"></p></div>
      <p style="margin-top:1.6rem;text-align:center;font-size:.8rem;color:#94a3b8"><a href="{{ route('demo.form') }}">Demo talep et</a> · <a href="{{ route('legal.privacy') }}">Gizlilik</a> · <a href="{{ route('legal.terms') }}">Şartlar</a></p>
    </div>
  </section>
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
