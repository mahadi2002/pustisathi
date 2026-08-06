'use strict';

/* Flips the no-js/js class before anything else runs, so CSS can assume
   "no .js class" genuinely means JS never ran (used by .reveal's fallback). */
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');

/* Light/dark theme toggle. Runs first since it decides how the page looks;
   deferred script execution still means a brief flash of the wrong theme
   on first paint is possible — the CSP here has no 'unsafe-inline', so
   there's no earlier point a script could run instead. */
(function () {
  var STORAGE_KEY = 'ps_theme';
  var root = document.documentElement;
  var toggle = document.getElementById('theme-toggle');

  function systemPrefersDark() {
    return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
  }

  function storedTheme() {
    try {
      return localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      return null;
    }
  }

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
  }

  applyTheme(storedTheme() || (systemPrefersDark() ? 'dark' : 'light'));

  if (toggle) {
    toggle.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      try {
        localStorage.setItem(STORAGE_KEY, next);
      } catch (e) {
        // Private browsing or storage disabled — theme just won't persist across visits.
      }
    });
  }
})();

/* Mobile nav toggle — plain show/hide, no framework needed for one menu. */
(function () {
  var toggle = document.getElementById('nav-toggle');
  var menu = document.getElementById('nav-links-mobile');
  if (!toggle || !menu) {
    return;
  }

  toggle.addEventListener('click', function () {
    var isOpen = menu.classList.toggle('open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });
})();

/* BMI/BMR calculator — client-side only, nothing sent to the server. */
(function () {
  var form = document.getElementById('bmi-form');
  if (!form) {
    return;
  }

  var BMI_LABELS = [
    [18.5, 'Underweight'],
    [23, 'Normal'],
    [25, 'Overweight'],
    [Infinity, 'Obese'],
  ];

  function bmiLabel(bmi) {
    for (var i = 0; i < BMI_LABELS.length; i++) {
      if (bmi < BMI_LABELS[i][0]) {
        return BMI_LABELS[i][1];
      }
    }
    return 'Obese';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var height = parseFloat(document.getElementById('height').value);
    var weight = parseFloat(document.getElementById('weight').value);
    var age = parseInt(document.getElementById('age').value, 10);
    var sex = document.getElementById('sex').value;
    var activity = parseFloat(document.getElementById('activity').value);

    if (!height || !weight || !age) {
      return;
    }

    var heightM = height / 100;
    var bmi = weight / (heightM * heightM);

    // Mifflin-St Jeor
    var bmr = sex === 'male'
      ? 10 * weight + 6.25 * height - 5 * age + 5
      : 10 * weight + 6.25 * height - 5 * age - 161;

    var tdee = bmr * activity;

    document.getElementById('bmi-value').textContent = bmi.toFixed(1);
    document.getElementById('bmi-label').textContent = bmiLabel(bmi);
    document.getElementById('bmr-value').textContent = Math.round(tdee).toString();
    document.getElementById('bmi-result').hidden = false;
  });
})();

/* Scroll-reveal for anything marked .reveal — fades/slides in once, the
   first time it crosses into view. Content is fully visible without this;
   it's decoration, not information, so a missing IntersectionObserver
   (there isn't one this old) just means no animation, nothing hidden. */
(function () {
  var items = document.querySelectorAll('.reveal');
  if (!items.length) {
    return;
  }

  if (!('IntersectionObserver' in window)) {
    items.forEach(function (el) { el.classList.add('is-visible'); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

  items.forEach(function (el) { observer.observe(el); });
})();

/* Turns the server-rendered flash notice into an auto-dismissing toast
   instead of a static banner that sits at the top of the page forever. */
(function () {
  var alertEl = document.querySelector('main > .alert');
  if (!alertEl) {
    return;
  }

  var stack = document.createElement('div');
  stack.className = 'toast-stack';
  stack.setAttribute('role', 'status');
  stack.setAttribute('aria-live', 'polite');

  var closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'toast-close';
  closeBtn.setAttribute('aria-label', 'Dismiss');
  closeBtn.textContent = '×';

  alertEl.parentNode.removeChild(alertEl);
  alertEl.appendChild(closeBtn);
  stack.appendChild(alertEl);
  document.body.appendChild(stack);

  function dismiss() {
    alertEl.style.transition = 'opacity 200ms ease';
    alertEl.style.opacity = '0';
    setTimeout(function () { stack.remove(); }, 220);
  }

  closeBtn.addEventListener('click', dismiss);
  setTimeout(dismiss, 5000);
})();

/* Live food search — fetches JSON instead of a full page reload as the
   user types. The plain GET form still works with JS disabled; this only
   upgrades the experience when it's available. */
(function () {
  var input = document.getElementById('q');
  var form = document.getElementById('food-search-form');
  if (!input || !form) {
    return;
  }

  var resultsHost = document.getElementById('food-results');
  var statusHost = document.getElementById('food-search-status');
  var timer = null;

  function render(data) {
    if (!resultsHost) {
      return;
    }
    if (statusHost) {
      statusHost.textContent = '';
    }

    if (data.capped) {
      resultsHost.innerHTML = '<div class="alert alert-info">আজকের জন্য Free খোঁজার সীমা শেষ হয়ে গেছে। '
        + '<a href="/subscribe">Subscribe করুন</a> — Unlimited Search সহ পুরো Diet Plan পাবেন।</div>';
      return;
    }

    if (data.results.length === 0) {
      resultsHost.innerHTML = '<div class="alert alert-info">"' + escapeHtml(data.query) + '" এর সাথে মিলে এমন কোনো খাবার পাওয়া যায়নি।</div>';
      return;
    }

    var rows = data.results.map(function (f) {
      return '<tr><td>' + escapeHtml(f.name_bn) + ' <span class="text-muted">(' + escapeHtml(f.name_en) + ')</span></td>'
        + '<td>' + Number(f.per_100g_kcal).toFixed(1) + '</td>'
        + '<td>' + Number(f.per_100g_protein_g).toFixed(1) + '</td>'
        + '<td>' + Number(f.per_100g_carb_g).toFixed(1) + '</td>'
        + '<td>' + Number(f.per_100g_fat_g).toFixed(1) + '</td></tr>';
    }).join('');

    resultsHost.innerHTML = '<div class="compare-table-wrap"><table class="compare-table">'
      + '<thead><tr><th>খাবার</th><th>ক্যালরি (kcal)</th><th>Protein (g)</th><th>Carb (g)</th><th>Fat (g)</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table></div>'
      + '<p class="text-muted section-note">মান প্রতি ১০০ গ্রামে, আনুমানিক — যাচাইকৃত তথ্যভাণ্ডার আসার আগ পর্যন্ত রেফারেন্স হিসেবে ব্যবহার করুন।</p>';
  }

  function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = String(s);
    return div.innerHTML;
  }

  // Each fired search counts against the guest's daily cap server-side, so
  // this holds off until there's a real word, not just a first keystroke.
  var MIN_QUERY_LENGTH = 2;

  function search(query) {
    var url = '/foods?q=' + encodeURIComponent(query);
    window.history.replaceState(null, '', query ? url : '/foods');

    if (!query) {
      if (resultsHost) resultsHost.innerHTML = '';
      if (statusHost) statusHost.textContent = '';
      return;
    }

    if (query.length < MIN_QUERY_LENGTH) {
      if (statusHost) statusHost.textContent = 'আরও লিখুন...';
      return;
    }

    if (statusHost) statusHost.textContent = 'খোঁজা হচ্ছে...';

    fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(render)
      .catch(function () {
        if (statusHost) statusHost.textContent = '';
      });
  }

  input.addEventListener('input', function () {
    clearTimeout(timer);
    var query = input.value.trim();
    timer = setTimeout(function () { search(query); }, 300);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearTimeout(timer);
    search(input.value.trim());
  });
})();
