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
      else if (t === 'radio') { const c = p.querySelector('input[type=radio]:checked'); out[k] = c ? c.value : ''; }
      else if (t === 'checkbox') out[k] = $$('input[type=checkbox]:checked', p).map(c => c.value);
      else if (t === 'table') out[k] = $$('tbody tr', p).map(tr => $$('input', tr).map(i => i.value)).filter(r => r.some(x => x.trim() !== ''));
      else if (t !== 'file') { const i = p.querySelector('input,select'); if (i) out[k] = i.value; }
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
