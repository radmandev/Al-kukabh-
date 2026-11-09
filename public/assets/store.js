// Al Kawkaba storefront behaviour — plain JS, no build step.
// Bag / wishlist / recently viewed live in localStorage (per device); prices are re-checked server-side at checkout.
(() => {
  'use strict';
  const { l, t, currency } = window.KW;
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name="csrf"]')?.content || '';
  const rtl = document.documentElement.dir === 'rtl';

  /* ---------- helpers ---------- */
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  const fmt = {};
  const money = (v) => {
    const k = v % 1 ? 2 : 0;
    fmt[k] ??= new Intl.NumberFormat(l === 'ar' ? 'ar-SA-u-nu-latn' : 'en', { style: 'currency', currency, maximumFractionDigits: k });
    return fmt[k].format(v);
  };
  const WIDTHS = [128, 256, 384, 640, 828, 1080, 1440, 1920, 2560];
  const imgUrl = (url, w) => (/^\/(media|brand)\/[^?]+\.(jpe?g|png|webp|avif|gif)$/i.test(url || '')
    ? '/img/' + w + url.split('/').map((s) => encodeURIComponent(decodeURIComponent(s))).join('/') : url);
  const imgHtml = (url, alt, cls, sizes, priority) => {
    const set = imgUrl(url, 640) !== url ? WIDTHS.map((w) => `${imgUrl(url, w)} ${w}w`).join(', ') : '';
    return `<img src="${esc(imgUrl(url, 1080))}"${set ? ` srcset="${esc(set)}" sizes="${esc(sizes)}"` : ''} alt="${esc(alt)}" class="${cls}"${priority ? '' : ' loading="lazy"'} decoding="async">`;
  };
  const ICON = {
    plus: 'M12 5v14M5 12h14', minus: 'M5 12h14',
  };
  const icon = (name, cls) => `<svg viewBox="0 0 24 24" class="${cls}" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="${ICON[name]}"/></svg>`;
  const post = (url, body) => fetch(url, {
    method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF': csrf }, body: JSON.stringify(body),
  }).then((r) => r.json());
  const load = (k, d) => { try { const v = JSON.parse(localStorage.getItem(k)); return Array.isArray(v) ? v : d; } catch { return d; } };
  const save = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} };
  const ids = (a) => a.map((x) => (typeof x === 'string' ? x : x?.id)).filter(Boolean); // older versions stored whole products

  /* ---------- scroll lock shared by overlays ---------- */
  const locks = new Set();
  const lock = (key, on) => { on ? locks.add(key) : locks.delete(key); document.body.style.overflow = locks.size ? 'hidden' : ''; };

  /* ---------- state ---------- */
  let lines = load('kw_bag', []).filter((x) => x && x.productId);
  let wish = ids(load('kw_wish', []));
  let recent = ids(load('kw_recent', []));
  const bagListeners = [];
  const bagChanged = () => { save('kw_bag', lines); renderBag(); bagListeners.forEach((f) => f()); };

  const addLine = (x) => {
    const i = lines.findIndex((y) => y.productId === x.productId && y.size === x.size && y.color === x.color);
    if (i < 0) lines.push(x); else lines[i].qty = Math.min(lines[i].qty + x.qty, 10);
    bagChanged();
  };

  /* ---------- wishlist hearts ---------- */
  const syncWish = (root = document) => {
    $$('[data-wish]', root).forEach((b) => {
      const on = wish.includes(b.dataset.wish);
      b.setAttribute('aria-pressed', String(on));
      const svg = $('svg', b);
      if (svg) { svg.setAttribute('fill', on ? 'currentColor' : 'none'); svg.classList.toggle('text-gold', on); }
    });
    const dot = $('[data-wish-dot]');
    if (dot) dot.hidden = !wish.length;
  };
  const toggleWish = (id) => {
    wish = wish.includes(id) ? wish.filter((x) => x !== id) : [id, ...wish];
    save('kw_wish', wish);
    syncWish();
  };

  /* ---------- header: transparent over the homepage hero, mega menu, drawer, search ---------- */
  const header = $('[data-header]');
  let paintHeader = () => {};
  if (header) {
    const isHome = header.dataset.home === '1';
    const solid = header.dataset.solid.split(' '), clear = header.dataset.clear.split(' ');
    const panel = $('[data-mega-panel]');
    let scrolled = false, mega = false;
    paintHeader = () => {
      const over = isHome && !scrolled && !mega && !locks.has('drawer');
      header.classList.remove(...(over ? solid : clear));
      header.classList.add(...(over ? clear : solid));
    };
    const onScroll = () => { scrolled = window.scrollY > (isHome ? window.innerHeight * 0.85 : 40); paintHeader(); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    $$('[data-nav]', header).forEach((a) => a.addEventListener('mouseenter', () => {
      mega = a.hasAttribute('data-mega') && !!panel;
      if (panel) panel.hidden = !mega;
      paintHeader();
    }));
    header.addEventListener('mouseleave', () => { mega = false; if (panel) panel.hidden = true; paintHeader(); });
  }

  /** Slide-in panel (mobile menu, bag): wrapper + overlay + panel with data-closed translate class. */
  const sheet = (wrap, key) => {
    if (!wrap) return () => {};
    const overlay = $('[data-' + key + '-overlay]', wrap), panelEl = $('[data-' + key + '-panel]', wrap);
    const closed = panelEl.dataset.closed;
    return (open) => {
      wrap.classList.toggle('pointer-events-none', !open);
      wrap.setAttribute('aria-hidden', String(!open));
      overlay.classList.toggle('opacity-100', open);
      overlay.classList.toggle('opacity-0', !open);
      panelEl.classList.toggle('translate-x-0', open);
      panelEl.classList.toggle(closed, !open);
      lock(key, open);
      paintHeader();
    };
  };
  const setDrawer = sheet($('[data-drawer]'), 'drawer');
  const setBag = sheet($('[data-bag]'), 'bag');

  const search = $('[data-search]');
  const setSearch = (open) => {
    if (!search) return;
    search.hidden = !open;
    lock('search', open);
    if (open) $('input', search).focus();
  };

  /* ---------- bag drawer ---------- */
  const lineHtml = (x, i) => `
    <li class="flex gap-4 py-5">
      <a href="/${l}/product/${encodeURIComponent(x.slug)}" data-bag-close class="relative aspect-[3/4] w-24 shrink-0 bg-sand">${x.image ? imgHtml(x.image, '', 'absolute inset-0 h-full w-full object-cover', '96px') : ''}</a>
      <div class="flex flex-1 flex-col text-sm">
        <p class="display text-lg">${esc(l === 'ar' ? x.nameAr : x.nameEn)}</p>
        <p class="mt-1 text-stone">${esc([x.size, x.color].filter(Boolean).join(' · '))}</p>
        <p class="mt-1">${esc(money(x.price))}</p>
        <div class="mt-auto flex items-center justify-between pt-3">
          <div class="flex items-center border border-line">
            <button type="button" class="p-2" data-line="${i}" data-step="-1" aria-label="-">${icon('minus', 'h-3 w-3')}</button>
            <span class="w-6 text-center">${x.qty}</span>
            <button type="button" class="p-2" data-line="${i}" data-step="1" aria-label="+">${icon('plus', 'h-3 w-3')}</button>
          </div>
          <button type="button" data-line-remove="${i}" class="link-u text-xs text-stone">${esc(t.remove)}</button>
        </div>
      </div>
    </li>`;

  function renderBag() {
    const count = lines.reduce((n, x) => n + x.qty, 0);
    const badge = $('[data-bag-count]');
    if (badge) { badge.hidden = !count; badge.textContent = count; }
    const btn = $('[data-bag-open]');
    if (btn) btn.setAttribute('aria-label', `${t.bag} (${count})`);
    const n = $('[data-bag-lines]');
    if (n) n.textContent = lines.length;
    const body = $('[data-bag-body]');
    if (!body) return;
    if (!lines.length) {
      body.innerHTML = `<div class="grid flex-1 place-items-center p-10 text-center"><div><p class="display text-2xl">${esc(t.emptyBag)}</p>
        <a href="/${l}/shop" data-bag-close class="btn btn-dark mt-8">${esc(t.continueShopping)}</a></div></div>`;
      return;
    }
    const subtotal = lines.reduce((s, x) => s + x.price * x.qty, 0);
    body.innerHTML = `<ul class="flex-1 divide-y divide-line overflow-y-auto px-6">${lines.map(lineHtml).join('')}</ul>
      <div class="border-t border-line p-6">
        <div class="flex justify-between text-sm"><span>${esc(t.subtotal)}</span><span>${esc(money(subtotal))}</span></div>
        <a href="/${l}/checkout" data-bag-close class="btn btn-dark mt-5 w-full">${esc(t.checkout)}</a>
      </div>`;
  }

  /* ---------- quick view ---------- */
  const closeModal = () => { $$('[data-modal]').forEach((m) => m.remove()); lock('modal', false); };
  const openQuickView = async (id) => {
    const r = await fetch(`/${l}/quickview/${encodeURIComponent(id)}`);
    if (!r.ok) return;
    closeModal();
    document.body.insertAdjacentHTML('beforeend', await r.text());
    lock('modal', true);
    syncWish($('[data-modal]'));
  };

  /* ---------- add to bag (product page + quick view) ---------- */
  const atb = (root, el) => {
    const p = JSON.parse(root.dataset.atb);
    const c = el.closest('[data-pick-color]');
    if (c) {
      root.dataset.color = c.dataset.pickColor;
      $$('[data-pick-color]', root).forEach((b) => {
        const on = b === c;
        b.setAttribute('aria-pressed', String(on));
        b.classList.toggle('border-ink', on);
        b.classList.toggle('border-transparent', !on);
      });
      const name = $('[data-color-name]', root);
      if (name) name.textContent = root.dataset.color;
      return;
    }
    const s = el.closest('[data-pick-size]');
    if (s) {
      root.dataset.size = s.dataset.pickSize;
      $$('[data-pick-size]', root).forEach((b) => {
        const on = b === s;
        b.setAttribute('aria-pressed', String(on));
        ['border-ink', 'bg-ink', 'text-ivory'].forEach((k) => b.classList.toggle(k, on));
        ['border-line', 'hover:border-ink'].forEach((k) => b.classList.toggle(k, !on));
      });
      const err = $('[data-atb-err]', root);
      if (err) err.hidden = true;
      return;
    }
    const q = el.closest('[data-qty-step]');
    if (q) {
      const v = Math.max(1, Math.min(10, (+root.dataset.qty || 1) + +q.dataset.qtyStep));
      root.dataset.qty = v;
      $('[data-qty]', root).textContent = v;
      return;
    }
    const buy = !!el.closest('[data-atb-buy]');
    if (!buy && !el.closest('[data-atb-add]')) return;
    if (p.sizes.length && !root.dataset.size) {
      const err = $('[data-atb-err]', root);
      if (err) { err.textContent = t.selectSize; err.hidden = false; }
      return;
    }
    addLine({
      productId: p.id, slug: p.slug, nameAr: p.nameAr, nameEn: p.nameEn, image: p.image || undefined,
      price: p.salePrice ?? p.price, size: root.dataset.size || '', color: root.dataset.color || '', qty: +root.dataset.qty || 1,
    });
    if (buy) { window.location.href = `/${l}/checkout`; return; }
    closeModal();
    setBag(true);
  };

  /* ---------- one click handler for everything ---------- */
  document.addEventListener('click', (e) => {
    const el = e.target instanceof Element ? e.target : null;
    if (!el) return;
    const wishBtn = el.closest('[data-wish]');
    if (wishBtn) { e.preventDefault(); toggleWish(wishBtn.dataset.wish); return; }
    const root = el.closest('[data-atb]');
    if (root) { atb(root, el); return; }
    const qv = el.closest('[data-qv]');
    if (qv) { openQuickView(qv.dataset.qv); return; }
    if (el.closest('[data-modal-close]')) { closeModal(); return; }
    if (el.closest('[data-bag-open]')) { setBag(true); return; }
    if (el.closest('[data-bag-close]')) { setBag(false); return; }
    const step = el.closest('[data-step]');
    if (step) {
      const i = +step.dataset.line;
      lines[i].qty = Math.max(1, Math.min(10, lines[i].qty + +step.dataset.step));
      bagChanged();
      return;
    }
    const rm = el.closest('[data-line-remove]');
    if (rm) { lines.splice(+rm.dataset.lineRemove, 1); bagChanged(); return; }
    if (el.closest('[data-drawer-open]')) { setDrawer(true); return; }
    if (el.closest('[data-drawer-close]')) { setDrawer(false); return; }
    if (el.closest('[data-search-open]')) { setSearch(true); return; }
    if (el.closest('[data-search-close]')) { setSearch(false); return; }
    const lang = el.closest('[data-lang]');
    if (lang) document.cookie = `lang=${lang.dataset.lang};path=/;max-age=31536000`;
    const railBtn = el.closest('[data-rail-step]');
    if (railBtn) {
      const rail = $('[data-rail]', railBtn.closest('.relative'));
      const dir = getComputedStyle(rail).direction === 'rtl' ? -1 : 1;
      rail.scrollBy({ left: +railBtn.dataset.railStep * dir * rail.clientWidth * 0.8, behavior: 'smooth' });
    }
    if (el.closest('[data-scroll-buy]')) document.getElementById('buy')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    setBag(false); setDrawer(false); setSearch(false); closeModal();
  });

  // keep tabs in sync
  window.addEventListener('storage', (e) => {
    if (e.key === 'kw_bag') { lines = load('kw_bag', []); renderBag(); bagListeners.forEach((f) => f()); }
    if (e.key === 'kw_wish') { wish = ids(load('kw_wish', [])); syncWish(); }
  });

  /* ---------- filters: GET form that re-submits when a control changes ---------- */
  $$('[data-autoform]').forEach((f) => f.addEventListener('change', () => f.requestSubmit()));

  /* ---------- newsletter ---------- */
  $$('[data-newsletter]').forEach((f) => f.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = $('button', f);
    btn.disabled = true;
    try {
      const r = await fetch(f.action, { method: 'POST', body: new FormData(f), headers: { Accept: 'application/json' } }).then((x) => x.json());
      if (r.ok) f.outerHTML = `<p class="mt-8 text-gold-soft">${esc(t.subscribed)}</p>`;
    } finally { btn.disabled = false; }
  }));

  /* ---------- product cards fetched by id (wishlist, recently viewed) ---------- */
  const cards = (list) => (list.length ? fetch(`/${l}/cards?ids=${list.map(encodeURIComponent).join(',')}`).then((r) => r.text()) : Promise.resolve(''));

  const wl = $('[data-wishlist]');
  if (wl) {
    const empty = $('[data-wishlist-empty]');
    cards(wish).then((html) => { wl.innerHTML = html; empty.hidden = !!html.trim(); syncWish(wl); });
  }

  const rec = $('[data-recent]');
  if (rec) {
    const id = rec.dataset.recent;
    const shown = recent.filter((x) => x !== id).slice(0, 4);
    recent = [id, ...recent.filter((x) => x !== id)].slice(0, 12);
    save('kw_recent', recent);
    cards(shown).then((html) => {
      if (!html.trim()) return;
      $('[data-recent-grid]', rec).innerHTML = html;
      rec.hidden = false;
      syncWish(rec);
    });
  }

  /* ---------- sticky mobile buy bar ---------- */
  const sticky = $('[data-sticky-buy]'), buy = document.getElementById('buy');
  if (sticky && buy) {
    const f = () => sticky.classList.toggle('translate-y-full', buy.getBoundingClientRect().bottom >= 0);
    f();
    window.addEventListener('scroll', f, { passive: true });
  }

  /* ---------- product gallery ---------- */
  $$('[data-gallery]').forEach((g) => {
    const items = JSON.parse(g.dataset.gallery);
    const n = items.length;
    const thumbs = $$('[data-g-thumb]', g), dots = $$('[data-g-dot]', g);
    const main = $('[data-g-main]', g), strip = $('[data-g-strip]', g);
    const full = $('[data-g-full]', g), stage = $('[data-g-stage]', g), count = $('[data-g-count]', g);
    let i = 0, zoom = null, isFull = false;

    const slide = (it, sizes) => (it.kind === 'video'
      ? `<video src="${esc(it.url)}"${it.poster ? ` poster="${esc(imgUrl(it.poster, 1080))}"` : ''} class="absolute inset-0 h-full w-full object-cover" muted loop playsinline autoplay aria-label="${esc(it.alt)}"></video>`
      : imgHtml(it.url, it.alt, 'absolute inset-0 h-full w-full object-cover', sizes, true));

    const paintDots = () => dots.forEach((d, j) => {
      d.classList.toggle('w-5', j === i); d.classList.toggle('bg-ink', j === i);
      d.classList.toggle('w-1', j !== i); d.classList.toggle('bg-ink/30', j !== i);
    });
    const setI = (j) => {
      j = (j + n) % n;
      if (j === i) return;
      i = j;
      thumbs.forEach((b, k) => {
        const on = k === i;
        b.setAttribute('aria-current', String(on));
        ['opacity-100', 'ring-1', 'ring-ink'].forEach((c) => b.classList.toggle(c, on));
        ['opacity-60', 'hover:opacity-100'].forEach((c) => b.classList.toggle(c, !on));
      });
      paintDots();
      if (main) {
        main.innerHTML = slide(items[i], '55vw');
        main.classList.remove('reveal'); void main.offsetWidth; main.classList.add('reveal');
      }
      if (isFull) paintFull();
    };

    const applyZoom = () => {
      const im = $('img', stage);
      if (!im) return;
      im.style.transform = zoom ? 'scale(2.2)' : '';
      im.style.transformOrigin = zoom ? `${zoom.x}% ${zoom.y}%` : '';
      stage.classList.toggle('cursor-zoom-out', !!zoom);
      stage.classList.toggle('cursor-zoom-in', !zoom);
    };
    const paintFull = () => {
      const cur = items[i];
      zoom = null;
      stage.className = 'absolute inset-0 overflow-hidden' + (cur.kind === 'image' ? ' cursor-zoom-in' : '');
      stage.innerHTML = cur.kind === 'video'
        ? `<video src="${esc(cur.url)}"${cur.poster ? ` poster="${esc(imgUrl(cur.poster, 1920))}"` : ''} class="h-full w-full object-contain" controls autoplay playsinline></video>`
        : imgHtml(cur.url, cur.alt, 'absolute inset-0 h-full w-full object-contain transition-transform duration-300', '100vw', true);
      if (count) count.textContent = `${i + 1} / ${n}`;
    };
    const point = (e) => { const r = stage.getBoundingClientRect(); return { x: ((e.clientX - r.left) / r.width) * 100, y: ((e.clientY - r.top) / r.height) * 100 }; };
    const onKey = (e) => {
      if (e.key === 'Escape') setFull(false);
      if (e.key === 'ArrowRight') setI(i + (rtl ? -1 : 1));
      if (e.key === 'ArrowLeft') setI(i + (rtl ? 1 : -1));
    };
    const setFull = (on) => {
      isFull = on;
      full.hidden = !on;
      lock('gallery', on);
      if (on) { paintFull(); window.addEventListener('keydown', onKey); } else { stage.innerHTML = ''; window.removeEventListener('keydown', onKey); }
    };

    stage.addEventListener('click', (e) => { if (items[i].kind !== 'image') return; zoom = zoom ? null : point(e); applyZoom(); });
    stage.addEventListener('mousemove', (e) => { if (!zoom) return; zoom = point(e); applyZoom(); });
    thumbs.forEach((b, j) => { b.addEventListener('mouseenter', () => setI(j)); b.addEventListener('click', () => setI(j)); });
    $$('[data-g-open]', g).forEach((b) => b.addEventListener('click', () => { setI(+b.dataset.gOpen); setFull(true); }));
    $('[data-g-open-current]', g)?.addEventListener('click', () => setFull(true));
    $('[data-g-close]', g)?.addEventListener('click', () => setFull(false));
    $$('[data-g-step]', g).forEach((b) => b.addEventListener('click', () => setI(i + +b.dataset.gStep)));
    strip?.addEventListener('scroll', () => {
      const j = Math.round(Math.abs(strip.scrollLeft) / strip.clientWidth);
      if (j !== i && j < n) { i = j; paintDots(); }
    }, { passive: true });
  });

  /* ---------- hero slideshow ---------- */
  const IMAGE_MS = 3000;
  const VIDEO_MAX_MS = 90000; // safety net if a video never fires "ended" (e.g. autoplay blocked)
  $$('[data-hero]').forEach((root) => {
    const { slides, strip } = JSON.parse(root.dataset.hero);
    const n = slides.length;
    const layers = $$('[data-slide]', root), dots = $$('[data-dot]', root);
    const track = strip ? $('[data-hero-track]', root) : null;
    const pauseBtn = $('[data-hero-pause]', root);
    const mq = window.matchMedia('(max-width: 767px)');
    let i = 0, paused = false, timer = 0, snapT = 0;

    const isVideo = (j) => (mq.matches && slides[j].m !== null ? slides[j].m : slides[j].d);
    // restart the active video from the beginning, pause the others
    const syncVideos = () => layers.forEach((layer, j) => $$('video', layer).forEach((v) => {
      if (j === i && !paused && v.getClientRects().length) { v.currentTime = 0; v.play().catch(() => {}); } else v.pause();
    }));
    // images advance on a timer; videos advance when they end
    const schedule = () => {
      clearTimeout(timer);
      if (n < 2 || paused) return;
      timer = setTimeout(() => go((i + 1) % n), isVideo(i) ? VIDEO_MAX_MS : IMAGE_MS);
    };
    const setPos = (pos, animate) => {
      ['transition-transform', 'duration-[1.2s]', 'ease-[var(--ease-lux)]'].forEach((c) => track.classList.toggle(c, animate));
      track.style.transform = `translateX(-${(pos * 100) / 3}%)`;
    };
    const go = (j) => {
      const prev = i;
      i = j;
      layers.forEach((layer, k) => {
        const on = k === i;
        layer.classList.toggle('opacity-100', on);
        layer.classList.toggle('opacity-0', !on);
        layer.classList.toggle('pointer-events-none', !on);
        if (on) layer.removeAttribute('aria-hidden'); else layer.setAttribute('aria-hidden', 'true');
        $$('[data-kb]', layer).forEach((el) => { el.classList.remove('kenburns'); if (on) { void el.offsetWidth; el.classList.add('kenburns'); } });
        const cap = $('[data-caption]', layer);
        if (cap) cap.hidden = !on;
      });
      dots.forEach((d, k) => {
        d.setAttribute('aria-current', String(k === i));
        d.firstElementChild.classList.toggle('bg-ivory', k === i);
        d.firstElementChild.classList.toggle('bg-ivory/35', k !== i);
      });
      if (track) {
        clearTimeout(snapT);
        if (i === 0 && prev === n - 1) {
          // slide onto the cloned first frames, then jump back without animation
          setPos(n, true);
          snapT = setTimeout(() => { setPos(0, false); requestAnimationFrame(() => requestAnimationFrame(() => setPos(0, true))); }, 1300);
        } else setPos(i, true);
      }
      syncVideos();
      schedule();
    };

    layers.forEach((layer, j) => $$('video', layer).forEach((v) => v.addEventListener('ended', () => { if (j === i && n > 1) go((i + 1) % n); })));
    dots.forEach((d, k) => d.addEventListener('click', () => go(k)));
    pauseBtn?.addEventListener('click', () => {
      paused = !paused;
      pauseBtn.setAttribute('aria-label', paused ? 'Play' : 'Pause');
      $('[data-icon-pause]', pauseBtn).toggleAttribute('hidden', paused);
      $('[data-icon-play]', pauseBtn).toggleAttribute('hidden', !paused);
      syncVideos();
      schedule();
    });
    mq.addEventListener('change', () => { syncVideos(); schedule(); });
    syncVideos();
    schedule();
  });

  /* ---------- checkout ---------- */
  const co = $('[data-checkout]');
  if (co) {
    const { shipping } = JSON.parse(co.dataset.checkout);
    const form = $('[data-checkout-form]', co), empty = $('[data-checkout-empty]', co);
    const submit = $('[data-co-submit]', form), errEl = $('[data-co-err]', form), codeEl = $('[data-co-code]', form);
    let disc = null, pending = false;
    const subtotalOf = () => lines.reduce((s, x) => s + x.price * x.qty, 0);
    const render = () => {
      form.hidden = !lines.length;
      empty.hidden = !!lines.length;
      if (!lines.length) return;
      $('[data-co-lines]', form).innerHTML = lines.map((x) => `
        <li class="flex gap-4 text-sm">
          <div class="relative aspect-[3/4] w-16 shrink-0 bg-line">${x.image ? imgHtml(x.image, '', 'absolute inset-0 h-full w-full object-cover', '64px') : ''}
            <span class="absolute -end-2 -top-2 grid h-5 w-5 place-items-center rounded-full bg-ink text-[.65rem] text-ivory">${x.qty}</span></div>
          <div class="flex-1"><p>${esc(l === 'ar' ? x.nameAr : x.nameEn)}</p><p class="text-stone">${esc([x.size, x.color].filter(Boolean).join(' · '))}</p></div>
          <p>${esc(money(x.price * x.qty))}</p>
        </li>`).join('');
      const subtotal = subtotalOf();
      const after = subtotal - (disc?.amount ?? 0);
      const ship = shipping.freeOver > 0 && after >= shipping.freeOver ? 0 : shipping.flat;
      $('[data-co-subtotal]', form).textContent = money(subtotal);
      $('[data-co-disc-row]', form).hidden = !disc?.amount;
      $('[data-co-disc-code]', form).textContent = disc?.code ?? '';
      $('[data-co-disc]', form).textContent = money(disc?.amount ?? 0);
      $('[data-co-ship]', form).textContent = ship ? money(ship) : t.free;
      $('[data-co-total]', form).textContent = money(after + ship);
    };
    bagListeners.push(render);
    render();

    $('[data-co-apply]', form).addEventListener('click', async () => {
      const r = await post(`/${l}/discount`, { code: codeEl.value, subtotal: subtotalOf() }).catch(() => null);
      disc = r;
      $('[data-co-code-err]', form).hidden = !!r;
      render();
    });
    const bank = $('[data-co-bank]', form);
    $$('input[name="pay"]', form).forEach((r) => r.addEventListener('change', () => { if (bank) bank.hidden = r.value !== 'bank' || !r.checked; }));

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (pending) return;
      const fd = new FormData(form);
      const payment = fd.get('pay');
      if (!payment) return;
      pending = true;
      submit.disabled = true;
      const label = submit.textContent;
      submit.textContent = '…';
      errEl.hidden = true;
      const body = Object.fromEntries(['name', 'email', 'phone', 'country', 'city', 'address', 'notes'].map((k) => [k, String(fd.get(k) ?? '')]));
      const r = await post(`/${l}/checkout`, {
        ...body, payment, code: disc?.code ?? undefined,
        lines: lines.map((x) => ({ productId: x.productId, size: x.size, color: x.color, qty: x.qty })),
      }).catch(() => ({ error: 'required' }));
      if (r.error) {
        errEl.textContent = t[r.error] ?? r.error;
        errEl.hidden = false;
        pending = false;
        submit.disabled = false;
        submit.textContent = label;
        return;
      }
      lines = [];
      save('kw_bag', lines);
      window.location.href = r.url;
    });
  }

  // video showcase: the centre piece plays; neighbours sit smaller and dimmed, then the stage turns to the next
  $$('[data-showcase]').forEach((root) => {
    const items = $$('[data-show-item]', root);
    const dots = $$('[data-show-dot]', root);
    const n = items.length;
    if (!n) return;
    const MAX_MS = 9000;   // longest time one video stays in the centre
    const MIN_MS = 4500;   // shortest (very short clips loop until then)
    let active = 0, timer = 0, visible = false;
    const narrow = () => innerWidth < 768;

    const layout = () => {
      items.forEach((el, i) => {
        let o = i - active;
        if (o > n / 2) o -= n;
        if (o < -n / 2) o += n;
        const a = Math.abs(o);
        const step = narrow() ? 64 : 82;            // % of a card width between neighbours
        const scale = a === 0 ? 1 : a === 1 ? 0.8 : 0.64;
        const show = a <= (narrow() ? 1 : 2);
        el.style.transform = `translateX(calc(-50% + ${o * step}%)) scale(${scale})`;
        el.style.transformOrigin = 'center 40%';
        el.style.opacity = show ? (a === 0 ? '1' : a === 1 ? '.55' : '.25') : '0';
        el.style.filter = a === 0 ? 'none' : 'saturate(.6) brightness(.95)';
        el.style.zIndex = String(10 - a);
        el.style.pointerEvents = show ? 'auto' : 'none';
        el.style.outlineColor = a === 0 ? 'rgba(214,189,138,.75)' : 'transparent';
        el.setAttribute('aria-hidden', a === 0 ? 'false' : 'true');
        const cap = $('[data-show-cap]', el);
        if (cap) cap.style.opacity = a === 0 ? '1' : '0';
        const v = $('[data-show-video]', el);
        if (v) {
          if (a === 0 && visible) { v.loop = true; v.play().catch(() => {}); }
          else { v.pause(); if (a > 0) try { v.currentTime = 0; } catch {} }
        }
      });
      dots.forEach((d, i) => {
        d.firstElementChild.style.background = i === active ? 'var(--color-gold)' : '';
        d.setAttribute('aria-current', i === active ? 'true' : 'false');
      });
    };

    const schedule = () => {
      clearTimeout(timer);
      if (!visible || n < 2 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      const v = $('[data-show-video]', items[active]);
      const dur = v && isFinite(v.duration) && v.duration > 0 ? v.duration * 1000 : MAX_MS;
      timer = setTimeout(() => go(active + 1), Math.min(MAX_MS, Math.max(MIN_MS, dur)));
    };
    const go = (i) => { active = (i + n) % n; layout(); schedule(); };

    items.forEach((el, i) => el.addEventListener('click', () => i !== active && go(i)));
    dots.forEach((d, i) => d.addEventListener('click', () => go(i)));
    $$('[data-show-step]', root).forEach((b) => b.addEventListener('click', () => go(active + Number(b.dataset.showStep))));
    addEventListener('resize', layout, { passive: true });
    // only play while the showcase is on screen
    new IntersectionObserver(([e]) => { visible = e.isIntersecting; layout(); schedule(); }, { threshold: 0.25 }).observe(root);
    layout();
  });

  // homepage blocks fade up the first time they scroll into view (content stays visible without JS)
  const reveals = [...document.querySelectorAll('[data-reveal]')].filter((el) => el.getBoundingClientRect().top > innerHeight);
  if (reveals.length && 'IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const io = new IntersectionObserver((es) => es.forEach((e) => {
      if (!e.isIntersecting) return;
      e.target.style.opacity = '1';
      e.target.style.transform = 'none';
      io.unobserve(e.target);
    }), { rootMargin: '0px 0px -12% 0px' });
    reveals.forEach((el) => {
      el.style.cssText = 'opacity:0;transform:translateY(40px);transition:opacity 1.2s var(--ease-lux),transform 1.2s var(--ease-lux)';
      io.observe(el);
    });
  }

  renderBag();
  syncWish();
})();
