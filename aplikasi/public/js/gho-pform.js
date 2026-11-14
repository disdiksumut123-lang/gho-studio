/* Gho Studio: formulir dinamis peserta (simpan otomatis + kirim) dan dokumen (susun, simpan). */
(function () {
'use strict';
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const T = k => (window.GHO && GHO.t && GHO.t[k]) || k;
const mark = (txt) => { const sv = document.getElementById('saved'); if (sv) { sv.hidden = false; sv.querySelector('span').textContent = txt; } };

const f = document.getElementById('pform');
if (f) {
  const collect = () => {
    const out = {};
    $$('.pf', f).forEach(p => {
      const k = p.dataset.k, t = p.dataset.type;
      if (t === 'textarea') { const ed = p.querySelector('.ed'); if (ed) { if (window.ghoEditorSync) ghoEditorSync(ed.id); out[k] = ed.querySelector('input[type=hidden]').value; } }
      else if (t === 'radio' || t === 'linear_scale') { const c = p.querySelector('input[type=radio]:checked'); out[k] = c ? c.value : ''; }
      else if (t === 'checkbox') out[k] = $$('input[type=checkbox]:checked', p).map(c => c.value);
      else if (t === 'table') out[k] = $$('tbody tr', p).map(tr => $$('input', tr).map(i => i.value)).filter(r => r.some(x => x.trim() !== ''));
      else if (t === 'file' || t === 'photo' || t === 'narasi') { /* tidak dikumpulkan sebagai nilai JSON: berkas lewat unggahan, narasi bukan isian */ }
      else { const i = p.querySelector('input,select'); if (i) out[k] = i.value; }
    });
    return out;
  };
  let timer, last = '';
  const save = async () => {
    const v = collect(), s = JSON.stringify(v); if (s === last) return; last = s; mark(T('saving'));
    const r = await ghoPost(f.dataset.save, {values: v});
    mark(r.error ? T('fail') : T('saved') + (r.at ? ' ' + r.at : ''));
  };
  const later = () => { clearTimeout(timer); timer = setTimeout(save, 1500); };
  f.addEventListener('input', later); f.addEventListener('change', later);
  f.addEventListener('click', e => {
    const add = e.target.closest('[data-addrow]'), del = e.target.closest('[data-delrow]');
    if (add) { const tb = add.closest('.pf').querySelector('tbody'), tr = tb.rows[tb.rows.length - 1].cloneNode(true); $$('input', tr).forEach(i => { i.value = ''; }); tb.appendChild(tr); }
    if (del) { const tb = del.closest('tbody'); if (tb.rows.length > 1) del.closest('tr').remove(); else $$('input', del.closest('tr')).forEach(i => { i.value = ''; }); later(); }
  });
  f.addEventListener('submit', () => { clearTimeout(timer); document.getElementById('pvalues').value = JSON.stringify(collect()); });

  /* tanda tangan: gambar di kanvas, simpan sebagai data URL di input tersembunyi */
  $$('.sig-pad', f).forEach(pad => {
    const cv = pad.querySelector('canvas'), hid = pad.querySelector('input[type=hidden]'), ctx = cv.getContext('2d');
    ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.strokeStyle = '#111';
    if (hid.value) { const img = new Image(); img.onload = () => ctx.drawImage(img, 0, 0, cv.width, cv.height); img.src = hid.value; }
    let drawing = false, lx = 0, ly = 0;
    const pos = e => { const r = cv.getBoundingClientRect(), t = e.touches ? e.touches[0] : e; return [(t.clientX - r.left) * (cv.width / r.width), (t.clientY - r.top) * (cv.height / r.height)]; };
    const start = e => { e.preventDefault(); drawing = true; [lx, ly] = pos(e); };
    const move = e => { if (!drawing) return; e.preventDefault(); const [x, y] = pos(e); ctx.beginPath(); ctx.moveTo(lx, ly); ctx.lineTo(x, y); ctx.stroke(); [lx, ly] = [x, y]; };
    const end = () => { if (!drawing) return; drawing = false; hid.value = cv.toDataURL('image/png'); hid.dispatchEvent(new Event('input', {bubbles: true})); };
    cv.addEventListener('mousedown', start); cv.addEventListener('mousemove', move); addEventListener('mouseup', end);
    cv.addEventListener('touchstart', start); cv.addEventListener('touchmove', move); cv.addEventListener('touchend', end);
    pad.querySelector('[data-sig-clear]').addEventListener('click', () => { ctx.clearRect(0, 0, cv.width, cv.height); hid.value = ''; hid.dispatchEvent(new Event('input', {bubbles: true})); });
  });
}

/* ---------- halaman dokumen ---------- */
const d = document.getElementById('docpage');
if (d) {
  const sec = k => d.querySelector('.dsec[data-key="' + k + '"] .dbody');
  $$('button[data-compose]', d).forEach(b => b.addEventListener('click', async () => {
    const k = b.dataset.compose, old = b.innerHTML; b.disabled = true; b.textContent = T('composing');
    const r = await ghoPost(d.dataset.composeurl, {key: k});
    b.disabled = false; b.innerHTML = old;
    if (r.error) { ghoToast(r.error); return; }
    sec(k).innerHTML = r.html; b.lastChild.textContent = T('recompose'); mark(T('saved'));
  }));
  const all = document.getElementById('composeAll');
  if (all) all.addEventListener('click', async () => { for (const b of $$('button[data-compose]', d)) { b.click(); await new Promise(res => { const w = setInterval(() => { if (!b.disabled) { clearInterval(w); res(); } }, 300); }); } });
  let timer;
  d.addEventListener('input', () => { clearTimeout(timer); mark(T('saving')); timer = setTimeout(async () => {
    const s = {}; $$('.dsec', d).forEach(x => { const b = x.querySelector('.dbody'); if (!b.querySelector('.empty-sec')) s[x.dataset.key] = b.innerHTML; });
    const r = await ghoPost(d.dataset.update, {sections: s}); mark(r.error ? T('fail') : T('saved'));
  }, 1500); });
}
})();
