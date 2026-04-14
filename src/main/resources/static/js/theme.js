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

/* Windowed pagination (max 10 page buttons visible at a time) */
function initWindowedPagination() {
  var WINDOW_SIZE = 10;
  var NAV_TEXTS = ['\u00AB\u00AB', '\u2039', '\u203A', '\u00BB\u00BB', '<<', '>>'];

  document.querySelectorAll('.pagination-controls').forEach(function (controls) {
    if (controls.dataset.windowedInit) return;
    controls.dataset.windowedInit = '1';

    var allBtns = Array.from(controls.querySelectorAll('.page-btn'));
    var pageBtns = allBtns.filter(function (btn) {
      return NAV_TEXTS.indexOf(btn.textContent.trim()) === -1;
    });

    if (pageBtns.length <= WINDOW_SIZE) return;

    var activeBtn = pageBtns.find(function (btn) { return btn.classList.contains('active'); });
    var activeIndex = activeBtn ? pageBtns.indexOf(activeBtn) : 0;
    var currentWindow = Math.floor(activeIndex / WINDOW_SIZE) * WINDOW_SIZE;

    pageBtns.forEach(function (btn) { btn.style.display = 'none'; });

    var prevBtn = document.createElement('a');
    prevBtn.href = '#';
    prevBtn.className = 'page-btn page-batch-btn';
    prevBtn.textContent = '<<';
    prevBtn.title = 'Groupe precedent';
    prevBtn.setAttribute('aria-label', 'Pages precedentes');

    var nextBtn = document.createElement('a');
    nextBtn.href = '#';
    nextBtn.className = 'page-btn page-batch-btn';
    nextBtn.textContent = '>>';
    nextBtn.title = 'Groupe suivant';
    nextBtn.setAttribute('aria-label', 'Pages suivantes');

    var firstPage = pageBtns[0];
    var lastPage = pageBtns[pageBtns.length - 1];
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

function normalizeFilterText(value) {
  return (value || '')
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/_/g, ' ')
    .replace(/n[°º]/g, 'numero ')
    .replace(/\bndeg\b/g, 'numero')
    .replace(/\bno\b/g, 'numero')
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizeHeaderLabel(value) {
  return normalizeFilterText(value).replace(/\s+/g, ' ');
}

function resolveHeaderIndex(label, headerCells) {
  var normalizedLabel = normalizeHeaderLabel(label);
  if (!normalizedLabel) return -1;

  for (var i = 0; i < headerCells.length; i++) {
    var normalizedHeader = normalizeHeaderLabel(headerCells[i].textContent);
    if (normalizedHeader === normalizedLabel) return i;
    if (normalizedHeader.indexOf(normalizedLabel) !== -1 || normalizedLabel.indexOf(normalizedHeader) !== -1) return i;
  }

  return -1;
}

function parseTableDate(value) {
  var text = (value || '').toString().trim();
  if (!text) return null;

  var frMatch = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/);
  if (frMatch) {
    return new Date(
      Number(frMatch[3]),
      Number(frMatch[2]) - 1,
      Number(frMatch[1]),
      Number(frMatch[4] || 0),
      Number(frMatch[5] || 0),
      Number(frMatch[6] || 0)
    );
  }

  var isoMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (isoMatch) {
    return new Date(Number(isoMatch[1]), Number(isoMatch[2]) - 1, Number(isoMatch[3]));
  }

  return null;
}

function formatDateInputValue(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
  var year = String(date.getFullYear());
  var month = String(date.getMonth() + 1).padStart(2, '0');
  var day = String(date.getDate()).padStart(2, '0');
  return year + '-' + month + '-' + day;
}

function hideLegacyTableSearch(table) {
  var scope = table.closest('.page-section-card') || table.closest('section') || table.parentElement;
  if (!scope) return;

  scope.querySelectorAll('.table-search-input').forEach(function (input) {
    if (input.dataset.filterManaged === '1') return;
    var container = input.closest('form') || input.parentElement;
    if (container) container.classList.add('table-search-replaced');
  });
}

function collectDataRows(table) {
  var bodyRows = Array.from(table.querySelectorAll('tbody tr'));
  var headerCount = table.querySelectorAll('thead th').length;
  return bodyRows.filter(function (row) {
    return headerCount > 0 && row.children.length === headerCount;
  });
}

function detectColumnType(values) {
  var nonEmpty = values.filter(function (value) { return value !== ''; });
  if (!nonEmpty.length) return 'text';

  if (nonEmpty.every(function (value) { return !!parseTableDate(value); })) return 'date';

  var distinct = Array.from(new Set(nonEmpty));
  if (distinct.length > 1 && distinct.length <= 10) return 'select';

  return 'text';
}

function isFilterableColumn(headerText, columnCells) {
  var label = normalizeHeaderLabel(headerText);
  if (!label || label === '#' || label === 'action' || label === 'actions') return false;

  var interactiveCells = columnCells.filter(function (cell) {
    return !!cell.querySelector('button, a, form, input, select, textarea');
  });

  return interactiveCells.length !== columnCells.length;
}

function createFilterControl(meta, onChange) {
  var wrapper = document.createElement('label');
  wrapper.className = 'table-filter-item';

  var caption = document.createElement('span');
  caption.className = 'table-filter-label';
  caption.textContent = meta.label;
  wrapper.appendChild(caption);

  if (meta.type === 'select') {
    var searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.className = 'table-filter-control';
    searchInput.placeholder = 'Saisir...';
    searchInput.dataset.filterManaged = '1';
    searchInput.addEventListener('input', function () {
      meta.currentValue = normalizeFilterText(searchInput.value);
      onChange();
    });
    wrapper.appendChild(searchInput);
    return wrapper;
  }

  if (meta.type === 'date') {
    var dateInput = document.createElement('input');
    dateInput.type = 'date';
    dateInput.className = 'table-filter-control';
    dateInput.min = meta.minDate;
    dateInput.max = meta.maxDate;
    dateInput.addEventListener('input', function () {
      meta.currentValue = dateInput.value || '';
      onChange();
    });

    wrapper.appendChild(dateInput);
    return wrapper;
  }

  var textInput = document.createElement('input');
  textInput.type = 'search';
  textInput.className = 'table-filter-control';
  textInput.placeholder = 'Filtrer...';
  textInput.dataset.filterManaged = '1';
  textInput.addEventListener('input', function () {
    meta.currentValue = normalizeFilterText(textInput.value);
    onChange();
  });
  wrapper.appendChild(textInput);
  return wrapper;
}

function createNoResultMessage(table) {
  var message = document.createElement('div');
  message.className = 'table-no-results';
  message.textContent = 'Aucune ligne ne correspond aux filtres.';

  var wrapper = table.closest('.table-wrapper');
  if (wrapper) {
    wrapper.insertAdjacentElement('afterend', message);
  } else {
    table.insertAdjacentElement('afterend', message);
  }

  return message;
}

function updateTableCount(table, visibleCount, totalCount) {
  var scope = table.closest('.page-section-card') || table.closest('section') || document;
  var toolbar = scope.querySelector('.table-toolbar');
  if (!toolbar) return;

  var countNode = toolbar.querySelector('.pagination-info');
  if (!countNode) return;

  countNode.textContent = visibleCount === totalCount
    ? totalCount + (totalCount > 1 ? ' lignes' : ' ligne')
    : visibleCount + ' / ' + totalCount + ' lignes';
}

function hideLegacyFilterActions(searchForm) {
  if (!searchForm) return;

  searchForm.querySelectorAll('button[type="submit"], a.btn-actualiser').forEach(function (node) {
    node.style.display = 'none';
  });
}

function initLegacyLiveFilters(table, searchForm, searchInput) {
  if (!searchForm || searchForm.dataset.liveFilterInit === '1') return;
  searchForm.dataset.liveFilterInit = '1';

  var headerCells = Array.from(table.querySelectorAll('thead th'));
  var rows = collectDataRows(table);
  if (!headerCells.length || !rows.length) return;

  var controls = Array.from(searchForm.querySelectorAll('.table-search-input'));
  if (!controls.length) return;

  hideLegacyFilterActions(searchForm);

  var metas = controls.map(function (control) {
    var field = control.closest('div');
    var labelNode = field ? field.querySelector('label') : null;
    var label = labelNode ? labelNode.textContent.trim() : (control.name || control.id || '');
    var normalizedLabel = normalizeHeaderLabel(label);
    var type = control.type === 'date' ? 'date' : (control.tagName === 'SELECT' ? 'select' : 'text');
    var currentValue = type === 'select'
      ? normalizeFilterText(control.value || '')
      : normalizeFilterText(control.value || '');

    return {
      control: control,
      index: /recherche|search/.test(normalizedLabel) ? -1 : resolveHeaderIndex(label, headerCells),
      type: type,
      currentValue: currentValue
    };
  });

  function applyLegacyFilters() {
    var visible = 0;

    rows.forEach(function (row) {
      var matches = metas.every(function (meta) {
        if (!meta.currentValue) return true;

        if (meta.index === -1) {
          return normalizeFilterText(row.textContent).indexOf(meta.currentValue) !== -1;
        }

        var cell = row.children[meta.index];
        var rawValue = cell ? cell.textContent.trim() : '';
        var normalizedValue = normalizeFilterText(rawValue);

        if (meta.type === 'date') {
          var parsedDate = parseTableDate(rawValue);
          if (!parsedDate) return false;
          return formatDateInputValue(parsedDate) === meta.currentValue;
        }

        return normalizedValue.indexOf(meta.currentValue) !== -1;
      });

      row.style.display = matches ? '' : 'none';
      if (matches) visible++;
    });

    updateTableCount(table, visible, rows.length);
  }

  controls.forEach(function (control, index) {
    var handler = function () {
      metas[index].currentValue = control.tagName === 'SELECT'
        ? normalizeFilterText(control.value || '')
        : normalizeFilterText(control.value || '');
      applyLegacyFilters();
    };

    control.addEventListener('input', handler);
    control.addEventListener('change', handler);

    control.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') e.preventDefault();
    });
  });

  searchForm.addEventListener('submit', function (e) {
    e.preventDefault();
    applyLegacyFilters();
  });

  applyLegacyFilters();
}

