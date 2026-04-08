/* Apply theme before page renders to prevent flash */
(function () {
  var theme = localStorage.getItem('dt-theme') || 'system';
  var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  if (theme === 'dark' || (theme === 'system' && prefersDark)) {
    document.documentElement.setAttribute('data-theme', 'dark');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
})();

/* Called by the settings buttons */
function setTheme(choice) {
  localStorage.setItem('dt-theme', choice);
  var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  if (choice === 'dark' || (choice === 'system' && prefersDark)) {
    document.documentElement.setAttribute('data-theme', 'dark');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
  /* Update active button state */
  document.querySelectorAll('.theme-option').forEach(function (btn) {
    btn.classList.toggle('active', btn.dataset.theme === choice);
  });
}

/* React to OS theme changes when in system mode */
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
  if ((localStorage.getItem('dt-theme') || 'system') === 'system') {
    if (e.matches) {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }
});

/* ── Windowed pagination (max 10 page buttons visible at a time) ── */
function initWindowedPagination() {
  var WINDOW_SIZE = 10;
  var NAV_TEXTS   = ['\u00AB\u00AB', '\u2039', '\u203A', '\u00BB\u00BB', '««', '‹', '›', '»»'];

  document.querySelectorAll('.pagination-controls').forEach(function (controls) {
    // Skip if already processed
    if (controls.dataset.windowedInit) return;
    controls.dataset.windowedInit = '1';

    var allBtns  = Array.from(controls.querySelectorAll('.page-btn'));
    var pageBtns = allBtns.filter(function (btn) {
      return NAV_TEXTS.indexOf(btn.textContent.trim()) === -1;
    });

    if (pageBtns.length <= WINDOW_SIZE) return;

    // Determine initial window from active page
    var activeBtn    = pageBtns.find(function (btn) { return btn.classList.contains('active'); });
    var activeIndex  = activeBtn ? pageBtns.indexOf(activeBtn) : 0;
    var currentWindow = Math.floor(activeIndex / WINDOW_SIZE) * WINDOW_SIZE;

    // Hide all numbered buttons
    pageBtns.forEach(function (btn) { btn.style.display = 'none'; });

    // Create batch-prev and batch-next buttons
    var prevBtn = document.createElement('a');
    prevBtn.href = '#';
    prevBtn.className = 'page-btn page-batch-btn';
    prevBtn.textContent = '‹‹';
    prevBtn.title = 'Groupe précédent';

    var nextBtn = document.createElement('a');
    nextBtn.href = '#';
    nextBtn.className = 'page-btn page-batch-btn';
    nextBtn.textContent = '››';
    nextBtn.title = 'Groupe suivant';

    // Insert around the page-number buttons
    var firstPage = pageBtns[0];
    var lastPage  = pageBtns[pageBtns.length - 1];
    controls.insertBefore(prevBtn, firstPage);
    lastPage.insertAdjacentElement('afterend', nextBtn);

    function showWindow(start) {
      pageBtns.forEach(function (btn) { btn.style.display = 'none'; });
      var end = Math.min(start + WINDOW_SIZE, pageBtns.length);
      for (var i = start; i < end; i++) {
        pageBtns[i].style.display = '';
      }
      prevBtn.classList.toggle('disabled', start === 0);
      nextBtn.classList.toggle('disabled', start + WINDOW_SIZE >= pageBtns.length);
      currentWindow = start;
    }

    prevBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (currentWindow > 0) showWindow(currentWindow - WINDOW_SIZE);
    });

    nextBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (currentWindow + WINDOW_SIZE < pageBtns.length) showWindow(currentWindow + WINDOW_SIZE);
    });

    showWindow(currentWindow);
  });
}

/* Table search partout */
window.addEventListener('DOMContentLoaded', function () {
  initWindowedPagination();
  document.querySelectorAll('.table-toolbar').forEach(function (toolbar) {
    var title = toolbar.querySelector('.toolbar-title');
    if (title) {
      // si titre sans icône (avant), on reste en style avec ::before
    }

    if (toolbar.querySelector('.table-search-input')) {
      return; // déjà présente
    }

    var actions = toolbar.querySelector('.table-toolbar-actions');
    if (!actions) {
      actions = document.createElement('div');
      actions.className = 'table-toolbar-actions';
      toolbar.appendChild(actions);
    }

    var input = document.createElement('input');
    input.type = 'search';
    input.placeholder = 'Rechercher...';
    input.className = 'table-search-input';
    actions.appendChild(input);

    input.addEventListener('input', function (event) {
      var q = event.target.value.trim().toLowerCase();
      var wrapper = toolbar.closest('.page-section-card') || toolbar.closest('section') || document;
      var table = wrapper.querySelector('.table-wrapper .data-table');
      if (!table) return;
      table.querySelectorAll('tbody tr').forEach(function (row) {
        var text = row.textContent.trim().toLowerCase();
        row.style.display = q.length === 0 || text.indexOf(q) !== -1 ? '' : 'none';
      });
    });
  });
});
