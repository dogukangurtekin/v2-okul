/* v2-okul arayüz yardımcıları: menü, bildirim, sayaçlar, XP çubukları, konfeti. Bağımlılık yok; her şey isteğe bağlı (JS kapalıysa sayfa yine çalışır). */
(function () {
  'use strict';
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // --- Personel kenar menüsü (mobil) ---
  var shell = document.querySelector('.shell-staff');
  if (shell) {
    $$('[data-nav-toggle]').forEach(function (b) { b.addEventListener('click', function () { shell.classList.toggle('nav-open'); }); });
    $$('.scrim').forEach(function (s) { s.addEventListener('click', function () { shell.classList.remove('nav-open'); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') shell.classList.remove('nav-open'); });
  }

  // --- Açılır menüler (<details data-menu>) dışarı tıklayınca kapanır ---
  document.addEventListener('click', function (e) {
    $$('details[data-menu][open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });

  // --- Bildirimler (toast) ---
  function dismiss(t) { t.classList.add('leaving'); setTimeout(function () { t.remove(); }, 320); }
  function wireToast(t) {
    var x = t.querySelector('.t-x'); if (x) x.addEventListener('click', function () { dismiss(t); });
    if (t.classList.contains('ok')) setTimeout(function () { dismiss(t); }, 5200);
  }
  $$('.toast').forEach(wireToast);

  var UI = window.UI = {
    toast: function (html, type) {
      var box = document.querySelector('.toasts');
      if (!box) { box = document.createElement('div'); box.className = 'toasts'; document.body.appendChild(box); }
      var t = document.createElement('div'); t.className = 'toast ' + (type || 'ok');
      t.innerHTML = '<span class="t-i">' + (type === 'err' ? '⚠️' : '✅') + '</span><div>' + html + '</div><button class="t-x" aria-label="Kapat">×</button>';
      box.appendChild(t); wireToast(t); return t;
    },

    // Konfeti + XP bildirimi (oyun/ders başarısında çağrılır)
    celebrate: function (opts) {
      opts = opts || {};
      if (opts.xp) UI.toast('<b>+' + opts.xp + ' XP</b> kazandın! 🎉', 'ok');
      if (reduce) return;
      var c = document.createElement('canvas');
      c.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:200';
      document.body.appendChild(c);
      var ctx = c.getContext && c.getContext('2d'); if (!ctx) { c.remove(); return; }
      var W = c.width = innerWidth, H = c.height = innerHeight;
      var colors = ['#7c3aed', '#db2777', '#f59e0b', '#22c55e', '#06b6d4', '#ef4444'];
      var ps = []; for (var i = 0; i < 130; i++) ps.push({ x: W / 2 + (Math.random() - .5) * 160, y: H * .35, vx: (Math.random() - .5) * 15, vy: -Math.random() * 15 - 4, s: 5 + Math.random() * 7, r: Math.random() * 6, vr: (Math.random() - .5) * .4, c: colors[i % colors.length] });
      var t0 = null;
      (function frame(t) {
        if (t0 === null) t0 = t; var dt = t - t0;
        ctx.clearRect(0, 0, W, H);
        ps.forEach(function (p) { p.vy += .42; p.x += p.vx; p.y += p.vy; p.vx *= .99; p.r += p.vr; ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r); ctx.fillStyle = p.c; ctx.globalAlpha = Math.max(0, 1 - dt / 2600); ctx.fillRect(-p.s / 2, -p.s / 3, p.s, p.s * .6); ctx.restore(); });
        if (dt < 2600) requestAnimationFrame(frame); else c.remove();
      })(performance.now());
    },

    // data-count="123": 0'dan o sayıya sayar
    count: function (el) {
      var to = parseFloat(el.getAttribute('data-count')) || 0, dec = (el.getAttribute('data-count').split('.')[1] || '').length;
      if (reduce || to === 0) { el.textContent = to.toFixed(dec); return; }
      var t0 = null, dur = 900;
      requestAnimationFrame(function step(t) {
        if (t0 === null) t0 = t; var k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
        el.textContent = (to * e).toFixed(dec); if (k < 1) requestAnimationFrame(step);
      });
    }
  };

  $$('[data-count]').forEach(UI.count);
  // data-pct="62": XP çubuğu 0'dan dolar
  $$('[data-pct]').forEach(function (el) { var p = Math.max(0, Math.min(100, parseFloat(el.getAttribute('data-pct')) || 0)); el.style.width = '0%'; requestAnimationFrame(function () { requestAnimationFrame(function () { el.style.width = p + '%'; }); }); });

  // Çerez bildirimi
  var ck = document.getElementById('cookie');
  try { if (ck && !localStorage.getItem('cookie_ok')) { ck.hidden = false; var b = document.getElementById('cookie-ok'); if (b) b.onclick = function () { localStorage.setItem('cookie_ok', '1'); ck.remove(); }; } } catch (e) {}

  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(function () {});
})();
