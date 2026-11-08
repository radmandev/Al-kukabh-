// Al Kawkaba admin widgets — plain JS, no build step.
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name="csrf"]')?.content || '';
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  const h = (tag, cls = '', html = '') => { const el = document.createElement(tag); if (cls) el.className = cls; if (html) el.innerHTML = html; return el; };
  const INP = 'w-full rounded border border-black/15 bg-white px-3 py-2 text-sm outline-none focus:border-ink';
  const BTN = 'inline-flex items-center gap-2 rounded bg-ink px-4 py-2 text-sm text-ivory hover:bg-onyx disabled:opacity-50';
  const BTN2 = 'inline-flex items-center gap-2 rounded border border-black/15 bg-white px-3 py-1.5 text-sm hover:border-ink disabled:opacity-50';
  const ACCEPT = 'image/jpeg,image/png,image/webp,image/avif,image/gif,video/mp4,video/webm,video/quicktime';
  const imgUrl = (url, w) => (/^\/(media|brand)\/[^?]+\.(jpe?g|png|webp|avif|gif)$/i.test(url || '')
    ? '/img/' + w + url.split('/').map((s) => encodeURIComponent(decodeURIComponent(s))).join('/') : url);
  const post = (url, body) => fetch(url, {
    method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF': csrf }, body: JSON.stringify(body || {}),
  }).then((r) => r.json());

  const thumb = (m, cls) => `<div class="relative overflow-hidden bg-black/5 ${cls}">${m.kind === 'video'
    ? `<video src="${esc(m.url)}" muted preload="metadata" class="absolute inset-0 h-full w-full object-cover"></video><span class="absolute bottom-1 start-1 rounded bg-black/70 px-1.5 text-[.6rem] text-white">VIDEO</span>`
    : `<img src="${esc(imgUrl(m.url, 256))}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">`}</div>`;

  /* ---------- confirmations ---------- */
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset?.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });
  document.addEventListener('click', (e) => {
    const b = e.target.closest?.('button[data-confirm]');
    if (b && !confirm(b.dataset.confirm)) e.preventDefault();
  });

  /* ---------- reorderable rows ---------- */
  $$('[data-sortable]').forEach((ul) => {
    const paint = () => $$(':scope > li', ul).forEach((li, i, all) => {
      $('[data-move="-1"]', li).disabled = i === 0;
      $('[data-move="1"]', li).disabled = i === all.length - 1;
    });
    paint();
    ul.addEventListener('click', (e) => {
      const b = e.target.closest('[data-move]');
      if (!b) return;
      const li = b.closest('li');
      if (b.dataset.move === '-1') li.previousElementSibling?.before(li); else li.nextElementSibling?.after(li);
      paint();
      post('/admin/reorder', { model: ul.dataset.sortable, ids: $$(':scope > li', ul).map((x) => x.dataset.id) });
    });
  });

  /* ---------- uploads: XHR so we get progress for large videos ---------- */
  const upload = (files, onProgress) => new Promise((res, rej) => {
    const fd = new FormData();
    files.forEach((f) => fd.append('file[]', f));
    const x = new XMLHttpRequest();
    x.open('POST', '/admin/api/media');
    x.setRequestHeader('X-CSRF', csrf);
    x.upload.onprogress = (e) => e.lengthComputable && onProgress(e.loaded / e.total);
    x.onload = () => { try { res(JSON.parse(x.responseText)); } catch { rej(new Error(`Upload failed (${x.status})`)); } };
    x.onerror = () => rej(new Error('Network error'));
    x.send(fd);
  });

  const dropZone = (host, { onUploaded, multiple = true, accept = ACCEPT, compact = false }) => {
    host.innerHTML = '';
    const idle = `<p><b>Drag &amp; drop</b> ${multiple ? 'files' : 'a file'} here or click to browse<br><span class="text-xs text-stone">JPG, PNG, WEBP, AVIF, GIF, MP4, WEBM, MOV — images are auto-optimized</span></p>`;
    const zone = h('div', `grid cursor-pointer place-items-center rounded-md border-2 border-dashed text-center text-sm transition ${compact ? 'p-4' : 'p-10'} border-black/15 bg-white hover:border-ink`, idle);
    zone.setAttribute('role', 'button');
    zone.tabIndex = 0;
    const input = h('input');
    Object.assign(input, { type: 'file', hidden: true, multiple, accept });
    const errs = h('div');
    host.append(zone, input, errs);
    const over = (on) => { ['border-gold', 'bg-gold/5'].forEach((c) => zone.classList.toggle(c, on)); ['border-black/15', 'bg-white', 'hover:border-ink'].forEach((c) => zone.classList.toggle(c, !on)); };
    const go = async (list) => {
      if (!list?.length) return;
      errs.innerHTML = '';
      const prog = (p) => {
        zone.innerHTML = `<div class="w-full max-w-xs"><div class="h-1 overflow-hidden rounded bg-black/10"><div class="h-full bg-gold transition-all" style="width:${p * 100}%"></div></div>
          <p class="mt-2 text-xs text-stone">Uploading… ${Math.round(p * 100)}%${p === 1 ? ' — optimizing' : ''}</p></div>`;
      };
      prog(0);
      try {
        const r = await upload([...list], prog);
        errs.innerHTML = (r.errors || []).map((x) => `<p class="mt-2 text-xs text-red-700">${esc(x)}</p>`).join('');
        if (r.media?.length) onUploaded(r.media);
      } catch (e) { errs.innerHTML = `<p class="mt-2 text-xs text-red-700">${esc(e.message)}</p>`; }
      zone.innerHTML = idle;
      input.value = '';
    };
    zone.addEventListener('click', () => input.click());
    zone.addEventListener('keydown', (e) => e.key === 'Enter' && input.click());
    zone.addEventListener('dragover', (e) => { e.preventDefault(); over(true); });
    zone.addEventListener('dragleave', () => over(false));
    zone.addEventListener('drop', (e) => { e.preventDefault(); over(false); go(e.dataTransfer.files); });
    input.addEventListener('change', () => go(input.files));
  };

  /* ---------- media picker modal: pick from library or upload new ---------- */
  const openPicker = ({ multiple = false, kind = '' } = {}) => new Promise((resolve) => {
    const modal = h('div', 'fixed inset-0 z-50 grid place-items-center bg-black/50 p-4', `
      <div class="flex max-h-[90vh] w-full max-w-5xl flex-col rounded-md bg-[#f4f2ee]">
        <div class="flex items-center justify-between border-b border-black/10 p-4"><h2 class="font-medium">Media library</h2><button type="button" class="${BTN2}" data-close>Close</button></div>
        <div class="overflow-y-auto p-4"><div data-dz></div><div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-7" data-grid></div></div>
        ${multiple ? `<div class="flex justify-end gap-2 border-t border-black/10 p-4"><button type="button" class="${BTN}" data-add disabled>Add selected</button></div>` : ''}
      </div>`);
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    document.body.append(modal);
    let items = [], sel = [];
    const close = (picked) => { modal.remove(); document.removeEventListener('keydown', onKey); resolve(picked); };
    const onKey = (e) => e.key === 'Escape' && close([]);
    document.addEventListener('keydown', onKey);
    const grid = $('[data-grid]', modal), addBtn = $('[data-add]', modal);
    const draw = () => {
      grid.innerHTML = items.map((m) => {
        const n = sel.indexOf(m.id);
        return `<button type="button" data-id="${esc(m.id)}" class="relative rounded ring-2 ${n >= 0 ? 'ring-gold' : 'ring-transparent hover:ring-black/20'}">${thumb(m, 'aspect-[3/4] rounded')}
          ${n >= 0 ? `<span class="absolute end-1 top-1 grid h-5 w-5 place-items-center rounded-full bg-gold text-[.65rem] text-white">${n + 1}</span>` : ''}</button>`;
      }).join('');
      if (addBtn) { addBtn.disabled = !sel.length; addBtn.textContent = `Add ${sel.length || ''} selected`; }
    };
    grid.addEventListener('click', (e) => {
      const b = e.target.closest('[data-id]');
      if (!b) return;
      const m = items.find((x) => x.id === b.dataset.id);
      if (!multiple) return close([m]);
      sel = sel.includes(m.id) ? sel.filter((x) => x !== m.id) : [...sel, m.id];
      draw();
    });
    $('[data-close]', modal).addEventListener('click', () => close([]));
    addBtn?.addEventListener('click', () => close(sel.map((id) => items.find((m) => m.id === id))));
    dropZone($('[data-dz]', modal), {
      compact: true, multiple, accept: kind === 'image' ? 'image/*' : kind === 'video' ? 'video/*' : ACCEPT,
      onUploaded: (ms) => { if (!multiple) return close(ms.slice(0, 1)); items = [...ms, ...items]; sel = [...sel, ...ms.map((m) => m.id)]; draw(); },
    });
    fetch('/admin/api/media' + (kind ? `?kind=${kind}` : '')).then((r) => r.json()).then((list) => { items = list; draw(); });
  });

  const patchMedia = (id, d) => post(`/admin/api/media/${encodeURIComponent(id)}`, d);

  /* ---------- product gallery: drag to reorder, first = primary, alt text & video thumbnail ---------- */
  $$('[data-media-manager]').forEach((root) => {
    let items = JSON.parse(root.dataset.mediaManager);
    const hidden = $('input[name="media"]', root);
    const box = h('div', 'space-y-4', `<div data-dz></div>
      <div class="flex justify-between text-xs text-stone"><span>Drag to reorder · first item is the primary image</span><button type="button" class="underline" data-lib>Choose from library</button></div>
      <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-list></ul>`);
    root.append(box);
    const list = $('[data-list]', box);
    let drag = null;
    const set = (next) => { items = next; hidden.value = JSON.stringify(items.map((m) => m.id)); draw(); };
    const add = (ms) => set([...items, ...ms.filter((m) => !items.some((x) => x.id === m.id))]);
    const move = (from, to) => { const a = [...items]; const [x] = a.splice(from, 1); a.splice(to, 0, x); set(a); };
    const draw = () => {
      list.innerHTML = items.map((m, i) => `
        <li draggable="true" data-i="${i}" class="rounded border bg-white p-2 ${i === 0 ? 'border-gold' : 'border-black/10'}">
          <div class="relative cursor-grab">${thumb(m, 'aspect-[3/4] rounded')}${i === 0 ? '<span class="absolute start-1 top-1 rounded bg-gold px-1.5 text-[.6rem] text-white">PRIMARY</span>' : ''}</div>
          <input class="${INP} mt-2 py-1 text-xs" dir="rtl" placeholder="Alt (AR)" value="${esc(m.altAr)}" data-alt="altAr">
          <input class="${INP} mt-1 py-1 text-xs" placeholder="Alt (EN)" value="${esc(m.altEn)}" data-alt="altEn">
          <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs">
            ${i > 0 ? '<button type="button" class="underline" data-act="primary">Make primary</button><button type="button" aria-label="Move earlier" data-act="left">←</button>' : ''}
            ${i < items.length - 1 ? '<button type="button" aria-label="Move later" data-act="right">→</button>' : ''}
            ${m.kind === 'video' ? `<button type="button" class="underline" data-act="poster">${m.posterId ? 'Change' : 'Add'} thumbnail</button>` : ''}
            <button type="button" class="ms-auto text-red-700" data-act="remove">Remove</button>
          </div>
        </li>`).join('');
    };
    list.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-act]');
      if (!b) return;
      const i = +b.closest('li').dataset.i;
      const act = b.dataset.act;
      if (act === 'primary') move(i, 0);
      if (act === 'left') move(i, i - 1);
      if (act === 'right') move(i, i + 1);
      if (act === 'remove') set(items.filter((_, j) => j !== i));
      if (act === 'poster') {
        const [p] = await openPicker({ kind: 'image' });
        if (p) { items[i] = { ...items[i], posterId: p.id }; patchMedia(items[i].id, { posterId: p.id }); draw(); }
      }
    });
    list.addEventListener('focusout', (e) => {
      const inp = e.target.closest('[data-alt]');
      if (!inp) return;
      const m = items[+inp.closest('li').dataset.i];
      const k = inp.dataset.alt;
      if (inp.value !== m[k]) { m[k] = inp.value; patchMedia(m.id, { [k]: inp.value }); }
    });
    list.addEventListener('dragstart', (e) => { const li = e.target.closest('li'); drag = +li.dataset.i; li.classList.add('opacity-40'); });
    list.addEventListener('dragend', (e) => e.target.closest('li')?.classList.remove('opacity-40'));
    list.addEventListener('dragover', (e) => e.preventDefault());
    list.addEventListener('drop', (e) => {
      e.preventDefault();
      const li = e.target.closest('li');
      if (li && drag != null && drag !== +li.dataset.i) move(drag, +li.dataset.i);
      drag = null;
    });
    $('[data-lib]', box).addEventListener('click', async () => add(await openPicker({ multiple: true })));
    dropZone($('[data-dz]', box), { onUploaded: add });
    draw();
  });

  /* ---------- single media slot (collection covers by id, settings logo by url) ---------- */
  $$('[data-media-slot]').forEach((slot) => {
    const mode = slot.dataset.mediaSlot;
    const input = $('input[type="hidden"]', slot), preview = $('[data-slot-preview]', slot);
    const pickBtn = $('[data-slot-pick]', slot), clearBtn = $('[data-slot-clear]', slot);
    const empty = mode === 'id' ? '<div class="grid aspect-[4/5] w-40 place-items-center rounded border border-dashed border-black/20 text-xs text-stone">None</div>' : '';
    pickBtn.addEventListener('click', async () => {
      const [m] = await openPicker({ kind: slot.dataset.kind || '' });
      if (!m) return;
      input.value = mode === 'id' ? m.id : m.url;
      preview.innerHTML = mode === 'id' ? thumb(m, preview.dataset.class) : `<img src="${esc(imgUrl(m.url, 256))}" alt="" class="h-20 w-20 rounded-full object-cover">`;
      if (mode === 'id') pickBtn.textContent = 'Replace';
      clearBtn.hidden = false;
    });
    clearBtn.addEventListener('click', () => {
      input.value = '';
      preview.innerHTML = empty;
      if (mode === 'id') pickBtn.textContent = 'Choose / upload';
      clearBtn.hidden = true;
    });
  });

  /* ---------- searchable multi-select with order (selected first, reorderable) ---------- */
  const itemPicker = (host, items, value, onChange) => {
    host.innerHTML = '';
    const wrap = h('div', 'rounded border border-black/10');
    const chosenUl = h('ul', 'divide-y divide-black/5 border-b border-black/10 bg-gold/5');
    const search = h('div', 'p-2', `<input class="${INP}" placeholder="Search to add…">`);
    const restUl = h('ul', 'max-h-56 overflow-y-auto text-sm');
    wrap.append(chosenUl, search, restUl);
    host.append(wrap);
    const q = $('input', search);
    const set = (v) => { value = v; onChange(v); draw(); };
    const draw = () => {
      const chosen = value.map((id) => items.find((i) => i.id === id)).filter(Boolean);
      chosenUl.hidden = !chosen.length;
      chosenUl.innerHTML = chosen.map((it, i) => `<li class="flex items-center gap-2 px-3 py-1.5 text-sm"><span class="w-5 text-xs text-stone">${i + 1}</span><span class="flex-1">${esc(it.label)}</span>
        ${i > 0 ? `<button type="button" data-up="${i}" aria-label="Up">↑</button>` : ''}${i < chosen.length - 1 ? `<button type="button" data-down="${i}" aria-label="Down">↓</button>` : ''}
        <button type="button" class="text-red-700" data-rm="${esc(it.id)}">✕</button></li>`).join('');
      const term = q.value.toLowerCase();
      restUl.innerHTML = items.filter((i) => !value.includes(i.id) && (i.label + (i.sub ?? '')).toLowerCase().includes(term))
        .map((it) => `<li><button type="button" class="w-full px-3 py-1.5 text-start hover:bg-black/5" data-add="${esc(it.id)}">+ ${esc(it.label)} <span class="text-xs text-stone">${esc(it.sub ?? '')}</span></button></li>`).join('');
    };
    const swap = (i, j) => { const a = [...value]; [a[i], a[j]] = [a[j], a[i]]; set(a); };
    chosenUl.addEventListener('click', (e) => {
      const b = e.target.closest('button');
      if (!b) return;
      if (b.dataset.up) swap(+b.dataset.up, +b.dataset.up - 1);
      if (b.dataset.down) swap(+b.dataset.down, +b.dataset.down + 1);
      if (b.dataset.rm) set(value.filter((x) => x !== b.dataset.rm));
    });
    restUl.addEventListener('click', (e) => { const b = e.target.closest('[data-add]'); if (b) set([...value, b.dataset.add]); });
    q.addEventListener('input', draw);
    draw();
  };

  $$('[data-item-picker]').forEach((root) => {
    const input = $('input[type="hidden"]', root);
    const host = h('div');
    root.append(host);
    itemPicker(host, JSON.parse(root.dataset.itemPicker), JSON.parse(input.value || '[]'), (v) => { input.value = JSON.stringify(v); });
  });

  /* ---------- homepage section editor: form generated from the schema in app/lib/sections.php ---------- */
  const mediaField = (host, obj, key) => {
    const draw = () => {
      const v = obj[key];
      host.innerHTML = `<div class="flex items-start gap-3">
        ${v?.url ? thumb(v, 'aspect-[4/5] w-24 shrink-0 rounded') : '<div class="grid aspect-[4/5] w-24 shrink-0 place-items-center rounded border border-dashed border-black/20 text-xs text-stone">Empty</div>'}
        <div class="flex-1 space-y-2">
          <div class="flex flex-wrap gap-2">
            <button type="button" class="${BTN2}" data-pick>${v?.url ? 'Replace' : 'Choose / upload'}</button>
            ${v?.kind === 'video' ? `<button type="button" class="${BTN2}" data-poster>${v.poster ? 'Change' : 'Add'} video thumbnail</button>` : ''}
            ${v?.url ? '<button type="button" class="text-sm text-red-700" data-clear>Remove</button>' : ''}
          </div>
          ${v?.url ? `<div class="grid gap-2 sm:grid-cols-2"><input class="${INP}" placeholder="Alt text (AR)" dir="rtl" value="${esc(v.altAr)}" data-k="altAr"><input class="${INP}" placeholder="Alt text (EN)" value="${esc(v.altEn)}" data-k="altEn"></div>` : ''}
        </div></div>`;
      $('[data-pick]', host).onclick = async () => {
        const [m] = await openPicker();
        if (!m) return;
        obj[key] = { id: m.id, url: m.url, kind: m.kind, altAr: m.altAr || v?.altAr || '', altEn: m.altEn || v?.altEn || '' };
        draw();
      };
      const pb = $('[data-poster]', host);
      if (pb) pb.onclick = async () => { const [m] = await openPicker({ kind: 'image' }); if (m) { obj[key].poster = m.url; draw(); } };
      const cb = $('[data-clear]', host);
      if (cb) cb.onclick = () => { delete obj[key]; draw(); };
      $$('[data-k]', host).forEach((inp) => inp.addEventListener('input', () => { obj[key][inp.dataset.k] = inp.value; }));
    };
    draw();
  };

  const renderFields = (fields, obj, opts) => {
    const grid = h('div', 'grid gap-4 md:grid-cols-2');
    fields.forEach((f) => {
      const wide = ['textarea', 'media', 'list', 'products', 'collections'].includes(f.kind);
      const simple = ['text', 'link', 'textarea', 'number', 'bool', 'select'].includes(f.kind);
      const field = h(simple ? 'label' : 'div', 'block' + (wide ? ' md:col-span-2' : ''), `<span class="mb-1 block text-xs font-medium text-stone">${esc(f.label)}</span>`);
      const rtl = f.key.endsWith('Ar');
      const v = obj[f.key];
      let el;
      if (f.kind === 'text' || f.kind === 'link' || f.kind === 'textarea') {
        el = h(f.kind === 'textarea' ? 'textarea' : 'input', INP);
        if (f.kind === 'textarea') el.rows = 3;
        if (f.kind === 'link') el.placeholder = '/ar/shop?tag=new';
        if (rtl) el.dir = 'rtl';
        el.value = v ?? '';
        el.addEventListener('input', () => { obj[f.key] = el.value; });
      } else if (f.kind === 'number') {
        el = h('input', INP);
        Object.assign(el, { type: 'number', min: 1, value: v ?? '' });
        el.addEventListener('input', () => { if (el.value) obj[f.key] = +el.value; else delete obj[f.key]; });
      } else if (f.kind === 'bool') {
        el = h('input');
        Object.assign(el, { type: 'checkbox', checked: !!v });
        el.addEventListener('change', () => { obj[f.key] = el.checked; });
      } else if (f.kind === 'select') {
        el = h('select', INP, f.options.map((o) => `<option${o === (v ?? f.options[0]) ? ' selected' : ''}>${esc(o)}</option>`).join(''));
        el.addEventListener('change', () => { obj[f.key] = el.value; });
      } else if (f.kind === 'media') {
        el = h('div');
        mediaField(el, obj, f.key);
      } else if (f.kind === 'products' || f.kind === 'collections') {
        el = h('div');
        itemPicker(el, opts[f.kind], Array.isArray(v) ? v : [], (x) => { obj[f.key] = x; });
      } else if (f.kind === 'list') {
        if (!Array.isArray(obj[f.key])) obj[f.key] = [];
        const arr = obj[f.key];
        arr.forEach((x, i) => { if (!x || typeof x !== 'object' || Array.isArray(x)) arr[i] = {}; });
        el = h('div', 'space-y-3');
        const draw = () => {
          el.innerHTML = '';
          arr.forEach((item, i) => {
            const card = h('div', 'rounded border border-black/10 bg-[#faf9f6] p-4');
            const bar = h('div', 'mb-3 flex items-center gap-3 text-xs text-stone', `<span class="font-medium">#${i + 1}</span>
              ${i > 0 ? '<button type="button" data-up>↑ Move up</button>' : ''}${i < arr.length - 1 ? '<button type="button" data-down>↓ Move down</button>' : ''}
              <button type="button" class="ms-auto text-red-700" data-rm>Remove</button>`);
            bar.addEventListener('click', (e) => {
              const b = e.target.closest('button');
              if (!b) return;
              if ('up' in b.dataset) [arr[i - 1], arr[i]] = [arr[i], arr[i - 1]];
              if ('down' in b.dataset) [arr[i + 1], arr[i]] = [arr[i], arr[i + 1]];
              if ('rm' in b.dataset) arr.splice(i, 1);
              draw();
            });
            card.append(bar, renderFields(f.fields, item, opts));
            el.append(card);
          });
          const add = h('button', BTN2, `+ Add ${esc(f.label.toLowerCase().replace(/s$/, ''))}`);
          add.type = 'button';
          add.addEventListener('click', () => { arr.push({}); draw(); });
          el.append(add);
        };
        draw();
      }
      field.append(el);
      grid.append(field);
    });
    return grid;
  };

  $$('[data-section-editor]').forEach((root) => {
    const cfg = JSON.parse(root.dataset.sectionEditor);
    const data = cfg.data && typeof cfg.data === 'object' && !Array.isArray(cfg.data) ? cfg.data : {};
    root.append(renderFields(cfg.fields, data, { products: cfg.products, collections: cfg.collections }));
    const form = root.closest('form');
    const hidden = $('input[name="data"]', form);
    hidden.value = JSON.stringify(data);
    form.addEventListener('submit', () => { hidden.value = JSON.stringify(data); });
    form.addEventListener('input', () => { const m = $('[data-saved-msg]', form); if (m) m.remove(); });
  });

  /* ---------- media library page ---------- */
  $$('[data-media-library]').forEach((root) => {
    dropZone($('[data-dropzone]', root), { onUploaded: () => window.location.reload() });
    root.addEventListener('click', async (e) => {
      const f = e.target.closest('[data-kind-filter]');
      if (f) {
        $$('[data-kind-filter]', root).forEach((b) => { const on = b === f; b.classList.toggle('bg-ink', on); b.classList.toggle('text-ivory', on); b.classList.toggle('bg-white', !on); });
        $$('[data-media-item]', root).forEach((li) => { li.hidden = !!f.dataset.kindFilter && li.dataset.kind !== f.dataset.kindFilter; });
        return;
      }
      const c = e.target.closest('[data-copy]');
      if (c) { navigator.clipboard?.writeText(window.location.origin + c.dataset.copy); c.textContent = 'Copied ✓'; return; }
      const d = e.target.closest('[data-media-delete]');
      if (d && confirm(d.dataset.mediaDelete)) {
        const li = d.closest('[data-media-item]');
        await post(`/admin/api/media/${encodeURIComponent(li.dataset.mediaItem)}/delete`);
        li.remove();
      }
    });
    $$('[data-alt]', root).forEach((inp) => {
      let last = inp.value;
      inp.addEventListener('blur', () => {
        if (inp.value === last) return;
        last = inp.value;
        patchMedia(inp.closest('[data-media-item]').dataset.mediaItem, { [inp.dataset.alt]: inp.value });
      });
    });
  });

  /* ---------- product colours ---------- */
  $$('[data-colors]').forEach((box) => {
    const tpl = $('[data-color-template]', box);
    box.addEventListener('click', (e) => {
      if (e.target.closest('[data-color-remove]')) e.target.closest('[data-color-row]').remove();
      if (e.target.closest('[data-color-add]')) tpl.before(tpl.content.cloneNode(true));
    });
  });

  /* ---------- order status: warn before cancelling ---------- */
  $$('[data-order-form]').forEach((form) => {
    const warn = $('[data-cancel-warning]', form);
    $('select[name="status"]', form).addEventListener('change', (e) => { warn.hidden = !(e.target.value === 'cancelled' && form.dataset.was !== 'cancelled'); });
  });
})();
