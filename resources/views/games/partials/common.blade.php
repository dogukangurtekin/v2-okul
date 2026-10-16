<script>
// Ortak oyun yardimcisi: seviye sekmeleri + sonuc gonderme (ogrenci: kayit+XP, personel: yalniz kontrol).
window.GAME = {
  done: new Set(@json($done)), can: @json($canRecord), level: 0,
  csrf: @json(csrf_token()),
  url: @json($canRecord ? route('games.result', $slug) : route('games.check', $slug)),
  async submit(level, seconds, payload) {
    const r = await fetch(this.url, {method:'POST', headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':this.csrf}, body: JSON.stringify({level, seconds, payload})});
    if (!r.ok) return {ok:false, messages:['Sunucu hatası ('+r.status+'). Önceki seviyeyi tamamlamış olmalısın.']};
    const j = await r.json();
    if (j.ok && this.can) { this.done.add(level); if (window.UI && j.repeat === false) UI.celebrate({xp: j.xp}); }
    if (!j.ok) { const m = document.getElementById('msg'); if (m) { m.classList.remove('shake'); void m.offsetWidth; m.classList.add('shake'); } }
    return j;
  },
  tabs(el, count, onPick) {
    el.innerHTML = '';
    for (let i = 0; i < count; i++) {
      const b = document.createElement('button'), open = !this.can || i === 0 || this.done.has(i);
      b.textContent = (this.done.has(i + 1) ? '✅ ' : '') + (i + 1); b.disabled = !open && !this.done.has(i + 1);
      b.className = 'px-3 py-1 rounded border ' + (i === this.level ? 'bg-indigo-600 text-white' : 'bg-white') + (b.disabled ? ' opacity-40' : '');
      b.onclick = () => { this.level = i; onPick(i); }; el.appendChild(b);
    }
  },
  esc: s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])),
};
</script>
