/* Gho Studio: pembuat isian dan output dinamis (fasilitator/coach). */
(function () {
'use strict';
const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
const slug = (s, used) => { let b = String(s || 'isian').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 30) || 'isian', k = b, i = 2; while (used.has(k)) k = b + '_' + i++; used.add(k); return k; };

window.ghoFormBuilder = {
  init(opts) {
    this.o = opts; this.types = opts.types;
    this.form = opts.form && opts.form.sections ? opts.form : {sections: []};
    this.output = opts.output && opts.output.sections ? opts.output : {sections: []};
    this.render();
    const f = $('#actForm'); f.addEventListener('submit', () => this.sync());
    $('#fbAddSec').onclick = () => { this.form.sections.push({key: this.newKey('bagian'), title: 'Bagian baru', help: '', fields: []}); this.render(); };
    $('#fbAddOut').onclick = () => { this.output.sections.push({key: this.newKey('output'), title: 'Bagian output baru', prompt: ''}); this.render(); };
    $('#fbTpl').onchange = e => { const t = opts.templates.find(x => String(x.id) === e.target.value); if (!t) return;
      if (!confirm(opts.t.replace)) { e.target.value = ''; return; }
      this.form = JSON.parse(JSON.stringify(t.config.form || {sections: []})); this.output = JSON.parse(JSON.stringify(t.config.output || {sections: []}));
      if (t.config.page) this.setPage(t.config.page); if (t.config.fonts) this.setFonts(t.config.fonts); this.render(); e.target.value = ''; ghoToast(opts.t.loaded); };
    $('#fbSaveTpl').onclick = async () => { const name = prompt(opts.t.tplName); if (!name) return; this.sync();
      const r = await ghoPost(opts.tplUrl, {name, config: {form: this.form, output: this.output, page: this.getPage(), fonts: this.getFonts()}});
      ghoToast(r.error || opts.t.tplSaved); };
    $$('#fbPage [name], #fbFonts [name]').forEach(i => i.addEventListener('change', () => this.pagePreview()));
    this.pagePreview();
  },
  keys() { const s = new Set(); this.form.sections.forEach(x => { s.add(x.key); x.fields.forEach(f => s.add(f.key)); }); this.output.sections.forEach(x => s.add(x.key)); return s; },
  newKey(b) { return slug(b, this.keys()); },
  getPage() { const g = n => ($('#fbPage [name=' + n + ']') || {}).value; return {size: g('size'), orientation: g('orientation'), width_mm: +g('width_mm'), height_mm: +g('height_mm'), margin: {top: +g('m_top'), right: +g('m_right'), bottom: +g('m_bottom'), left: +g('m_left')}}; },
  setPage(p) { const s = (n, v) => { const i = $('#fbPage [name=' + n + ']'); if (i && v !== undefined) i.value = v; }; s('size', p.size); s('orientation', p.orientation); s('width_mm', p.width_mm); s('height_mm', p.height_mm); const m = p.margin || {}; s('m_top', m.top); s('m_right', m.right); s('m_bottom', m.bottom); s('m_left', m.left); },
  getFonts() { const g = n => ($('#fbFonts [name=' + n + ']') || {}).value; return {input_family: g('input_family'), input_size: +g('input_size'), output_family: g('output_family'), output_size: +g('output_size'), heading_size: +g('heading_size'), line_height: +g('line_height')}; },
  setFonts(f) { Object.entries(f).forEach(([k, v]) => { const i = $('#fbFonts [name=' + k + ']'); if (i) i.value = v; }); },
  pagePreview() { const p = this.getPage(), S = this.o.sizes; let [w, h] = S[p.size] || [p.width_mm || 210, p.height_mm || 297]; if (p.orientation === 'landscape') [w, h] = [h, w];
    $('#fbCustom').hidden = p.size !== 'custom'; const pv = $('#fbPv'), sc = 150 / Math.max(w, h);
    pv.style.width = w * sc + 'px'; pv.style.height = h * sc + 'px'; pv.style.padding = `${p.margin.top * sc}px ${p.margin.right * sc}px ${p.margin.bottom * sc}px ${p.margin.left * sc}px`;
    const f = this.getFonts(); pv.querySelector('div').style.fontFamily = this.o.fonts[f.output_family] || 'serif'; $('#fbPvLbl').textContent = w + ' × ' + h + ' mm'; },
  sync() { $('#cfgForm').value = JSON.stringify(this.form); $('#cfgOutput').value = JSON.stringify(this.output); $('#cfgPage').value = JSON.stringify(this.getPage()); $('#cfgFonts').value = JSON.stringify(this.getFonts()); },
  fieldRefs() { return this.form.sections.flatMap(s => s.fields.map(f => ({key: f.key, label: f.label, sec: s.title}))); },
  render() {
    const T = this.types;
    $('#fbForm').innerHTML = this.form.sections.map((s, si) => `
      <div class="qed" data-si="${si}">
        <div class="row" style="justify-content:space-between"><span class="chip brand">Bagian ${si + 1}</span><span class="row">
          <button type="button" class="btn sm ghost" data-a="secUp">↑</button><button type="button" class="btn sm ghost" data-a="secDown">↓</button><button type="button" class="btn sm ghost" data-a="secDel">✕</button></span></div>
        <input type="text" data-p="title" value="${esc(s.title)}" placeholder="Judul bagian">
        <input type="text" data-p="help" value="${esc(s.help)}" placeholder="Petunjuk bagian (opsional)">
        <div class="stack" style="gap:8px">${s.fields.map((f, fi) => `
          <div class="card stack" data-fi="${fi}" style="padding:10px;gap:6px;background:var(--surface-2)">
            <div class="row" style="flex-wrap:nowrap"><select data-f="type" style="width:auto">${Object.entries(T).map(([k, v]) => `<option value="${k}" ${f.type === k ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select>
              <input type="text" data-f="label" value="${esc(f.label)}" placeholder="Label isian" style="flex:1">
              <button type="button" class="btn sm ghost" data-a="fUp">↑</button><button type="button" class="btn sm ghost" data-a="fDown">↓</button><button type="button" class="btn sm ghost" data-a="fDel">✕</button></div>
            <div class="row" style="flex-wrap:nowrap"><input type="text" data-f="help" value="${esc(f.help || '')}" placeholder="${f.type === 'narasi' ? 'Isi teks narasi/penjelasan yang akan tampil di sini' : 'Petunjuk atau contoh jawaban (opsional)'}" style="flex:1">
              ${f.type === 'narasi' ? '' : `<label class="sw" style="font-size:12px"><input type="checkbox" data-f="required" ${f.required ? 'checked' : ''}> Wajib</label>`}</div>
            ${['select', 'radio', 'checkbox'].includes(f.type) ? `<input type="text" data-f="options" value="${esc((f.options || []).join('; '))}" placeholder="Pilihan, pisahkan dengan titik koma (;)">` : ''}
            ${f.type === 'linear_scale' ? `<input type="text" data-f="options" value="${esc((f.options || []).join('; '))}" placeholder="Minimum; Maksimum; Label kiri; Label kanan — contoh: 1; 5; Sangat tidak setuju; Sangat setuju">` : ''}
            ${f.type === 'table' ? `<input type="text" data-f="columns" value="${esc((f.columns || []).join('; '))}" placeholder="Judul kolom, pisahkan dengan titik koma (;)">` : ''}
            <span class="faint mono" style="font-size:11.5px">Kode untuk output: {${esc(f.key)}}</span>
          </div>`).join('')}</div>
        <div><button type="button" class="btn sm" data-a="fAdd">+ Isian</button></div>
      </div>`).join('') || '<div class="empty">Belum ada bagian isian.</div>';
    const refs = this.fieldRefs();
    $('#fbOut').innerHTML = this.output.sections.map((o, oi) => `
      <div class="qed" data-oi="${oi}">
        <div class="row" style="justify-content:space-between"><span class="chip accent">Output ${oi + 1}</span><span class="row">
          <button type="button" class="btn sm ghost" data-a="oUp">↑</button><button type="button" class="btn sm ghost" data-a="oDown">↓</button><button type="button" class="btn sm ghost" data-a="oDel">✕</button></span></div>
        <input type="text" data-o="title" value="${esc(o.title)}" placeholder="Judul bagian output, contoh: BAB I PENDAHULUAN">
        <textarea data-o="prompt" placeholder="Perintah penyusunan. Rujuk isian dengan kode, contoh: Susun latar belakang dari {isu_utama} dan {data_pendukung}.">${esc(o.prompt)}</textarea>
        <div class="row" style="gap:4px">${refs.map(r => `<button type="button" class="chip" data-ins="{${esc(r.key)}}" title="${esc(r.sec)}">{${esc(r.key)}}</button>`).join('')}</div>
      </div>`).join('') || '<div class="empty">Belum ada bagian output.</div>';
    this.bind();
  },
  bind() {
    const self = this, mv = (arr, i, d) => { const j = i + d; if (j >= 0 && j < arr.length) [arr[i], arr[j]] = [arr[j], arr[i]]; };
    $$('#fbForm [data-si]').forEach(box => { const si = +box.dataset.si, s = self.form.sections[si];
      $$('[data-p]', box).forEach(i => i.addEventListener('input', () => { s[i.dataset.p] = i.value; }));
      $$('[data-fi]', box).forEach(fb => { const fi = +fb.dataset.fi, f = s.fields[fi];
        $$('[data-f]', fb).forEach(i => i.addEventListener(i.type === 'checkbox' || i.tagName === 'SELECT' ? 'change' : 'input', () => {
          const k = i.dataset.f; if (k === 'required') f.required = i.checked; else if (k === 'options' || k === 'columns') f[k] = i.value.split(';').map(x => x.trim()).filter(Boolean); else f[k] = i.value;
          if (k === 'type') self.render(); })); });
      box.addEventListener('click', e => { const a = e.target.dataset.a; if (!a) return; const fi = +(e.target.closest('[data-fi]') || {dataset: {}}).dataset.fi;
        if (a === 'secUp') mv(self.form.sections, si, -1); if (a === 'secDown') mv(self.form.sections, si, 1);
        if (a === 'secDel' && confirm('Hapus bagian ini beserta isiannya?')) self.form.sections.splice(si, 1);
        if (a === 'fAdd') { const label = 'Isian baru'; s.fields.push({key: self.newKey(label), label, type: 'textarea', required: true, help: ''}); }
        if (a === 'fUp') mv(s.fields, fi, -1); if (a === 'fDown') mv(s.fields, fi, 1); if (a === 'fDel') s.fields.splice(fi, 1);
        self.render(); });
      // kode isian mengikuti label saat label pertama kali diganti
      $$('[data-f=label]', box).forEach(i => i.addEventListener('change', () => { const f = s.fields[+i.closest('[data-fi]').dataset.fi]; if (/^isian_baru/.test(f.key)) { const used = self.keys(); used.delete(f.key); f.key = slug(i.value, used); self.render(); } }));
    });
    $$('#fbOut [data-oi]').forEach(box => { const oi = +box.dataset.oi, o = self.output.sections[oi];
      $$('[data-o]', box).forEach(i => i.addEventListener('input', () => { o[i.dataset.o] = i.value; }));
      $$('[data-ins]', box).forEach(b => b.addEventListener('click', () => { const ta = $('[data-o=prompt]', box), p = ta.selectionStart ?? ta.value.length; ta.value = ta.value.slice(0, p) + b.dataset.ins + ta.value.slice(p); o.prompt = ta.value; ta.focus(); }));
      box.addEventListener('click', e => { const a = e.target.dataset.a; if (!a) return;
        if (a === 'oUp') mv(self.output.sections, oi, -1); if (a === 'oDown') mv(self.output.sections, oi, 1); if (a === 'oDel' && confirm('Hapus bagian output ini?')) self.output.sections.splice(oi, 1);
        self.render(); }); });
  }
};
})();
