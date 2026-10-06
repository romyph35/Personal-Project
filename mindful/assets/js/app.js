/* Mindful app.js — no dependencies */
(function(){
  "use strict";
  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Nav scroll + burger
  var nav = document.getElementById("topnav");
  window.addEventListener("scroll", function(){
    if (nav) nav.classList.toggle("scrolled", window.scrollY > 8);
  }, {passive:true});
  var burger = document.getElementById("navBurger"), mobile = document.getElementById("navMobile");
  if (burger && mobile) {
    burger.addEventListener("click", function(){
      var isOpen = mobile.classList.contains("open");
      if (isOpen) { mobile.classList.remove("open"); mobile.setAttribute("hidden",""); burger.setAttribute("aria-expanded","false"); }
      else { mobile.classList.add("open"); mobile.removeAttribute("hidden"); burger.setAttribute("aria-expanded","true"); }
    });
    window.addEventListener("resize", function(){
      if (window.innerWidth > 960 && mobile.classList.contains("open")) {
        mobile.classList.remove("open"); mobile.setAttribute("hidden",""); burger.setAttribute("aria-expanded","false");
      }
    });
  }

  // Theme toggle
  var root = document.documentElement, tBtn = document.getElementById("themeToggle");
  try {
    var saved = localStorage.getItem("mindful-theme");
    if (saved === "dark" || saved === "light") root.setAttribute("data-theme", saved);
    if (tBtn) tBtn.textContent = root.getAttribute("data-theme") === "dark" ? "☀️" : "🌙";
  } catch(e){}
  if (tBtn) tBtn.addEventListener("click", function(){
    var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
    root.setAttribute("data-theme", next);
    try { localStorage.setItem("mindful-theme", next); } catch(e){}
    tBtn.textContent = next === "dark" ? "☀️" : "🌙";
  });

  // Reveal on scroll
  var reveals = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window && !reduceMotion && reveals.length) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } });
    }, {threshold:.12});
    reveals.forEach(function(el){ io.observe(el); });
  } else {
    reveals.forEach(function(el){ el.classList.add("in"); });
  }

  // Counters
  var counters = document.querySelectorAll("[data-count]");
  function animateCount(el){
    var target = parseFloat(el.getAttribute("data-count") || "0");
    if (reduceMotion) { el.textContent = target; return; }
    var start = 0, dur = 1200, t0 = null;
    function step(t){
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur);
      el.textContent = Math.round(target * (0.2 + 0.8 * p * (2 - p)));
      if (p < 1) requestAnimationFrame(step); else el.textContent = target;
    }
    requestAnimationFrame(step);
  }
  if ("IntersectionObserver" in window && counters.length) {
    var cio = new IntersectionObserver(function(es){
      es.forEach(function(en){ if (en.isIntersecting) { animateCount(en.target); cio.unobserve(en.target); } });
    }, {threshold:.4});
    counters.forEach(function(el){ cio.observe(el); });
  } else { counters.forEach(animateCount); }

  // Weekly bars animate
  document.querySelectorAll(".bars .bar").forEach(function(b){
    var h = b.getAttribute("data-h") || b.style.height;
    if (!reduceMotion && h) {
      b.style.height = "8px";
      setTimeout(function(){ b.style.height = h; }, 150);
    } else if (h) { b.style.height = h; }
  });

  // Mood selector
  document.querySelectorAll("[data-mood-group]").forEach(function(group){
    var btns = group.querySelectorAll(".mood-btn");
    var msg = document.querySelector(group.getAttribute("data-confirm") || "#moodConfirm");
    btns.forEach(function(btn){
      btn.addEventListener("click", function(){
        btns.forEach(function(b){ b.classList.remove("active"); b.setAttribute("aria-pressed","false"); });
        btn.classList.add("active"); btn.setAttribute("aria-pressed","true");
        var radio = btn.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        if (msg) {
          var label = btn.getAttribute("data-label") || "noted";
          msg.textContent = "You selected “" + label + "”. Your feelings are worth noticing. 💚";
          msg.classList.add("show");
        }
      });
    });
  });

  // Toast helper
  window.toast = function(text){
    var wrap = document.getElementById("toastWrap");
    if (!wrap) { alert(text); return; }
    var t = document.createElement("div");
    t.className = "toast"; t.textContent = text;
    wrap.appendChild(t);
    setTimeout(function(){ t.remove(); }, 3200);
  };

  // Breathing exercise
  var circle = document.getElementById("breatheCircle");
  var phaseEl = document.getElementById("breathePhase");
  var timerEl = document.getElementById("breatheTimer");
  var breatheTimer = null, breatheEnd = 0;
  function setPhase(txt, cls){
    if (phaseEl) phaseEl.textContent = txt;
    if (circle) { circle.classList.remove("inhale","exhale"); if (cls) circle.classList.add(cls); }
  }
  function breatheCycle(){
    if (reduceMotion) { setPhase("Breathe gently…", ""); return; }
    setPhase("Breathe in…", "inhale");
    setTimeout(function(){ setPhase("Hold…", "inhale"); }, 4000);
    setTimeout(function(){ setPhase("Breathe out…", "exhale"); }, 7000);
  }
  window.startBreathing = function(minutes){
    minutes = parseInt(minutes || "1", 10) || 1;
    if (breatheTimer) clearInterval(breatheTimer);
    breatheEnd = Date.now() + minutes * 60000;
    breatheCycle();
    var cyc = setInterval(breatheCycle, 11000);
    breatheTimer = setInterval(function(){
      var left = Math.max(0, breatheEnd - Date.now());
      var s = Math.ceil(left / 1000);
      if (timerEl) timerEl.textContent = Math.floor(s/60) + ":" + String(s%60).padStart(2,"0");
      if (left <= 0) {
        clearInterval(breatheTimer); clearInterval(cyc); breatheTimer = null;
        setPhase("Well done 🌿", ""); toast("Breathing session complete. Nice work.");
      }
    }, 500);
    toast("Breathing for " + minutes + " minute" + (minutes>1?"s":"") + ". Follow the circle.");
  };
  var stopBtn = document.getElementById("breatheStop");
  if (stopBtn) stopBtn.addEventListener("click", function(){
    if (breatheTimer) { clearInterval(breatheTimer); breatheTimer = null; }
    setPhase("Paused — press start when ready", "");
  });

  // Booking modal steps
  var modal = document.getElementById("bookModal");
  window.openBooking = function(therapistId, therapistName){
    if (!modal) return;
    modal.classList.add("open");
    var idField = document.getElementById("bookTherapistId");
    var nameEl = document.getElementById("bookTherapistName");
    if (idField) idField.value = therapistId;
    if (nameEl) nameEl.textContent = therapistName || "";
    goStep(1);
  };
  window.closeBooking = function(){ if (modal) modal.classList.remove("open"); };
  if (modal) modal.addEventListener("click", function(ev){ if (ev.target === modal) closeBooking(); });
  document.addEventListener("keydown", function(ev){ if (ev.key === "Escape") closeBooking(); });
  window.goStep = function(n){
    document.querySelectorAll(".book-step").forEach(function(s){ s.hidden = s.getAttribute("data-step") !== String(n); });
    document.querySelectorAll(".steps span").forEach(function(d, i){ d.classList.toggle("on", i < n); });
  };

  // Resource search / filter (client-side assist; server also filters)
  var search = document.getElementById("resSearch"), catSel = document.getElementById("resCat");
  function filterCards(){
    var q = (search ? search.value : "").toLowerCase();
    var c = catSel ? catSel.value : "";
    document.querySelectorAll("[data-res-card]").forEach(function(card){
      var hay = ((card.getAttribute("data-title")||"") + " " + (card.getAttribute("data-cat")||"")).toLowerCase();
      var okQ = !q || hay.indexOf(q) > -1;
      var okC = !c || card.getAttribute("data-cat") === c;
      card.style.display = (okQ && okC) ? "" : "none";
    });
  }
  if (search) search.addEventListener("input", filterCards);
  if (catSel) catSel.addEventListener("change", filterCards);

  // Bookmark via fetch (progressive enhancement; forms still work without JS)
  document.querySelectorAll("[data-bookmark-form]").forEach(function(form){
    form.addEventListener("submit", function(ev){
      if (!window.fetch) return; // let it post normally
      ev.preventDefault();
      var fd = new FormData(form);
      fetch(form.action, {method:"POST", body:fd, credentials:"same-origin"})
        .then(function(){ 
          var btn = form.querySelector("button");
          var on = form.querySelector('input[name="on"]');
          var marked = btn && btn.getAttribute("data-marked") === "1";
          if (btn) {
            btn.setAttribute("data-marked", marked ? "0" : "1");
            btn.textContent = marked ? "🔖 Save" : "✅ Saved";
          }
          toast(marked ? "Removed from your library." : "Saved to your library. 📖");
          if (on) on.value = marked ? "0" : "1";
        })
        .catch(function(){ form.submit(); });
    });
  });

  // Grounding interactive steps
  var gSteps = document.querySelectorAll("[data-ground-step]");
  gSteps.forEach(function(btn){
    btn.addEventListener("click", function(){
      btn.classList.toggle("done");
      var done = document.querySelectorAll("[data-ground-step].done").length;
      var note = document.getElementById("groundNote");
      if (note) note.textContent = done + " of " + gSteps.length + " grounding steps complete. You're doing great. 🌿";
      if (done === gSteps.length) toast("Grounding complete. Welcome back to this moment. 💚");
    });
  });
})();
