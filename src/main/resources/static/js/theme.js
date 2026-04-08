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
    .trim();
}

function normalizeHeaderLabel(value) {
  return normalizeFilterText(value).replace(/\s+/g, ' ');
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
  if (nonEmpty.every(function (value) { return /^-?\d+(?:[.,]\d+)?$/.test(value.replace(/\s/g, '')); })) return 'number';

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

  if (meta.type === 'number') {
    var minInput = document.createElement('input');
    minInput.type = 'number';
    minInput.className = 'table-filter-control';
    minInput.placeholder = 'Min';
    minInput.addEventListener('input', function () {
      meta.minValue = minInput.value !== '' ? Number(minInput.value) : null;
      onChange();
    });

    var maxInput = document.createElement('input');
    maxInput.type = 'number';
    maxInput.className = 'table-filter-control';
    maxInput.placeholder = 'Max';
    maxInput.addEventListener('input', function () {
      meta.maxValue = maxInput.value !== '' ? Number(maxInput.value) : null;
      onChange();
    });

    wrapper.appendChild(minInput);
    wrapper.appendChild(maxInput);
    return wrapper;
  }

  if (meta.type === 'date') {
    var fromInput = document.createElement('input');
    fromInput.type = 'date';
    fromInput.className = 'table-filter-control';
    fromInput.min = meta.minDate;
    fromInput.max = meta.maxDate;
    fromInput.addEventListener('input', function () {
      meta.fromValue = fromInput.value || '';
      onChange();
    });

    var toInput = document.createElement('input');
    toInput.type = 'date';
    toInput.className = 'table-filter-control';
    toInput.min = meta.minDate;
    toInput.max = meta.maxDate;
    toInput.addEventListener('input', function () {
      meta.toValue = toInput.value || '';
      onChange();
    });

    wrapper.appendChild(fromInput);
    wrapper.appendChild(toInput);
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

function applyTableFilters(table, rows, filters, emptyState) {
  var visible = 0;

  rows.forEach(function (row) {
    var matches = filters.every(function (filterMeta) {
      var rawValue = row.dataset['filterCol' + filterMeta.index] || '';
      var normalizedValue = row.dataset['filterNorm' + filterMeta.index] || '';

      if (filterMeta.type === 'select') {
        return !filterMeta.currentValue || normalizedValue.indexOf(filterMeta.currentValue) !== -1;
      }

      if (filterMeta.type === 'number') {
        var parsedNumber = Number(rawValue.replace(',', '.'));
        if (Number.isNaN(parsedNumber)) return filterMeta.minValue === null && filterMeta.maxValue === null;
        if (filterMeta.minValue !== null && parsedNumber < filterMeta.minValue) return false;
        if (filterMeta.maxValue !== null && parsedNumber > filterMeta.maxValue) return false;
        return true;
      }

      if (filterMeta.type === 'date') {
        var parsedDate = parseTableDate(rawValue);
        if (!parsedDate) return !filterMeta.fromValue && !filterMeta.toValue;

        var currentValue = formatDateInputValue(parsedDate);
        if (filterMeta.fromValue && currentValue < filterMeta.fromValue) return false;
        if (filterMeta.toValue && currentValue > filterMeta.toValue) return false;
        return true;
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
      currentValue: '',
      minValue: null,
      maxValue: null,
      fromValue: '',
      toValue: ''
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
  var rerender = function () {
    applyTableFilters(table, rows, filters, emptyState);
  };

  filters.forEach(function (meta) {
    body.appendChild(createFilterControl(meta, rerender));
  });

  resetButton.addEventListener('click', function () {
    panel.querySelectorAll('input, select').forEach(function (control) {
      control.value = '';
    });
    filters.forEach(function (meta) {
      meta.currentValue = '';
      meta.minValue = null;
      meta.maxValue = null;
      meta.fromValue = '';
      meta.toValue = '';
    });
    rerender();
  });

  var insertionTarget = table.closest('.table-wrapper') || table;
  insertionTarget.insertAdjacentElement('beforebegin', panel);
  rerender();
}

function initSmartTableFilters() {
  document.querySelectorAll('table.data-table').forEach(initColumnFilters);
}

window.addEventListener('DOMContentLoaded', function () {
  initWindowedPagination();
  initSmartTableFilters();

  var observer = new MutationObserver(function (mutations) {
    var shouldRefresh = mutations.some(function (mutation) {
      return Array.from(mutation.addedNodes).some(function (node) {
        return node.nodeType === 1 && (
          (node.matches && node.matches('table.data-table')) ||
          (node.querySelector && node.querySelector('table.data-table'))
        );
      });
    });

    if (shouldRefresh) initSmartTableFilters();
  });

  observer.observe(document.body, { childList: true, subtree: true });
});
