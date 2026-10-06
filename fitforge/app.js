/* FITFORGE — modular motion: nav, reveal, counters, rings, bars, menu */
(function () {
  'use strict';

  /* Sticky nav shrink */
  var nav = document.getElementById('nav');
  function onScroll() {
    nav.classList.toggle('scrolled', window.scrollY > 24);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* Mobile menu */
  var burger = document.getElementById('burger');
  var menu = document.getElementById('mobileMenu');
  burger.addEventListener('click', function () {
    var open = menu.classList.toggle('open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  });
  menu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () {
      menu.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
    });
  });

  /* Animated number counters */
  function animateCount(el) {
    var target = parseInt(el.dataset.count, 10) || 0;
    var dur = 1400, start = null;
    function step(t) {
      if (!start) start = t;
      var p = Math.min((t - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* Progress rings: stroke-dashoffset from % (circumference ≈ 327 / 377) */
  function animateRing(el) {
    var pct = parseFloat(el.dataset.ring) || 0;
    var r = parseFloat(el.getAttribute('r')) || 52;
    var c = 2 * Math.PI * r;
    el.style.strokeDasharray = c;
    el.style.strokeDashoffset = c;
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.style.strokeDashoffset = c * (1 - pct / 100);
      });
    });
  }

  /* Bars + charts fire when visible */
  var animated = new WeakSet();
  function fireAnims(root) {
    root.querySelectorAll('.count').forEach(function (el) {
      if (!animated.has(el)) { animated.add(el); animateCount(el); }
    });
    root.querySelectorAll('[data-ring]').forEach(function (el) {
      if (!animated.has(el)) { animated.add(el); animateRing(el); }
    });
    root.querySelectorAll('[data-progress]').forEach(function (el) {
      if (!animated.has(el)) {
        animated.add(el);
        el.style.width = '0';
        requestAnimationFrame(function () {
          el.style.width = el.dataset.progress + '%';
        });
      }
    });
    root.querySelectorAll('.chart .col i, .spark i').forEach(function (el) {
      if (!animated.has(el)) {
        animated.add(el);
        var h = el.style.getPropertyValue('--h');
        el.style.height = '6%';
        requestAnimationFrame(function () {
          requestAnimationFrame(function () { el.style.height = h; });
        });
      }
    });
  }

  /* Scroll-triggered reveals */
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var revealEls = document.querySelectorAll('.reveal');
  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('in'); });
    fireAnims(document);
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('in');
          fireAnims(e.target.tagName === 'SECTION' || e.target.classList.contains('dash') ? e.target : e.target.closest('section') || document);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
    /* hero fires immediately */
    fireAnims(document.querySelector('.hero'));
  }

  /* Subtle hero parallax on floating chips (desktop, pointer only) */
  var hero = document.querySelector('.hero');
  var chips = document.querySelectorAll('.chip');
  if (window.matchMedia('(pointer: fine)').matches && !reduceMotion) {
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - 0.5;
      var y = (e.clientY - r.top) / r.height - 0.5;
      chips.forEach(function (c, i) {
        var f = (i + 1) * 10;
        c.style.translate = (-x * f) + 'px ' + (-y * f) + 'px';
      });
    });
  }

  /* Demo buttons: tiny toast so every CTA does something */
  var toast = document.createElement('div');
  toast.className = 'demo-toast';
  toast.setAttribute('role', 'status');
  toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#c8ff1e;color:#0c0e05;font-weight:700;padding:12px 24px;border-radius:12px;z-index:200;transition:transform .35s cubic-bezier(.2,.7,.2,1);box-shadow:0 12px 40px rgba(0,0,0,.5);font-size:.9rem;';
  document.body.appendChild(toast);
  var toastTimer;
  document.querySelectorAll('.wcard .btn, .tcard .btn, .phone .btn').forEach(function (b) {
    b.addEventListener('click', function () {
      toast.textContent = '🔥 Demo — this would open in the FITFORGE app!';
      toast.style.transform = 'translateX(-50%) translateY(0)';
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
        toast.style.transform = 'translateX(-50%) translateY(80px)';
      }, 2200);
    });
  });
})();