function applyTableFilters(table, rows, filters, emptyState) {
  var visible = 0;

  rows.forEach(function (row) {
    var matches = filters.every(function (filterMeta) {
      var rawValue = row.dataset['filterCol' + filterMeta.index] || '';
      var normalizedValue = row.dataset['filterNorm' + filterMeta.index] || '';

      if (filterMeta.type === 'select') {
        return !filterMeta.currentValue || normalizedValue.indexOf(filterMeta.currentValue) !== -1;
      }

      if (filterMeta.type === 'date') {
        var parsedDate = parseTableDate(rawValue);
        if (!parsedDate) return !filterMeta.currentValue;

        var currentValue = formatDateInputValue(parsedDate);
        return !filterMeta.currentValue || currentValue === filterMeta.currentValue;
      }

      return !filterMeta.currentValue || normalizedValue.indexOf(filterMeta.currentValue) !== -1;
    });

    row.style.display = matches ? '' : 'none';
    if (matches) visible++;
  });

  if (emptyState) emptyState.style.display = visible === 0 ? 'block' : 'none';
  updateTableCount(table, visible, rows.length);
}

function initColumnFilters(table) {
  if (table.dataset.columnFiltersInit === '1') return;
  if (table.dataset.disableColumnFilters === 'true') return;
  table.dataset.columnFiltersInit = '1';

  var card = table.closest('.page-section-card');
  var searchInput = card ? card.querySelector('.table-search-input') : null;
  var searchForm = searchInput ? searchInput.closest('form') : null;
  var legacyManagedInputs = searchForm ? searchForm.querySelectorAll('.table-search-input').length : 0;
  var hasInlineLegacyFiltering = searchForm && Array.from(searchForm.querySelectorAll('.table-search-input')).some(function (control) {
    return control.hasAttribute('oninput') || control.hasAttribute('onchange');
  });

  // Preserve pages that already define a full table-filter toolbar so they all
  // keep the same shared appearance as the validation screen.
  if (legacyManagedInputs > 1) {
    if (!hasInlineLegacyFiltering) {
      initLegacyLiveFilters(table, searchForm, searchInput);
    }
    return;
  }

  if (legacyManagedInputs === 1 && searchForm && !hasInlineLegacyFiltering) {
    initLegacyLiveFilters(table, searchForm, searchInput);
    return;
  }

  var headerCells = Array.from(table.querySelectorAll('thead th'));
  if (!headerCells.length) return;

  var rows = collectDataRows(table);
  if (!rows.length) return;

  hideLegacyTableSearch(table);

  var filters = [];
  headerCells.forEach(function (headerCell, index) {
    var cells = rows.map(function (row) { return row.children[index]; }).filter(Boolean);
    if (!cells.length || !isFilterableColumn(headerCell.textContent, cells)) return;

    var values = cells.map(function (cell) { return cell.textContent.trim(); });
    var type = detectColumnType(values);
    var meta = {
      index: index,
      label: headerCell.textContent.trim(),
      type: type,
      currentValue: ''
    };

    if (type === 'select') {
      meta.options = Array.from(new Set(values.filter(function (value) { return value !== ''; }))).sort();
    }

    if (type === 'date') {
      var parsedDates = values.map(parseTableDate).filter(Boolean).sort(function (a, b) {
        return a.getTime() - b.getTime();
      });
      meta.minDate = parsedDates.length ? formatDateInputValue(parsedDates[0]) : '';
      meta.maxDate = parsedDates.length ? formatDateInputValue(parsedDates[parsedDates.length - 1]) : '';
    }

    rows.forEach(function (row) {
      var cell = row.children[index];
      var rawValue = cell ? cell.textContent.trim() : '';
      row.dataset['filterCol' + index] = rawValue;
      row.dataset['filterNorm' + index] = normalizeFilterText(rawValue);
    });

    filters.push(meta);
  });

  if (!filters.length) return;

  var panel = document.createElement('div');
  panel.className = 'table-filter-panel';

  var header = document.createElement('div');
  header.className = 'table-filter-panel-header';
  header.innerHTML = '<span>Filtres par colonnes</span>';

  var resetButton = document.createElement('button');
  resetButton.type = 'button';
  resetButton.className = 'table-filter-reset';
  resetButton.textContent = 'Reinitialiser';
  header.appendChild(resetButton);
  panel.appendChild(header);

  var body = document.createElement('div');
  body.className = 'table-filter-grid';
  panel.appendChild(body);

  var emptyState = createNoResultMessage(table);

  // Server-side: onChange is a no-op (meta values are kept up-to-date by
  // createFilterControl; submission is triggered explicitly by Enter key).
  var rerender = searchForm
    ? function () { /* submit on Enter only — see keydown listener below */ }
    : function () { applyTableFilters(table, rows, filters, emptyState); };

  filters.forEach(function (meta) {
    body.appendChild(createFilterControl(meta, rerender));
  });

  if (searchForm) {
    // Submit the server search when the user presses Enter inside any filter input
    panel.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      var val = '';
      for (var i = 0; i < filters.length; i++) {
        var v = filters[i].currentValue || '';
        if (v) { val = v; break; }
      }
      searchInput.value = val;
      searchForm.submit();
    });

    // Update placeholders so the user knows to press Enter
    panel.querySelectorAll('input').forEach(function (input) {
      if (!input.dataset.filterManaged) return;
      input.placeholder = 'Filtrer… (Entrée)';
    });

    // Pre-fill the first text filter if a search value is already in the URL
    var urlSearch = new URLSearchParams(window.location.search).get(searchInput.name || 'search') || '';
    if (urlSearch) {
      var firstInput = panel.querySelector('input[data-filter-managed="1"]');
      if (firstInput) {
        firstInput.value = urlSearch;
        var firstMeta = filters.find(function (m) { return m.type !== 'date'; });
        if (firstMeta) firstMeta.currentValue = normalizeFilterText(urlSearch);
      }
    }
  }

  resetButton.addEventListener('click', function () {
    panel.querySelectorAll('input, select').forEach(function (control) {
      control.value = '';
    });
    filters.forEach(function (meta) {
      meta.currentValue = '';
    });
    if (searchForm) {
      searchInput.value = '';
      searchForm.submit();
    } else {
      applyTableFilters(table, rows, filters, emptyState);
    }
  });

  var insertionTarget = table.closest('.table-wrapper') || table;
  insertionTarget.insertAdjacentElement('beforebegin', panel);
  if (!searchForm) {
    applyTableFilters(table, rows, filters, emptyState);
  }
}

