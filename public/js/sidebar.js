(function () {
  var root = document.documentElement;
  var sidebar = document.getElementById('sidebar');
  var overlay = document.querySelector('[data-sidebar-close]');
  var filter = document.getElementById('sidebar-filter');
  var empty = document.querySelector('.nav-empty');
  var mq = window.matchMedia('(max-width: 1100px)');
  var GROUP_KEY = 'el.sidebar.groups';
  var COLLAPSE_KEY = 'el.sidebar.collapsed';

  function isMobile() {
    return mq.matches;
  }

  function setCollapsed(on) {
    if (isMobile()) return;
    root.classList.toggle('sidebar-collapsed', on);
    try { localStorage.setItem(COLLAPSE_KEY, on ? '1' : '0'); } catch (e) {}
    document.querySelectorAll('[data-sidebar-collapse]').forEach(function (btn) {
      btn.setAttribute('title', on ? 'Expandir menu (Ctrl+B)' : 'Recolher menu (Ctrl+B)');
    });
  }

  function openMobile() {
    root.classList.add('sidebar-open');
    if (overlay) overlay.hidden = false;
  }

  function closeMobile() {
    root.classList.remove('sidebar-open');
    if (overlay) overlay.hidden = true;
  }

  document.querySelectorAll('[data-sidebar-collapse]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (isMobile()) {
        closeMobile();
        return;
      }
      setCollapsed(!root.classList.contains('sidebar-collapsed'));
    });
  });

  document.querySelectorAll('[data-sidebar-open]').forEach(function (btn) {
    btn.addEventListener('click', openMobile);
  });

  if (overlay) overlay.addEventListener('click', closeMobile);

  document.querySelectorAll('[data-group-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.closest('.nav-group');
      if (!group) return;
      group.classList.toggle('is-closed');
      persistGroups();
    });
  });

  function persistGroups() {
    var state = {};
    document.querySelectorAll('.nav-group').forEach(function (g) {
      state[g.getAttribute('data-group')] = g.classList.contains('is-closed');
    });
    try { localStorage.setItem(GROUP_KEY, JSON.stringify(state)); } catch (e) {}
  }

  try {
    var saved = JSON.parse(localStorage.getItem(GROUP_KEY) || '{}');
    Object.keys(saved).forEach(function (id) {
      if (!saved[id]) return;
      var g = document.querySelector('.nav-group[data-group="' + id + '"]');
      if (g && !g.querySelector('.nav-link.is-active')) g.classList.add('is-closed');
    });
  } catch (e) {}

  if (filter) {
    filter.addEventListener('input', function () {
      var q = filter.value.trim().toLowerCase();
      var visible = 0;
      document.querySelectorAll('[data-nav-item]').forEach(function (link) {
        var hit = !q || (link.getAttribute('data-label') || '').toLowerCase().indexOf(q) !== -1;
        link.hidden = !hit;
        if (hit) visible++;
      });
      document.querySelectorAll('.nav-group').forEach(function (g) {
        var any = Array.prototype.some.call(g.querySelectorAll('[data-nav-item]'), function (l) { return !l.hidden; });
        g.hidden = !any;
        if (q && any) g.classList.remove('is-closed');
      });
      if (empty) empty.hidden = visible !== 0;
    });
  }

  document.addEventListener('keydown', function (e) {
    var meta = e.ctrlKey || e.metaKey;
    if (meta && e.key.toLowerCase() === 'b') {
      e.preventDefault();
      if (isMobile()) {
        root.classList.contains('sidebar-open') ? closeMobile() : openMobile();
      } else {
        setCollapsed(!root.classList.contains('sidebar-collapsed'));
      }
    }
    if (meta && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      if (isMobile()) openMobile();
      if (root.classList.contains('sidebar-collapsed')) setCollapsed(false);
      if (filter) filter.focus();
    }
    if (e.key === 'Escape') closeMobile();
  });

  mq.addEventListener('change', function () {
    if (isMobile()) {
      root.classList.remove('sidebar-collapsed');
      closeMobile();
    } else {
      closeMobile();
      try {
        if (localStorage.getItem(COLLAPSE_KEY) === '1') setCollapsed(true);
      } catch (e) {}
    }
  });
})();
