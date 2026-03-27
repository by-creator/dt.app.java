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