function initSmartTableFilters() {
  document.querySelectorAll('table').forEach(function (table) {
    if (table.dataset.tableTheme === 'off') return;
    if (!table.classList.contains('data-table') && table.querySelector('thead th') && table.querySelector('tbody')) {
      table.classList.add('data-table');
    }
    if (table.classList.contains('data-table')) {
      initColumnFilters(table);
    }
  });
}

/* ── Mobile sidebar ────────────────────────────────────────────────────── */
function openSidebar() {
  var s = document.getElementById('mobileSidebar') || document.querySelector('.sidebar');
  var o = document.getElementById('sidebarOverlay');
  if (s) s.classList.add('open');
  if (o) o.classList.add('open');
}
function closeSidebar() {
  var s = document.getElementById('mobileSidebar') || document.querySelector('.sidebar');
  var o = document.getElementById('sidebarOverlay');
  if (s) s.classList.remove('open');
  if (o) o.classList.remove('open');
}

window.addEventListener('DOMContentLoaded', function () {
  initWindowedPagination();
  initSmartTableFilters();

  /* ── Auto-inject sidebar overlay + hamburger (all post-login pages) ── */
  var appLayout = document.querySelector('.app-layout');
  if (appLayout) {
    var sidebar = appLayout.querySelector('.sidebar');
    if (sidebar && !sidebar.id) sidebar.id = 'mobileSidebar';

    if (!document.getElementById('sidebarOverlay')) {
      var overlay = document.createElement('div');
      overlay.id = 'sidebarOverlay';
      overlay.className = 'sidebar-overlay';
      overlay.setAttribute('onclick', 'closeSidebar()');
      appLayout.parentNode.insertBefore(overlay, appLayout);
    }

    var main = appLayout.querySelector('.main-content');
    if (main && !main.querySelector('.mobile-topbar')) {
      var topbar = document.createElement('div');
      topbar.className = 'mobile-topbar';
      topbar.innerHTML =
        '<button class="hamburger-btn" onclick="openSidebar()" aria-label="Menu">' +
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>' +
        '</svg></button>';
      main.insertBefore(topbar, main.firstChild);
    }
  }

  /* ── SweetAlert auto-trigger from hidden flash spans ─────────────────── */
  if (typeof Swal !== 'undefined') {
    var flashSuccess = document.getElementById('_flashSuccess');
    var flashError   = document.getElementById('_flashError');
    var flashWarn    = document.getElementById('_flashWarning');
    var msg = flashSuccess ? (flashSuccess.textContent || flashSuccess.dataset.msg || '').trim() : '';
    var err = flashError   ? (flashError.textContent   || flashError.dataset.msg   || '').trim() : '';
    var warn = flashWarn   ? (flashWarn.textContent    || flashWarn.dataset.msg    || '').trim() : '';
    if (msg) {
      Swal.fire({ icon: 'success', title: 'Succès', text: msg, confirmButtonColor: '#3367bf', confirmButtonText: 'OK' });
    } else if (err) {
      Swal.fire({ icon: 'error', title: 'Erreur', text: err, confirmButtonColor: '#ef4444', confirmButtonText: 'OK' });
    } else if (warn) {
      Swal.fire({ icon: 'warning', title: 'Attention', text: warn, confirmButtonColor: '#f59e0b', confirmButtonText: 'Compris' });
    }
  }

  var observer = new MutationObserver(function (mutations) {
    var shouldRefresh = mutations.some(function (mutation) {
      return Array.from(mutation.addedNodes).some(function (node) {
        return node.nodeType === 1 && (
          (node.matches && node.matches('table')) ||
          (node.querySelector && node.querySelector('table'))
        );
      });
    });

    if (shouldRefresh) initSmartTableFilters();
  });

  observer.observe(document.body, { childList: true, subtree: true });
});
