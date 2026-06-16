/**
 * Waiting-area and patient-ticket call alerts (chime + flash).
 * Browsers require a user gesture before audio — use unlockAudio() first.
 */
(function (global) {
  'use strict';

  var STORAGE_SOUND = 'bhc_sound_enabled';
  var audioCtx = null;

  function getAudioContext() {
    if (audioCtx) return audioCtx;
    var AC = global.AudioContext || global.webkitAudioContext;
    if (!AC) return null;
    audioCtx = new AC();
    return audioCtx;
  }

  function isSoundEnabled() {
    try {
      return localStorage.getItem(STORAGE_SOUND) === '1';
    } catch (e) {
      return false;
    }
  }

  function setSoundEnabled(on) {
    try {
      localStorage.setItem(STORAGE_SOUND, on ? '1' : '0');
    } catch (e) {}
  }

  function unlockAudio() {
    var ctx = getAudioContext();
    if (!ctx) return Promise.resolve(false);
    setSoundEnabled(true);
    if (ctx.state === 'suspended') {
      return ctx.resume().then(function () { return true; }).catch(function () { return false; });
    }
    return Promise.resolve(true);
  }

  function tone(ctx, freq, start, duration, volume) {
    var o = ctx.createOscillator();
    var g = ctx.createGain();
    o.type = 'sine';
    o.frequency.value = freq;
    g.gain.value = 0.0001;
    o.connect(g);
    g.connect(ctx.destination);
    o.start(start);
    g.gain.exponentialRampToValueAtTime(volume, start + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, start + duration);
    o.stop(start + duration + 0.02);
  }

  function playChime() {
    if (!isSoundEnabled()) return Promise.resolve(false);
    var ctx = getAudioContext();
    if (!ctx) return Promise.resolve(false);
    return (ctx.state === 'suspended' ? ctx.resume() : Promise.resolve())
      .then(function () {
        try {
          var t = ctx.currentTime;
          tone(ctx, 880, t, 0.22, 0.14);
          tone(ctx, 1175, t + 0.28, 0.28, 0.12);
          return true;
        } catch (e) {
          return false;
        }
      })
      .catch(function () { return false; });
  }

  function flashElement(el, ms) {
    if (!el) return;
    ms = ms || 1400;
    var old = el.style.boxShadow;
    el.style.boxShadow = '0 0 0 4px rgba(47,107,255,0.28), 0 12px 22px rgba(15,23,42,0.12)';
    setTimeout(function () { el.style.boxShadow = old; }, ms);
  }

  function storageGet(key) {
    try { return localStorage.getItem(key); } catch (e) { return null; }
  }

  function storageSet(key, val) {
    try { localStorage.setItem(key, val); } catch (e) {}
  }

  /** Display page: chime when Now serving ticket number changes. */
  function watchDisplayServing(storageKey, currentTicket, flashEl) {
    var last = storageGet(storageKey);
    if (!currentTicket || currentTicket === last) return;
    storageSet(storageKey, currentTicket);
    flashElement(flashEl);
    playChime();
  }

  /** Ticket page: chime when this ticket becomes serving. */
  function watchTicketServing(ticketId, status, flashEl) {
    var key = 'bhc_ticket_status_' + ticketId;
    var last = storageGet(key);
    storageSet(key, status);
    if (status === 'serving' && last !== 'serving') {
      flashElement(flashEl);
      playChime();
    }
  }

  function wireEnableButton(btnId, bannerId) {
    var btn = document.getElementById(btnId);
    var banner = bannerId ? document.getElementById(bannerId) : null;
    if (!btn) return;
    btn.addEventListener('click', function () {
      unlockAudio().then(function (ok) {
        if (ok) playChime();
        if (banner) banner.style.display = 'none';
        btn.textContent = 'Sound on';
        btn.classList.add('ok');
        btn.disabled = true;
      });
    });
    if (isSoundEnabled() && banner) banner.style.display = 'none';
    if (isSoundEnabled() && btn) {
      btn.textContent = 'Sound on';
      btn.classList.add('ok');
      btn.disabled = true;
    }
  }

  global.BhcQueueAlert = {
    isSoundEnabled: isSoundEnabled,
    unlockAudio: unlockAudio,
    playChime: playChime,
    flashElement: flashElement,
    watchDisplayServing: watchDisplayServing,
    watchTicketServing: watchTicketServing,
    wireEnableButton: wireEnableButton,
  };
})(typeof window !== 'undefined' ? window : this);
