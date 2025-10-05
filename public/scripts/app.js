// Global UI interactions for Binajia
// Smooth anchor scroll
(function(){
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const href = this.getAttribute('href');
      if (!href || href === '#') return;
      const target = document.querySelector(href);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      try { closeMobileMenu && closeMobileMenu(); } catch(_){}
    });
  });
})();

// Mobile menu toggle
(function(){
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  const closeMenuBtn = document.getElementById('close-menu');
  const menuOverlay = document.getElementById('menu-overlay');
  if (!mobileMenu || !menuOverlay) return;
  function openMobileMenu(){ mobileMenu.classList.add('active'); menuOverlay.classList.remove('hidden'); document.body.style.overflow='hidden'; }
  function closeMobileMenu(){ mobileMenu.classList.remove('active'); menuOverlay.classList.add('hidden'); document.body.style.overflow=''; }
  window.closeMobileMenu = closeMobileMenu;
  mobileMenuBtn && mobileMenuBtn.addEventListener('click', openMobileMenu);
  closeMenuBtn && closeMenuBtn.addEventListener('click', closeMobileMenu);
  menuOverlay && menuOverlay.addEventListener('click', closeMobileMenu);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeMobileMenu(); });
})();

// Navbar shadow on scroll
(function(){
  const navbar = document.querySelector('nav');
  if (!navbar) return;
  function updateShadow(){
    const s = window.pageYOffset;
    navbar.style.boxShadow = s<=0 ? '0 10px 15px -3px rgba(0,0,0,0.08)' : '0 20px 25px -5px rgba(0,0,0,0.12)';
  }
  updateShadow();
  window.addEventListener('scroll', updateShadow, { passive: true });
})();

// Simple appear animations for cards
(function(){
  const els = document.querySelectorAll('.event-card, .card-hover');
  if (!('IntersectionObserver' in window) || !els.length) return;
  const observer = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        entry.target.style.opacity='1';
        entry.target.style.transform='translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: .1, rootMargin: '0px 0px -50px 0px' });
  els.forEach(el=>{ el.style.opacity='0'; el.style.transform='translateY(20px)'; el.style.transition='opacity .5s ease, transform .5s ease'; observer.observe(el); });
})();
