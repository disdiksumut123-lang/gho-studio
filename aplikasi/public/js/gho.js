/* Gho Studio: skrip halaman (tanpa build, tanpa framework). */
(function () {
'use strict';
const G = window.GHO || {csrf: '', t: {}};
const T = k => (G.t && G.t[k]) || k;
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
window.ghoEsc = esc;

let toastT;
window.ghoToast = msg => { const t = $('#toast'); if (!t) { alert(msg); return; } t.textContent = msg; t.classList.add('show'); clearTimeout(toastT); toastT = setTimeout(() => t.classList.remove('show'), 3600); };

window.ghoPost = async (url, data) => {
  try {
    const r = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': G.csrf, 'X-Requested-With': 'XMLHttpRequest'}, body: JSON.stringify(data || {})});
    const j = await r.json().catch(() => ({}));
    if (!r.ok && !j.error) j.error = j.message || ('Error ' + r.status);
    return j;
  } catch (e) { return {error: T('fail')}; }
};

window.ghoSeq = async (el, url) => { const r = await ghoPost(url); if (r.error) { el.checked = !el.checked; ghoToast(r.error); } };

/* ---------- penanda simpan ---------- */
const saved = {
  busy() { const s = $('#saved'); if (!s) return; s.hidden = false; s.classList.add('busy'); s.querySelector('span').textContent = T('saving'); },
  ok(at) { const s = $('#saved'); if (!s) return; s.classList.remove('busy'); s.querySelector('span').textContent = T('saved') + (at ? ' ' + at : ''); },
  fail() { const s = $('#saved'); if (!s) return; s.classList.add('busy'); s.querySelector('span').textContent = T('fail'); }
};

/* ---------- persamaan ---------- */
let mlState = 'idle';
function eqRender(lx) {
  if (mlState === 'ok' && window.MathLive && MathLive.convertLatexToMarkup) { try { return MathLive.convertLatexToMarkup(lx); } catch (e) {} }
  let h = esc(lx);
  for (let k = 0; k < 3; k++) h = h.replace(/\\frac\{([^{}]*)\}\{([^{}]*)\}/g, '<span class="frac"><span>$1</span><span>$2</span></span>');
  return h.replace(/\\sqrt\{([^{}]*)\}/g, '√<span style="border-top:1px solid">$1</span>').replace(/\^\{([^{}]*)\}/g, '<sup>$1</sup>').replace(/_\{([^{}]*)\}/g, '<sub>$1</sub>')
    .replace(/\\sum/g, 'Σ').replace(/\\int/g, '∫').replace(/\\times/g, '×').replace(/\\div/g, '÷').replace(/\\cdot/g, '·').replace(/\\le(q)?/g, '≤').replace(/\\ge(q)?/g, '≥')
    .replace(/\\pi/g, 'π').replace(/\\alpha/g, 'α').replace(/\\beta/g, 'β').replace(/\\Delta/g, 'Δ').replace(/\\text\{([^{}]*)\}/g, '$1').replace(/\\%/g, '%').replace(/\\,/g, ' ').replace(/\\[a-zA-Z]+/g, '').replace(/[{}]/g, '');
}
const reEq = (root = document) => $$('.eq[data-latex]', root).forEach(e => { e.innerHTML = eqRender(e.dataset.latex); });
function loadML(cb) {
  if (mlState === 'ok' || mlState === 'fail') { cb(); return; }
  document.addEventListener('ml-ready', cb, {once: true});
  if (mlState === 'loading') return;
  mlState = 'loading';
  const sc = document.createElement('script'); sc.src = 'https://cdn.jsdelivr.net/npm/mathlive@0.101.0/dist/mathlive.min.js'; sc.defer = true;
  sc.onload = () => { mlState = customElements.get('math-field') ? 'ok' : 'fail'; document.dispatchEvent(new Event('ml-ready')); reEq(); };
  sc.onerror = () => { mlState = 'fail'; document.dispatchEvent(new Event('ml-ready')); };
  document.head.appendChild(sc);
}

/* ---------- editor ---------- */
const SYM = ['Σ','√','π','±','×','÷','≤','≥','≠','≈','∞','α','β','γ','Δ','θ','µ','→','⇒','°','½','¼','²','³','%','‰','€','©','•','✓'];
const EQT = [['a/b','\\frac{a}{b}'],['√x','\\sqrt{x}'],['x²','x^{2}'],['xᵢ','x_{i}'],['Σ','\\sum_{i=1}^{n} x_i'],['∫','\\int_{a}^{b} f(x)\\,dx'],['[ ]','\\begin{pmatrix}a & b\\\\ c & d\\end{pmatrix}'],['%','\\frac{x}{y}\\times 100\\%']];

function Editor(root) {
  const area = $('.ed-area', root), pop = $('.ed-pop', root), hidden = root.querySelector('input[type=hidden][name="' + root.dataset.name + '"]'), wc = $('.wc', root);
  let range = null, eqEdit = null, timer = null, lastSent = null;
  document.addEventListener('selectionchange', () => { const s = getSelection(); if (s.rangeCount && area.contains(s.getRangeAt(0).commonAncestorContainer)) range = s.getRangeAt(0).cloneRange(); });
  const restore = () => { area.focus(); if (range) { const s = getSelection(); s.removeAllRanges(); s.addRange(range); } };
  const cmd = (c, v) => { restore(); try { document.execCommand(c, false, v); } catch (e) {} changed(); };
  const html = h => { restore(); try { document.execCommand('insertHTML', false, h); } catch (e) {} changed(); };
  const value = () => { const c = area.cloneNode(true); $$('.eq', c).forEach(e => { e.innerHTML = ' '; }); return c.innerHTML.trim() === '<br>' ? '' : c.innerHTML; };
  const count = () => { if (wc) wc.textContent = ((area.innerText.trim().match(/\S+/g) || []).length) + ' ' + T('words'); };
  const sync = () => { if (hidden) hidden.value = value(); };
  async function save() {
    const v = value(); if (v === lastSent) return; saved.busy();
    const r = await ghoPost(root.dataset.save, {body: v});
    if (r.error) saved.fail(); else { lastSent = v; saved.ok(r.at); }
  }
  function changed() { count(); sync(); if (root.dataset.save) { saved.busy(); clearTimeout(timer); timer = setTimeout(save, 1500); } }
  area.addEventListener('input', changed);
  area.addEventListener('click', e => { const q = e.target.closest('.eq'); if (q) { eqEdit = q; showPop('eq', q.dataset.latex); } });
  area.addEventListener('paste', e => { const t = e.clipboardData && e.clipboardData.getData('text/html'); if (t) { e.preventDefault(); const d = document.createElement('div'); d.innerHTML = t;
      $$('script,style,meta,link,iframe,object', d).forEach(x => x.remove()); $$('*', d).forEach(x => [...x.attributes].forEach(a => { if (/^on/i.test(a.name) || a.name === 'class' && !x.classList.contains('eq')) x.removeAttribute(a.name); }));
      document.execCommand('insertHTML', false, d.innerHTML); changed(); } });
  $$('.ed-bar [data-cmd]', root).forEach(b => {
    if (b.tagName === 'BUTTON') { b.addEventListener('mousedown', e => e.preventDefault()); b.addEventListener('click', () => cmd(b.dataset.cmd)); }
    else b.addEventListener('change', () => { if (b.value) cmd(b.dataset.cmd, b.value); if (b.tagName === 'SELECT') b.selectedIndex = 0; });
  });
  $$('.ed-bar [data-act]', root).forEach(b => { b.addEventListener('mousedown', e => e.preventDefault()); b.addEventListener('click', () => {
    const a = b.dataset.act;
    if (a === 'table') html('<table><tr><th>&nbsp;</th><th>&nbsp;</th></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr></table><p></p>');
    else { eqEdit = null; showPop(a); }
  }); });
  function showPop(kind, latex) {
    if (pop.dataset.kind === kind && !pop.hidden && !latex) { pop.hidden = true; return; }
    pop.dataset.kind = kind; pop.hidden = false;
    if (kind === 'sym') { pop.innerHTML = '<div class="syms">' + SYM.map(s => '<button type="button">' + s + '</button>').join('') + '</div>';
      $$('button', pop).forEach(x => { x.addEventListener('mousedown', e => e.preventDefault()); x.addEventListener('click', () => html(x.textContent)); }); }
    if (kind === 'link') { pop.innerHTML = '<div class="row" style="flex-wrap:nowrap"><input type="text" placeholder="https://"><button type="button" class="btn sm primary">OK</button></div>';
      $('button', pop).addEventListener('click', () => { const u = $('input', pop).value.trim(); if (/^https?:\/\//.test(u)) cmd('createLink', u); pop.hidden = true; }); }
    if (kind === 'eq') {
      const cur = latex || '\\frac{x}{y}\\times 100\\%';
      pop.innerHTML = '<div class="row">' + EQT.map(t => '<button type="button" class="btn sm" data-lx="' + esc(t[1]) + '">' + t[0] + '</button>').join('') + '</div><div class="eqbox"></div>' +
        '<div class="row"><button type="button" class="btn sm primary" data-ok>✓</button><button type="button" class="btn sm" data-kb>⌨</button><button type="button" class="btn sm ghost" data-close>✕</button></div>';
      const box = $('.eqbox', pop);
      const build = () => {
        if (mlState === 'ok') { box.innerHTML = '<math-field></math-field>'; $('math-field', box).value = cur; }
        else { box.innerHTML = '<input type="text" class="mono"><div class="eqprev"></div>'; const i = $('input', box), pv = $('.eqprev', box); i.value = cur; pv.innerHTML = eqRender(cur); i.addEventListener('input', () => { pv.innerHTML = eqRender(i.value); }); }
      };
      build(); loadML(build);
      const get = () => { const mf = $('math-field', box); return mf ? mf.value : $('input', box).value; };
      $$('[data-lx]', pop).forEach(x => x.addEventListener('click', () => { const mf = $('math-field', box); if (mf) { mf.executeCommand(['insert', x.dataset.lx]); mf.focus(); } else { const i = $('input', box); i.value = (i.value ? i.value + ' ' : '') + x.dataset.lx; i.dispatchEvent(new Event('input')); } }));
      $('[data-kb]', pop).addEventListener('click', () => { try { window.mathVirtualKeyboard.show(); } catch (e) { ghoToast('…'); } });
      $('[data-close]', pop).addEventListener('click', () => { pop.hidden = true; });
      $('[data-ok]', pop).addEventListener('click', () => { const lx = get().trim(); if (!lx) return;
        const h = '<span class="eq" contenteditable="false" data-latex="' + esc(lx) + '">' + eqRender(lx) + '</span>';
        if (eqEdit && eqEdit.isConnected) { eqEdit.outerHTML = h; eqEdit = null; changed(); } else html(h + '&nbsp;');
        pop.hidden = true; });
    }
  }
  // tab ketik / unggah
  $$('.edmode [data-mode]', root).forEach(b => b.addEventListener('click', () => {
    $$('.edmode button', root).forEach(x => x.classList.toggle('on', x === b));
    $('.ed-ketik', root).hidden = b.dataset.mode !== 'ketik'; $('.ed-unggah', root).hidden = b.dataset.mode !== 'unggah';
  }));
  const fi = $('.ed-unggah input[type=file]', root);
  if (fi) fi.addEventListener('change', () => { const f = fi.files[0], out = $('.fileinfo', root); if (!f) return;
    out.innerHTML = f.size > 10 * 1048576 ? '<span class="chip bad">&gt; 10 MB</span>' : '<span class="chip good">✓</span> <b>' + esc(f.name) + '</b> <span class="faint mono">' + Math.round(f.size / 1024) + ' KB</span>'; });
  reEq(area); count(); lastSent = value();
  return {set(h) { area.innerHTML = h; reEq(area); changed(); }, sync};
}
const editors = {};
window.ghoEditorSet = (id, h) => editors[id] && editors[id].set(h);
window.ghoEditorSync = id => editors[id] && editors[id].sync();
document.addEventListener('submit', e => { $$('.ed', e.target).forEach(ed => editors[ed.id] && editors[ed.id].sync()); }, true);

/* ---------- kuis: urutkan ---------- */
window.ghoMove = (btn, d) => { const it = btn.closest('.urut'), p = it.parentNode; if (d < 0 && it.previousElementSibling) p.insertBefore(it, it.previousElementSibling); else if (d > 0 && it.nextElementSibling) p.insertBefore(it.nextElementSibling, it);
  $$('.urut .n', p).forEach((n, i) => { n.textContent = i + 1; }); };

/* ---------- penyunting soal (fasilitator) ---------- */
window.ghoQuiz = {
  items: [], el: null, types: {},
  init(el, items, types) { this.el = el; this.items = items || []; this.types = types; this.render(); },
  blank(t) { return {mcq: {options: ['', '', '', ''], correct: 0}, mcq_multi: {options: ['', '', '', ''], correct: []}, tf_multi: {statements: ['', '', ''], correct: [true, false, true]},
    matching: {left: ['', '', ''], right: ['', '', ''], correct: [0, 1, 2]}, ordering: {items: ['', '', ''], correct: [0, 1, 2]}, short: {accepted: ['']}}[t]; },
  add(t) { this.items.push(Object.assign({type: t, prompt: '', explanation: ''}, this.blank(t))); this.render(); this.el.lastElementChild.scrollIntoView({behavior: 'smooth', block: 'center'}); },
  render() {
    const c = $('#qCount'); if (c) c.textContent = this.items.length + ' soal';
    this.el.innerHTML = this.items.map((q, i) => {
      const list = (key, ph) => (q[key] || []).map((v, k) => '<div class="optrow"><span class="mono faint">' + (k + 1) + '</span><input type="text" data-i="' + i + '" data-k="' + key + '" data-x="' + k + '" value="' + esc(v) + '" placeholder="' + ph + '"></div>').join('');
      let body = '';
      if (q.type === 'mcq' || q.type === 'mcq_multi') body = (q.options || []).map((v, k) => {
        const on = q.type === 'mcq' ? +q.correct === k : (q.correct || []).map(Number).includes(k);
        return '<div class="optrow"><input type="' + (q.type === 'mcq' ? 'radio' : 'checkbox') + '" name="c' + i + '" data-i="' + i + '" data-cor="' + k + '" ' + (on ? 'checked' : '') + ' title="Kunci"><input type="text" data-i="' + i + '" data-k="options" data-x="' + k + '" value="' + esc(v) + '" placeholder="Pilihan ' + (k + 1) + '"></div>'; }).join('') + '<button type="button" class="btn sm ghost" data-addopt="' + i + '">+ pilihan</button>';
      if (q.type === 'tf_multi') body = (q.statements || []).map((v, k) => '<div class="optrow"><select data-i="' + i + '" data-tf="' + k + '" style="width:auto"><option value="1" ' + (q.correct[k] ? 'selected' : '') + '>B</option><option value="0" ' + (!q.correct[k] ? 'selected' : '') + '>S</option></select><input type="text" data-i="' + i + '" data-k="statements" data-x="' + k + '" value="' + esc(v) + '" placeholder="Pernyataan"></div>').join('') + '<button type="button" class="btn sm ghost" data-addst="' + i + '">+ pernyataan</button>';
      if (q.type === 'matching') body = '<div class="faint" style="font-size:12px">Kiri dipasangkan dengan kanan pada baris yang sama. Urutan kanan diacak otomatis saat disimpan.</div>' + (q.left || []).map((v, k) => '<div class="optrow"><input type="text" data-i="' + i + '" data-k="left" data-x="' + k + '" value="' + esc(v) + '" placeholder="Kiri"><span>↔</span><input type="text" data-i="' + i + '" data-k="right" data-x="' + k + '" value="' + esc((q.right || [])[(q.correct || [])[k] ?? k] ?? '') + '" placeholder="Kanan"></div>').join('') + '<button type="button" class="btn sm ghost" data-addpair="' + i + '">+ pasangan</button>';
      if (q.type === 'ordering') body = '<div class="faint" style="font-size:12px">Tulis dalam urutan yang BENAR. Diacak otomatis untuk peserta.</div>' + list('items', 'Langkah') + '<button type="button" class="btn sm ghost" data-additem="' + i + '">+ langkah</button>';
      if (q.type === 'short') body = '<div class="faint" style="font-size:12px">Jawaban yang diterima (huruf besar/kecil tidak berpengaruh).</div>' + list('accepted', 'Jawaban') + '<button type="button" class="btn sm ghost" data-addacc="' + i + '">+ jawaban lain</button>';
      return '<div class="qed"><div class="row" style="justify-content:space-between"><span class="chip brand">' + (i + 1) + ' · ' + esc(this.types[q.type] || q.type) + '</span><span class="row"><button type="button" class="btn sm ghost" data-up="' + i + '">↑</button><button type="button" class="btn sm ghost" data-del="' + i + '">✕</button></span></div>' +
        '<textarea data-i="' + i + '" data-k="prompt" placeholder="Pertanyaan" style="min-height:60px">' + esc(q.prompt) + '</textarea><div class="opts">' + body + '</div>' +
        '<input type="text" data-i="' + i + '" data-k="explanation" value="' + esc(q.explanation || '') + '" placeholder="Pembahasan (opsional)"></div>';
    }).join('') || '<div class="empty">Belum ada soal.</div>';
    this.bind();
  },
  normalize() { // matching: simpan kanan dalam urutan acak + kunci; ordering: acak item + kunci
    this.items.forEach(q => {
      if (q.type === 'matching' && !q._norm) {
        const pairs = (q.left || []).map((l, k) => [l, (q.right || [])[(q.correct || [])[k] ?? k] ?? '']);
        const idx = pairs.map((_, k) => k).sort(() => Math.random() - .5);
        q.right = idx.map(k => pairs[k][1]); q.correct = pairs.map((_, k) => idx.indexOf(k)); q.left = pairs.map(p => p[0]); q._norm = true;
      }
      if (q.type === 'ordering' && !q._norm) {
        const right = (q.correct || []).map(k => q.items[k]);
        const idx = right.map((_, k) => k).sort(() => Math.random() - .5);
        q.items = idx.map(k => right[k]); q.correct = right.map((_, k) => idx.indexOf(k)); q._norm = true;
      }
    });
  },
  bind() {
    const self = this, it = i => self.items[+i];
    $$('[data-k]', this.el).forEach(inp => inp.addEventListener('input', () => { const q = it(inp.dataset.i), k = inp.dataset.k;
      if (inp.dataset.x !== undefined) {
        if (q.type === 'matching' && k === 'right') { q.right = q.right || []; const ci = (q.correct || [])[+inp.dataset.x] ?? +inp.dataset.x; q.right[ci] = inp.value; }
        else if (q.type === 'ordering' && k === 'items') { q.items[(q.correct || [])[+inp.dataset.x] ?? +inp.dataset.x] = inp.value; }
        else q[k][+inp.dataset.x] = inp.value;
      } else q[k] = inp.value; }));
    $$('[data-cor]', this.el).forEach(inp => inp.addEventListener('change', () => { const q = it(inp.dataset.i), k = +inp.dataset.cor;
      if (q.type === 'mcq') q.correct = k; else { const s = new Set((q.correct || []).map(Number)); inp.checked ? s.add(k) : s.delete(k); q.correct = [...s].sort(); } }));
    $$('[data-tf]', this.el).forEach(s => s.addEventListener('change', () => { it(s.dataset.i).correct[+s.dataset.tf] = s.value === '1'; }));
    const act = (attr, fn) => $$('[' + attr + ']', this.el).forEach(b => b.addEventListener('click', () => { fn(it(b.getAttribute(attr)), +b.getAttribute(attr)); self.render(); }));
    act('data-del', (q, i) => self.items.splice(i, 1));
    act('data-up', (q, i) => { if (i > 0) [self.items[i - 1], self.items[i]] = [self.items[i], self.items[i - 1]]; });
    act('data-addopt', q => q.options.push(''));
    act('data-addst', q => { q.statements.push(''); q.correct.push(true); });
    act('data-addpair', q => { q.left.push(''); q.right.push(''); q.correct.push(q.right.length - 1); });
    act('data-additem', q => { q.items.push(''); q.correct.push(q.items.length - 1); });
    act('data-addacc', q => q.accepted.push(''));
  }
};
// Saat disimpan, soal diacak bila perlu. Soal dalam urutan "benar" di layar disimpan dengan kunci yang sesuai.
document.addEventListener('submit', e => { if (e.target.querySelector('[name=questions]') && window.ghoQuiz.el) {
  ghoQuiz.items.forEach(q => { if (q.type === 'matching' || q.type === 'ordering') { // ubah tampilan ke bentuk "benar" dulu
      if (q.type === 'ordering') { q.items = (q.correct || q.items.map((_, k) => k)).map(k => q.items[k]); q.correct = q.items.map((_, k) => k); }
      if (q.type === 'matching') { q.right = q.left.map((_, k) => q.right[(q.correct || [])[k] ?? k] ?? ''); q.correct = q.left.map((_, k) => k); }
      delete q._norm; } });
  ghoQuiz.normalize(); ghoQuiz.items.forEach(q => delete q._norm);
  e.target.questions.value = JSON.stringify(ghoQuiz.items); } }, true);

window.ghoGenQuiz = async url => {
  const material = $('#genMat').value.trim(), n = +$('#genN').value || 10, level = $('#genLv').value, types = $$('#genTypes input:checked').map(x => x.value);
  if (material.length < 40) { ghoToast('Tempel materi minimal beberapa paragraf.'); return; }
  if (!types.length) { ghoToast('Pilih minimal satu bentuk soal.'); return; }
  ghoToast('Sedang menyusun soal…');
  const r = await ghoPost(url, {material, n, types, level});
  if (r.error) { ghoToast(r.error); return; }
  (r.questions || []).forEach(q => {
    if (q.type === 'ordering') { q.items = (q.correct || []).map(k => q.items[k]); q.correct = q.items.map((_, k) => k); }
    if (q.type === 'matching') { q.right = q.left.map((_, k) => q.right[(q.correct || [])[k] ?? k] ?? ''); q.correct = q.left.map((_, k) => k); }
    ghoQuiz.items.push(q); });
  ghoQuiz.render(); ghoToast((r.questions || []).length + ' soal ditambahkan. Periksa lalu tekan Simpan semua soal.');
};

/* ---------- selesai -> buka tombol berikutnya ---------- */
function markDone() { const c = $('#doneChip'); if (c) { c.className = 'chip good'; c.textContent = '✓ ' + (document.documentElement.lang === 'en' ? 'Completed' : 'Selesai'); }
  const n = $('#nextBtn'); if (n) { n.hidden = false; n.classList.add('primary'); } }

/* ---------- video & audio: tanpa melompat ---------- */
function mediaTrack() {
  const box = $('#media'); if (!box) return;
  let max = +box.dataset.max || 0, dur = 0, lastSent = 0, done = false;
  const bar = $('#mbar'), pct = $('#mpct');
  const show = () => { const p = dur ? Math.min(100, max / dur * 100) : 0; bar.style.width = p + '%'; pct.textContent = Math.round(p) + '%'; };
  const send = async (pos, ended) => { const r = await ghoPost(box.dataset.url, {position: Math.floor(pos), duration: Math.floor(dur), ended: !!ended}); if (r.max !== undefined) max = Math.max(max, r.max); if (r.done && !done) { done = true; markDone(); } show(); };
  const tick = (cur, seekTo) => { // dipanggil saat waktu berubah
    if (cur > max + 2) { seekTo(max); ghoToast(document.documentElement.lang === 'en' ? 'You cannot skip ahead.' : 'Bagian ini belum ditonton, tidak bisa dilompati.'); return; }
    if (cur > max) max = cur;
    if (cur - lastSent >= 10) { lastSent = cur; send(cur); }
    show(); };
  const el = $('#mel');
  if (el) {
    el.addEventListener('loadedmetadata', () => { dur = el.duration || 0; show(); if (max > 0 && max < dur) el.currentTime = max; });
    el.addEventListener('timeupdate', () => tick(el.currentTime, t => { el.currentTime = t; }));
    el.addEventListener('seeking', () => { if (el.currentTime > max + 2) el.currentTime = max; });
    el.addEventListener('ratechange', () => { if (el.playbackRate > 1) el.playbackRate = 1; });
    el.addEventListener('ended', () => send(dur, true));
    return;
  }
  const yt = $('#ytp'); if (!yt) return;
  window.onYouTubeIframeAPIReady = () => {
    const p = new YT.Player('ytp', {videoId: yt.dataset.id, width: '100%', height: '100%', playerVars: {rel: 0, modestbranding: 1, disablekb: 1, playsinline: 1, start: Math.floor(max)},
      events: {onReady: () => { dur = p.getDuration(); show(); setInterval(() => { if (p.getPlayerState() === 1) { dur = p.getDuration(); tick(p.getCurrentTime(), t => p.seekTo(t, true)); } }, 1000); },
        onStateChange: e => { if (e.data === 0) send(dur, true); },
        onPlaybackRateChange: () => { if (p.getPlaybackRate() > 1) p.setPlaybackRate(1); }}});
    const f = $('#ytp'); if (f) { f.style.width = '100%'; f.style.height = '100%'; }
  };
  const s = document.createElement('script'); s.src = 'https://www.youtube.com/iframe_api'; document.head.appendChild(s);
}

/* ---------- ebook Heyzine: waktu baca aktif + konfirmasi halaman terakhir ---------- */
function ebookTrack() {
  const box = $('#ebook'); if (!box) return;
  const need = +box.dataset.need || 0; let have = +box.dataset.have || 0, pending = 0, done = false;
  const bar = $('#ebar'), lbl = $('#etime'), last = $('#elast');
  const fmt = s => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  const show = () => { bar.style.width = (need ? Math.min(100, have / need * 100) : 100) + '%'; lbl.textContent = fmt(Math.min(have, need)) + ' / ' + fmt(need); last.disabled = have < need; };
  const send = async () => { const t = pending; pending = 0; const r = await ghoPost(box.dataset.url, {tick: t, confirm_last: last.checked}); if (r.seconds !== undefined) have = Math.max(have, r.seconds); if (r.done && !done) { done = true; markDone(); } show(); };
  setInterval(() => { if (document.visibilityState === 'visible' && document.hasFocus()) { have++; pending++; show(); if (pending >= 15) send(); } }, 1000);
  last.addEventListener('change', () => { if (last.checked) send(); });
  show();
}

/* ---------- studi kasus interaktif ---------- */
function caseFlow() {
  const box = $('#case'); if (!box) return;
  const chat = $('#cchat'), form = $('#cform'), input = $('#cinput'), sendB = $('#csend'), startRow = $('#cstartRow'), rounds = $('#crounds');
  let sid = box.dataset.session, state = null, busy = false;
  const en = document.documentElement.lang === 'en';
  const L = en ? {sit: 'Situation', q: 'Question', you: 'Your answer', fb: 'Feedback', cond: 'Condition changed', fin: 'Final result', total: 'Total score', pat: 'Decision pattern', dev: 'Area to develop', wait: 'Preparing the next situation…', retry: 'Try again'}
    : {sit: 'Situasi', q: 'Pertanyaan', you: 'Jawabanmu', fb: 'Tanggapan', cond: 'Kondisi berubah', fin: 'Hasil akhir', total: 'Skor total', pat: 'Pola keputusan', dev: 'Area pengembangan', wait: 'Menyiapkan situasi berikutnya…', retry: 'Coba lagi'};
  const url = k => box.dataset[k].replace('__ID__', sid);
  function draw(extra) {
    if (!state) return;
    rounds.innerHTML = Array.from({length: state.max}, (_, i) => '<i class="' + (i < state.round ? 'on' : '') + '"></i>').join('');
    chat.innerHTML = state.turns.map(t => {
      const m = t.meta || {};
      if (t.role === 'opening') return '<div class="msg"><div class="meta"><span class="chip brand">' + L.sit + '</span>' + (m.title ? '<b>' + esc(m.title) + '</b>' : '') + '</div><div class="ai-text">' + esc(t.content) + '</div>' + (m.question ? '<p style="margin-top:8px"><b>' + L.q + ':</b> ' + esc(m.question) + '</p>' : '') + '</div>';
      if (t.role === 'participant') return '<div class="msg me"><div class="meta"><span class="chip">' + L.you + ' · ' + t.round + '</span></div><div class="ai-text">' + esc(t.content) + '</div></div>';
      if (t.role === 'feedback') return '<div class="msg"><div class="meta"><span class="chip ai">' + L.fb + '</span></div><div class="ai-text">' + esc(t.content) + '</div></div>';
      if (t.role === 'condition') return '<div class="msg cond"><div class="meta"><span class="chip accent">' + L.cond + '</span>' + (m.category ? '<span class="chip">' + esc(m.category) + '</span>' : '') + (m.impact ? '<span class="chip ' + (m.impact === 'Tinggi' ? 'bad' : 'warn') + '">' + esc(m.impact) + '</span>' : '') + '</div><div class="ai-text">' + esc(t.content) + '</div>' + (m.question ? '<p style="margin-top:8px"><b>' + L.q + ':</b> ' + esc(m.question) + '</p>' : '') + '</div>';
      if (t.role === 'final') return '<div class="msg" style="border-color:var(--good)"><div class="meta"><span class="chip good">' + L.fin + '</span>' + (m.total != null ? '<b class="mono">' + L.total + ': ' + esc(m.total) + '</b>' : '') + '</div>' +
        (m.scores || []).map(s => '<div class="row" style="flex-wrap:nowrap"><span style="flex:1">' + esc(s.criterion) + '</span><b class="mono">' + esc(s.score) + '</b></div>').join('') +
        (m.pattern ? '<p style="margin-top:8px"><b>' + L.pat + ':</b> ' + esc(m.pattern) + '</p>' : '') + (m.development ? '<p><b>' + L.dev + ':</b> ' + esc(m.development) + '</p>' : '') + '</div>';
      return '';
    }).join('') + (extra || '');
    form.hidden = state.status !== 'berjalan' || state.pending || busy;
    startRow.hidden = true;
    if (state.status !== 'berjalan') markDone();
  }
  async function respond() {
    busy = true; draw('<div class="msg"><span class="typing">' + L.wait + '</span></div>');
    const r = await ghoPost(url('respond'));
    busy = false;
    if (r.error) { draw('<div class="msg"><span class="chip bad">' + esc(r.error) + '</span> <button type="button" class="btn sm" id="cretry">' + L.retry + '</button></div>'); $('#cretry').onclick = respond; return; }
    state = r; draw(); if (!form.hidden) input.focus();
  }
  async function start() {
    $('#cstart').disabled = true; chat.innerHTML = '<div class="msg"><span class="typing">' + L.wait + '</span></div>';
    const r = await ghoPost(box.dataset.start);
    $('#cstart').disabled = false;
    if (r.error) { chat.innerHTML = '<div class="msg"><span class="chip bad">' + esc(r.error) + '</span></div>'; return; }
    state = r; sid = r.id; draw(); if (state.pending) respond();
  }
  $('#cstart').addEventListener('click', start);
  sendB.addEventListener('click', async () => {
    const v = input.value.trim(); if (v.length < 5) { input.focus(); return; }
    sendB.disabled = true;
    const r = await ghoPost(url('answer'), {content: v}); // 1) simpan dulu
    sendB.disabled = false;
    if (r.error) { ghoToast(r.error); return; }
    input.value = ''; state = r; draw(); respond(); // 2) baru minta tanggapan
  });
  if (sid) start();
}

// hanya satu panel jadwal/bagikan terbuka
document.addEventListener('toggle', e => { const d = e.target; if (d.matches && d.matches('details.inline') && d.open) $$('details.inline[open]').forEach(x => { if (x !== d) x.open = false; }); }, true);
document.addEventListener('click', e => { if (!e.target.closest('details.inline')) $$('details.inline[open]').forEach(x => { x.open = false; }); });

document.addEventListener('DOMContentLoaded', () => {
  $$('.ed').forEach(ed => { editors[ed.id] = Editor(ed); });
  reEq();
  if ($('.eq[data-latex]')) loadML(() => {});
  mediaTrack(); ebookTrack(); caseFlow();
});
})();

/* ---------- suara: bicara jadi teks, dan bacakan teks ---------- */
(function () {
'use strict';
const G = window.GHO || {}, V = G.voice || {}, T = k => (G.t && G.t[k]) || k;
const MIC = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/></svg>';
let active = null;

function insertInto(target, text) {
  if (!text) return;
  if (target.classList && target.classList.contains('ed-area')) {
    target.focus(); document.execCommand('insertText', false, (target.innerText.trim() ? ' ' : '') + text);
    target.dispatchEvent(new Event('input', {bubbles: true}));
  } else {
    const p = target.selectionStart ?? target.value.length, pre = target.value.slice(0, p);
    target.value = pre + (pre && !/\s$/.test(pre) ? ' ' : '') + text + target.value.slice(p);
    target.dispatchEvent(new Event('input', {bubbles: true}));
  }
}

function browserRecognize(btn, target) {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) { ghoToast(T('nomic')); return; }
  const r = new SR(); r.lang = V.lang || 'id-ID'; r.interimResults = false; r.continuous = true;
  r.onresult = e => { for (let i = e.resultIndex; i < e.results.length; i++) if (e.results[i].isFinal) insertInto(target, e.results[i][0].transcript.trim()); };
  r.onend = () => { btn.classList.remove('on'); active = null; };
  r.onerror = () => { btn.classList.remove('on'); active = null; };
  r.start(); btn.classList.add('on'); ghoToast(T('rec')); active = {stop: () => r.stop()};
}

async function serverRecognize(btn, target) {
  if (!navigator.mediaDevices || !window.MediaRecorder) { browserRecognize(btn, target); return; }
  let stream; try { stream = await navigator.mediaDevices.getUserMedia({audio: true}); } catch (e) { ghoToast(T('nomic')); return; }
  const chunks = [], rec = new MediaRecorder(stream);
  rec.ondataavailable = e => e.data.size && chunks.push(e.data);
  rec.onstop = async () => {
    stream.getTracks().forEach(t => t.stop()); btn.classList.remove('on'); active = null;
    const blob = new Blob(chunks, {type: rec.mimeType || 'audio/webm'}); if (blob.size < 1200) return;
    ghoToast(T('conv')); btn.disabled = true;
    const fd = new FormData(); fd.append('audio', blob, 'suara.' + ((rec.mimeType || '').includes('mp4') ? 'mp4' : 'webm'));
    try {
      const r = await fetch(V.text, {method: 'POST', headers: {'X-CSRF-TOKEN': G.csrf, 'Accept': 'application/json'}, body: fd}); const j = await r.json();
      if (j.text) insertInto(target, j.text); else if (j.error === 'no_key') browserRecognize(btn, target); else ghoToast(j.error || 'Error');
    } catch (e) { ghoToast('Error'); }
    btn.disabled = false;
  };
  rec.start(); btn.classList.add('on'); ghoToast(T('rec')); active = {stop: () => rec.stop()};
}

function micButton(target) {
  const b = document.createElement('button'); b.type = 'button'; b.className = 'micbtn'; b.title = T('speak'); b.setAttribute('aria-label', T('speak')); b.innerHTML = MIC;
  b.addEventListener('mousedown', e => e.preventDefault());
  b.addEventListener('click', () => { if (active) { active.stop(); return; } (V.server ? serverRecognize : browserRecognize)(b, target); });
  return b;
}

function addMics(root) {
  // editor lengkap: tombol di bilah alat
  root.querySelectorAll('.ed').forEach(ed => { if (ed.querySelector('.micbtn')) return; const bar = ed.querySelector('.ed-bar'); if (!bar) return;
    const gp = document.createElement('div'); gp.className = 'gp'; gp.appendChild(micButton(ed.querySelector('.ed-area'))); bar.appendChild(gp); });
  // kolom teks biasa untuk jawaban: textarea dan input bertanda data-voice
  root.querySelectorAll('textarea[data-voice], input[data-voice], #cinput').forEach(t => { if (t.dataset.micDone) return; t.dataset.micDone = 1;
    const w = document.createElement('div'); w.className = 'micwrap'; t.parentNode.insertBefore(w, t); w.appendChild(t); w.appendChild(micButton(t)); });
}
window.ghoAddMics = addMics;

/* bacakan */
let audio = null;
async function speak(btn, text) {
  if (audio) { audio.pause(); audio = null; speechSynthesis && speechSynthesis.cancel(); btn.classList.remove('on'); return; }
  btn.classList.add('on');
  const fallback = () => { if (!window.speechSynthesis) { btn.classList.remove('on'); return; } const u = new SpeechSynthesisUtterance(text); u.lang = V.lang || 'id-ID'; u.onend = () => { btn.classList.remove('on'); audio = null; }; speechSynthesis.speak(u); audio = {pause: () => speechSynthesis.cancel()}; };
  if (!V.server) { fallback(); return; }
  try {
    const r = await fetch(V.speak, {method: 'POST', headers: {'X-CSRF-TOKEN': G.csrf, 'Content-Type': 'application/json', 'Accept': 'audio/mpeg,application/json'}, body: JSON.stringify({text})});
    if (!r.ok) { fallback(); return; }
    audio = new Audio(URL.createObjectURL(await r.blob())); audio.onended = () => { btn.classList.remove('on'); audio = null; }; audio.play();
  } catch (e) { fallback(); }
}
function addSpeak(root) {
  root.querySelectorAll('.prose[data-speak], .ai-text[data-speak]').forEach(el => { if (el.dataset.spDone) return; el.dataset.spDone = 1;
    const b = document.createElement('button'); b.type = 'button'; b.className = 'btn sm ghost spk'; b.innerHTML = '🔊 ' + T('listen');
    b.addEventListener('click', () => speak(b, el.innerText.replace('🔊 ' + T('listen'), '').trim())); el.parentNode.insertBefore(b, el); });
}
document.addEventListener('DOMContentLoaded', () => { addMics(document); addSpeak(document); });
})();

/* ---------- batas: tempel teks, jumlah karakter, lama rekaman ---------- */
(function () {
'use strict';
const T = k => (window.GHO && GHO.t && GHO.t[k]) || k;
const en = document.documentElement.lang === 'en';
const msgPaste = en ? 'Pasting text is turned off for this activity. Please type or speak.' : 'Tempel teks dimatikan untuk aktivitas ini. Silakan ketik atau bicara.';
function apply(el, cfg) {
  const len = () => (el.isContentEditable ? el.innerText : el.value).replace(/\s+$/, '').length;
  if (cfg.paste === '0') {
    ['paste', 'drop'].forEach(ev => el.addEventListener(ev, e => { e.preventDefault(); e.stopImmediatePropagation(); ghoToast(msgPaste); }, true));
    el.addEventListener('contextmenu', e => e.preventDefault());
    el.addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) { e.preventDefault(); ghoToast(msgPaste); } });
  }
  const max = +cfg.maxchars || 0;
  if (max) {
    const lbl = document.createElement('span'); lbl.className = 'faint mono'; lbl.style.fontSize = '12px';
    const host = el.closest('.ed') ? el.closest('.ed').querySelector('.ed-foot') : el.parentNode; host.appendChild(lbl);
    const upd = () => { const n = len(); lbl.textContent = n + ' / ' + max; lbl.style.color = n > max ? 'var(--bad)' : ''; };
    el.addEventListener('beforeinput', e => { if (e.inputType && e.inputType.startsWith('insert') && len() >= max) { e.preventDefault(); ghoToast((en ? 'Maximum ' : 'Maksimal ') + max + (en ? ' characters.' : ' karakter.')); } });
    el.addEventListener('input', upd); upd();
  }
}
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.ed[data-paste]').forEach(ed => apply(ed.querySelector('.ed-area'), ed.dataset));
  document.querySelectorAll('textarea[data-paste], input[data-paste]').forEach(t => apply(t, t.dataset));
  // lama rekaman: mikrofon berhenti otomatis, 0 = sembunyikan mikrofon
  document.querySelectorAll('[data-maxvoice]').forEach(h => { const sec = +h.dataset.maxvoice; const scope = h.classList.contains('ed') ? h : h.parentNode;
    scope.querySelectorAll('.micbtn').forEach(b => { if (sec === 0) { b.hidden = true; return; }
      b.addEventListener('click', () => { clearTimeout(b._lim); if (b.classList.contains('on') || !sec) return; b._lim = setTimeout(() => { if (b.classList.contains('on')) b.click(); }, sec * 1000); }); }); });
});
})();
