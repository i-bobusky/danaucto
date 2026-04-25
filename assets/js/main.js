/* =========================================================
   Účetnictví Votýpková — main.js
   Vanilla JS, no dependencies.
   ========================================================= */

(function () {
  "use strict";

  /* ---------- Footer year ---------- */
  var yearEl = document.getElementById("year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  /* ---------- Mobile nav toggle ---------- */
  var toggle = document.querySelector(".nav-toggle");
  var nav = document.getElementById("primary-nav");

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var isOpen = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      toggle.setAttribute("aria-label", isOpen ? "Zavřít menu" : "Otevřít menu");
    });

    // Close mobile nav when clicking a link
    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        if (nav.classList.contains("open")) {
          nav.classList.remove("open");
          toggle.setAttribute("aria-expanded", "false");
          toggle.setAttribute("aria-label", "Otevřít menu");
        }
      });
    });

    // Close on Escape
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && nav.classList.contains("open")) {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.focus();
      }
    });
  }

  /* ---------- Smooth scroll with header offset ----------
     (browsers handle CSS scroll-behavior, but sticky header offset
      requires JS for accurate positioning) */
  function smoothScrollTo(target) {
    if (!target) return;
    var header = document.querySelector(".site-header");
    var offset = header ? header.offsetHeight + 8 : 0;
    var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
    window.scrollTo({ top: top, behavior: "smooth" });
  }

  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener("click", function (e) {
      var href = a.getAttribute("href");
      if (!href || href === "#" || href.length < 2) return;
      var target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        smoothScrollTo(target);
        // Update URL without jumping
        if (history.pushState) history.pushState(null, "", href);
      }
    });
  });

  /* ---------- Form status from query string ---------- */
  var statusEl = document.getElementById("form-status");
  var params = new URLSearchParams(window.location.search);

  function showStatus(type, message) {
    if (!statusEl) return;
    statusEl.classList.remove("success", "error");
    statusEl.classList.add("show", type);
    statusEl.textContent = message;
  }

  if (params.has("sent") && params.get("sent") === "1") {
    showStatus(
      "success",
      "Děkujeme, vaše zpráva byla odeslána. Ozveme se vám co nejdříve."
    );
    // Scroll to contact section
    var kontakt = document.getElementById("kontakt");
    if (kontakt) {
      setTimeout(function () { smoothScrollTo(kontakt); }, 200);
    }
  } else if (params.has("error") && params.get("error") === "1") {
    showStatus(
      "error",
      "Zprávu se nepodařilo odeslat. Zkuste to prosím znovu nebo zavolejte na 608 116 058."
    );
    var kontakt2 = document.getElementById("kontakt");
    if (kontakt2) {
      setTimeout(function () { smoothScrollTo(kontakt2); }, 200);
    }
  }

  /* ---------- Basic client-side form validation ---------- */
  var form = document.querySelector(".contact-form");
  if (form) {
    form.addEventListener("submit", function (e) {
      var name = form.querySelector("#name");
      var email = form.querySelector("#email");
      var message = form.querySelector("#message");
      var gdpr = form.querySelector("#gdpr");

      var problems = [];
      [name, email, message].forEach(function (el) {
        if (el && !el.value.trim()) {
          problems.push(el);
        }
      });

      if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        problems.push(email);
      }

      if (gdpr && !gdpr.checked) {
        problems.push(gdpr);
      }

      if (problems.length > 0) {
        e.preventDefault();
        showStatus("error", "Vyplňte prosím všechna povinná pole a potvrďte souhlas se zpracováním údajů.");
        problems[0].focus();
      }
    });
  }

})();
