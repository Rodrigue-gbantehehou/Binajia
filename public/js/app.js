// Mobile menu toggle and header behavior
document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.getElementById('mobile-menu-btn');
  const closeBtn = document.getElementById('close-menu');
  const menu = document.getElementById('mobile-menu');
  const overlay = document.getElementById('menu-overlay');
  const nav = document.querySelector('nav');

  function openMenu() {
    if (!menu || !overlay) return;
    menu.classList.remove('hidden');
    menu.classList.add('open');
    menu.setAttribute('aria-hidden', 'false');
    openBtn && openBtn.setAttribute('aria-expanded', 'true');
    overlay.classList.remove('hidden');
  }

  function closeMenu() {
    if (!menu || !overlay) return;
    menu.classList.add('hidden');
    menu.classList.remove('open');
    menu.setAttribute('aria-hidden', 'true');
    openBtn && openBtn.setAttribute('aria-expanded', 'false');
    overlay.classList.add('hidden');
  }

  openBtn && openBtn.addEventListener('click', openMenu);
  closeBtn && closeBtn.addEventListener('click', closeMenu);
  overlay && overlay.addEventListener('click', closeMenu);

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });

  // Header shadow/elevation on scroll
  const onScroll = () => {
    if (!nav) return;
    const scrolled = window.scrollY > 4;
    if (scrolled) {
      nav.classList.add('shadow-lg');
    } else {
      nav.classList.remove('shadow-lg');
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Scrollspy for active nav link
  const sections = ['accueil','apropos','evenements','lieux','contact']
    .map(id => ({ id, el: document.getElementById(id) }))
    .filter(s => !!s.el);
  const desktopLinks = Array.from(document.querySelectorAll('nav a[href^="#"]'));

  const updateActiveLink = () => {
    const scrollPos = window.scrollY + 120; // offset for fixed header
    let current = null;
    for (const s of sections) {
      const top = s.el.offsetTop;
      if (scrollPos >= top) current = s.id;
    }
    desktopLinks.forEach(a => {
      const href = a.getAttribute('href') || '';
      const isActive = current && href === `#${current}`;
      a.classList.toggle('nav-link-active', !!isActive);
    });
  };

  window.addEventListener('scroll', updateActiveLink, { passive: true });
  window.addEventListener('resize', updateActiveLink);
  updateActiveLink();

  // Reusable horizontal slider initializer
  function initSlider({ sliderId, prevId, nextId, dotsId, intervalMs = 3000 }) {
    const track = document.getElementById(sliderId);
    const prev = document.getElementById(prevId);
    const next = document.getElementById(nextId);
    const dots = document.getElementById(dotsId);
    if (!track || !prev || !next) return;

    const items = Array.from(track.querySelectorAll('article'));
    if (!items.length) return;

    const scrollToIndex = (i) => {
      const clamped = Math.max(0, Math.min(i, items.length - 1));
      const target = items[clamped];
      if (!target) return;
      track.scrollTo({ left: target.offsetLeft - 16, behavior: 'smooth' });
    };

    const getCurrentIndex = () => {
      const x = track.scrollLeft;
      let best = 0, bestDelta = Infinity;
      items.forEach((el, idx) => {
        const d = Math.abs(el.offsetLeft - x);
        if (d < bestDelta) { bestDelta = d; best = idx; }
      });
      return best;
    };

    const updateDots = () => {
      if (!dots) return;
      const cur = getCurrentIndex();
      const list = Array.from(dots.children);
      list.forEach((d, i) => d.classList.toggle('slider-dot--active', i === cur));
    };

    // Buttons
    const scrollAmount = () => Math.min(320, track.clientWidth * 0.8);
    prev.addEventListener('click', () => track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: scrollAmount(), behavior: 'smooth' }));

    // Dots
    if (dots) {
      dots.innerHTML = '';
      items.forEach((_, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'slider-dot';
        b.setAttribute('aria-label', `Aller à l'élément ${i + 1}`);
        b.addEventListener('click', () => scrollToIndex(i));
        dots.appendChild(b);
      });
      updateDots();
    }

    // Update active dot on scroll (throttled)
    let raf = null;
    track.addEventListener('scroll', () => {
      if (raf) return; raf = requestAnimationFrame(() => { updateDots(); raf = null; });
    }, { passive: true });

    // Autoplay with pause on hover and tab hidden
    let interval = null;
    const start = () => {
      if (interval || !intervalMs) return;
      interval = setInterval(() => {
        const idx = getCurrentIndex();
        const nextIdx = (idx + 1) % items.length;
        scrollToIndex(nextIdx);
      }, intervalMs);
    };
    const stop = () => { if (interval) { clearInterval(interval); interval = null; } };
    start();

    [track, prev, next].forEach(el => {
      el.addEventListener('mouseenter', stop);
      el.addEventListener('mouseleave', start);
      el.addEventListener('touchstart', stop, { passive: true });
      el.addEventListener('touchend', start, { passive: true });
    });
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stop(); else start();
    });
  }

  // Init Places slider
  initSlider({ sliderId: 'places-slider', prevId: 'places-prev', nextId: 'places-next', dotsId: 'places-dots', intervalMs: 3000 });

  // Init Testimonials slider
  initSlider({ sliderId: 'testimonials-slider', prevId: 'testimonials-prev', nextId: 'testimonials-next', dotsId: 'testimonials-dots', intervalMs: 3000 });
});