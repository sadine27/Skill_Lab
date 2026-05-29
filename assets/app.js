/* Smart Waste Aggregation System — shared client-side behaviour.
   Plain vanilla JS, no build step. Each feature is opt-in via data-attributes
   or element IDs, so a page only runs the bits it actually needs. */
(function () {
  "use strict";

  /* -----------------------------------------------------------------------
   * 1. Live stats on the landing page.
   *    Replaces the old hardcoded fake numbers with real counts fetched
   *    from api/stats.php. Looks for elements with [data-stat="<key>"].
   * --------------------------------------------------------------------- */
  function loadStats() {
    var targets = document.querySelectorAll("[data-stat]");
    if (!targets.length) return;

    var base = document.body.getAttribute("data-base") || ".";
    fetch(base + "/api/stats.php", { headers: { Accept: "application/json" } })
      .then(function (res) {
        if (!res.ok) throw new Error("HTTP " + res.status);
        return res.json();
      })
      .then(function (data) {
        targets.forEach(function (el) {
          var key = el.getAttribute("data-stat");
          if (Object.prototype.hasOwnProperty.call(data, key)) {
            countUp(el, Number(data[key]) || 0);
          }
        });
      })
      .catch(function () {
        // Backend not reachable (e.g. DB down): show a dash, never fake data.
        targets.forEach(function (el) { el.textContent = "—"; });
      });
  }

  // Small count-up animation so the real numbers feel alive.
  function countUp(el, target) {
    var start = 0;
    var steps = 24;
    var step = Math.max(1, Math.ceil(target / steps));
    var current = start;
    var timer = setInterval(function () {
      current += step;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      el.textContent = current;
    }, 20);
  }

  /* -----------------------------------------------------------------------
   * 2. Client-side validation for forms marked [data-validate].
   *    Server-side validation still runs — this is just a fast first pass.
   * --------------------------------------------------------------------- */
  function wireValidation() {
    document.querySelectorAll("form[data-validate]").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        var ok = true;
        clearErrors(form);

        form.querySelectorAll("[required]").forEach(function (field) {
          if (!String(field.value).trim()) {
            markError(field, "This field is required.");
            ok = false;
          }
        });

        var email = form.querySelector('input[type="email"]');
        if (email && email.value && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email.value)) {
          markError(email, "Enter a valid email address.");
          ok = false;
        }

        var pw = form.querySelector('input[name="password"]');
        if (pw && form.hasAttribute("data-min-password") && pw.value &&
            pw.value.length < Number(form.getAttribute("data-min-password"))) {
          markError(pw, "Password must be at least " +
            form.getAttribute("data-min-password") + " characters.");
          ok = false;
        }

        if (!ok) e.preventDefault();
      });
    });
  }

  function markError(field, msg) {
    field.classList.add("field-error");
    var note = document.createElement("p");
    note.className = "field-error-msg";
    note.textContent = msg;
    field.insertAdjacentElement("afterend", note);
  }
  function clearErrors(form) {
    form.querySelectorAll(".field-error").forEach(function (f) {
      f.classList.remove("field-error");
    });
    form.querySelectorAll(".field-error-msg").forEach(function (n) { n.remove(); });
  }

  /* -----------------------------------------------------------------------
   * 3. Confirmation prompts for state-changing actions.
   *    Any form with [data-confirm="message"] asks before submitting.
   * --------------------------------------------------------------------- */
  function wireConfirms() {
    document.querySelectorAll("[data-confirm]").forEach(function (form) {
      form.addEventListener("submit", function (e) {
        if (!window.confirm(form.getAttribute("data-confirm"))) {
          e.preventDefault();
        }
      });
    });
  }

  /* -----------------------------------------------------------------------
   * 4. Live table filtering. An <input data-filter="tableId"> filters the
   *    rows of the target table as you type.
   * --------------------------------------------------------------------- */
  function wireFilters() {
    document.querySelectorAll("[data-filter]").forEach(function (input) {
      var table = document.getElementById(input.getAttribute("data-filter"));
      if (!table) return;
      input.addEventListener("input", function () {
        var term = input.value.trim().toLowerCase();
        table.querySelectorAll("tbody tr").forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().indexOf(term) > -1 ? "" : "none";
        });
      });
    });
  }

  /* -----------------------------------------------------------------------
   * 5. Auto-dismiss success alerts after a few seconds.
   * --------------------------------------------------------------------- */
  function wireAlerts() {
    document.querySelectorAll(".alert.success").forEach(function (el) {
      setTimeout(function () {
        el.style.transition = "opacity .5s";
        el.style.opacity = "0";
        setTimeout(function () { el.remove(); }, 500);
      }, 4000);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    loadStats();
    wireValidation();
    wireConfirms();
    wireFilters();
    wireAlerts();
  });
})();
