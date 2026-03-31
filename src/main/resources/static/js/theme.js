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

/* Table search partout */
window.addEventListener('DOMContentLoaded', function () {
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
