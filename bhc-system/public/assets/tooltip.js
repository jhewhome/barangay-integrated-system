(function () {
  if (!document.body) return;

  var layer =
    document.getElementById('tooltip-layer') ||
    (function () {
      var el = document.createElement('div');
      el.id = 'tooltip-layer';
      document.body.appendChild(el);
      return el;
    })();

  var tipEl = null;
  var activeEl = null;
  var hideTimer = null;
  var pad = 8;
  var gap = 10;

  document.documentElement.classList.add('tooltips-js');

  function removeTip() {
    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
    if (tipEl) {
      tipEl.remove();
      tipEl = null;
    }
    activeEl = null;
  }

  function scheduleHide() {
    if (hideTimer) clearTimeout(hideTimer);
    hideTimer = setTimeout(removeTip, 120);
  }

  function cancelHide() {
    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
  }

  function measureTip() {
    tipEl.style.left = '-9999px';
    tipEl.style.top = '0';
    tipEl.style.visibility = 'hidden';
    tipEl.style.display = 'block';
    return {
      w: tipEl.offsetWidth,
      h: tipEl.offsetHeight,
    };
  }

  function placeTip() {
    if (!tipEl || !activeEl) return;

    var rect = activeEl.getBoundingClientRect();
    if (rect.width === 0 && rect.height === 0) {
      removeTip();
      return;
    }

    var size = measureTip();
    var tipH = size.h;
    var tipW = size.w;
    var top = rect.top - tipH - gap;
    var below = top < pad;

    if (below) {
      top = rect.bottom + gap;
    }
    if (top + tipH > window.innerHeight - pad) {
      top = Math.max(pad, rect.top - tipH - gap);
      below = false;
    }

    var left = rect.left + (rect.width - tipW) / 2;
    if (left < pad) left = pad;
    if (left + tipW > window.innerWidth - pad) {
      left = window.innerWidth - tipW - pad;
    }

    tipEl.style.top = Math.round(top) + 'px';
    tipEl.style.left = Math.round(left) + 'px';
    tipEl.style.visibility = 'visible';
    tipEl.setAttribute('data-placement', below ? 'below' : 'above');
  }

  function showTip(el) {
    var text = el.getAttribute('data-tip');
    if (!text) return;

    cancelHide();
    if (activeEl === el && tipEl) {
      placeTip();
      return;
    }

    removeTip();
    activeEl = el;

    tipEl = document.createElement('div');
    tipEl.className = 'tip-float';
    tipEl.setAttribute('role', 'tooltip');
    tipEl.textContent = text.trim();
    layer.appendChild(tipEl);

    requestAnimationFrame(function () {
      requestAnimationFrame(placeTip);
    });
  }

  function isScrollRelevant(target) {
    if (!activeEl || !target || target === document) return false;
    if (target === document.documentElement || target === document.body) return true;
    if (target === activeEl || target.contains(activeEl)) return true;
    var node = activeEl.parentElement;
    while (node && node !== document.body) {
      if (node === target) return true;
      node = node.parentElement;
    }
    return false;
  }

  function isSidebarScroll(target) {
    var sidebar = document.querySelector('.sidebar');
    if (!sidebar || !target || target === document) return false;
    return target === sidebar || sidebar.contains(target);
  }

  function bindTips() {
    document.body.addEventListener(
      'mouseover',
      function (e) {
        var el = e.target.closest ? e.target.closest('.has-tip[data-tip]') : null;
        if (!el) return;
        showTip(el);
      },
      true
    );

    document.body.addEventListener(
      'mouseout',
      function (e) {
        var el = e.target.closest ? e.target.closest('.has-tip[data-tip]') : null;
        if (!el) return;
        var related = e.relatedTarget;
        if (related && el.contains(related)) return;
        if (related && related.closest && related.closest('.has-tip[data-tip]') === el) {
          return;
        }
        scheduleHide();
      },
      true
    );

    document.body.addEventListener(
      'focusin',
      function (e) {
        var el = e.target.closest ? e.target.closest('.has-tip[data-tip]') : null;
        if (el) showTip(el);
      },
      true
    );

    document.body.addEventListener(
      'focusout',
      function (e) {
        var el = e.target.closest ? e.target.closest('.has-tip[data-tip]') : null;
        if (el) scheduleHide();
      },
      true
    );

    document.body.addEventListener(
      'touchstart',
      function (e) {
        var el = e.target.closest ? e.target.closest('.has-tip[data-tip]') : null;
        if (el) showTip(el);
      },
      { passive: true, capture: true }
    );

    document.addEventListener(
      'touchstart',
      function (e) {
        if (!tipEl) return;
        var el = e.target.closest ? e.target.closest('.has-tip[data-tip]') : null;
        if (!el || el !== activeEl) removeTip();
      },
      { passive: true, capture: true }
    );

    window.addEventListener(
      'scroll',
      function (e) {
        if (!tipEl) return;
        var target = e.target;
        if (isSidebarScroll(target) && activeEl && !activeEl.closest('.sidebar')) {
          removeTip();
          return;
        }
        if (isScrollRelevant(target)) {
          placeTip();
        }
      },
      true
    );

    window.addEventListener('resize', function () {
      if (tipEl) placeTip();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindTips);
  } else {
    bindTips();
  }
})();
