<?php
/**
 * cookie_consent.php
 * ══════════════════
 * Include this file in EVERY page (after session_start).
 * It:
 *   1. Reads the browser cookie "ff_cookie_consent"
 *   2. Exposes helper functions:
 *        cookieAllowed('analytics')
 *        cookieAllowed('preferences')
 *        cookieAllowed('marketing')
 *   3. Outputs the cookie banner HTML + JS (only when needed)
 *
 * Usage in any PHP page — paste just before </body>:
 *   <?php include 'cookie_consent.php'; ?>
 */

/* ── Parse saved consent from browser cookie ── */
$_COOKIE_PREFS = [];

if (isset($_COOKIE['ff_cookie_consent'])) {
    $decoded = json_decode(stripslashes($_COOKIE['ff_cookie_consent']), true);
    if (is_array($decoded)) {
        $_COOKIE_PREFS = $decoded;
    }
}

/**
 * Check if a specific cookie category is allowed.
 *
 * @param string $category  'analytics' | 'preferences' | 'marketing' | 'necessary'
 * @return bool
 */
function cookieAllowed(string $category): bool {
    global $_COOKIE_PREFS;

    // Necessary cookies are always allowed
    if ($category === 'necessary') return true;

    // No consent given yet → only necessary allowed
    if (empty($_COOKIE_PREFS)) return false;

    // "all" level → everything allowed
    if (isset($_COOKIE_PREFS['level']) && $_COOKIE_PREFS['level'] === 'all') return true;

    // Custom / necessary-only → check specific key
    return !empty($_COOKIE_PREFS[$category]);
}

$_CONSENT_GIVEN = !empty($_COOKIE_PREFS);
?>

<!-- ═══════════════════════════════════════════
     COOKIE CONSENT BANNER  (hidden if already accepted)
     ═══════════════════════════════════════════ -->
<style>
  #cookieBanner {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: #1a1a24;
    color: #fff;
    padding: 18px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    z-index: 99999;
    box-shadow: 0 -4px 24px rgba(0,0,0,.3);
    flex-wrap: wrap;
    transition: transform .4s ease;
  }
  #cookieBanner.hidden { transform: translateY(110%); }

  #cookieBanner .cookie-text {
    font-size: 13px;
    color: rgba(255,255,255,.82);
    line-height: 1.7;
    flex: 1;
    min-width: 260px;
  }
  #cookieBanner .cookie-text a {
    color: #d4af7a;
    text-decoration: underline;
    cursor: pointer;
  }
  #cookieBanner .cookie-text strong { color: #fff; }

  .cb-btns { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

  .cb-btn {
    border: none; border-radius: 50px;
    padding: 9px 22px; font-size: 13px;
    font-weight: 600; cursor: pointer;
    transition: background .2s, transform .15s, border-color .2s;
    white-space: nowrap;
  }
  .cb-btn-all  { background: #d4af7a; color: #1a1a24; }
  .cb-btn-all:hover  { background: #c9a060; transform: scale(1.03); }
  .cb-btn-nec  { background: transparent; color: rgba(255,255,255,.75); border: 1.5px solid rgba(255,255,255,.3); }
  .cb-btn-nec:hover  { border-color: #fff; color: #fff; }
  .cb-btn-set  { background: transparent; color: rgba(255,255,255,.5); border: 1.5px solid rgba(255,255,255,.15); font-size: 12px; }
  .cb-btn-set:hover  { border-color: rgba(255,255,255,.5); color: rgba(255,255,255,.8); }

  #reopenCookieBtn {
    position: fixed;
    bottom: 14px; right: 18px;
    background: #1a1a24;
    color: rgba(255,255,255,.6);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50px;
    padding: 6px 14px;
    font-size: 11px;
    cursor: pointer;
    z-index: 9998;
    display: none;
    transition: color .2s, border-color .2s;
  }
  #reopenCookieBtn.visible { display: block; }
  #reopenCookieBtn:hover { color: #fff; border-color: rgba(255,255,255,.5); }

  /* ── Cookie Settings Modal ── */
  #cookieModal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 100000;
    align-items: center;
    justify-content: center;
  }
  #cookieModal.open { display: flex; }

  .cm-box {
    background: #fff;
    border-radius: 18px;
    padding: 36px;
    width: 500px;
    max-width: 95vw;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
  }
  .cm-box h3 { font-size: 20px; font-weight: 700; margin-bottom: 6px; color: #1a1a24; }
  .cm-box > p { font-size: 13px; color: #888; line-height: 1.65; margin-bottom: 22px; }

  .cm-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid #f0f0f0;
  }
  .cm-row:last-of-type { border-bottom: none; }
  .cm-row .cm-info h6 { font-size: 14px; font-weight: 700; margin-bottom: 3px; color: #222; }
  .cm-row .cm-info p  { font-size: 12px; color: #aaa; margin: 0; line-height: 1.5; }

  .cm-toggle { position:relative; width:44px; height:24px; flex-shrink:0; }
  .cm-toggle input { opacity:0; width:0; height:0; position:absolute; }
  .cm-slider {
    position:absolute; inset:0;
    background:#ddd; border-radius:24px;
    cursor:pointer; transition:.3s;
  }
  .cm-slider:before {
    content:''; position:absolute;
    height:18px; width:18px;
    left:3px; bottom:3px;
    background:#fff; border-radius:50%;
    transition:.3s;
  }
  .cm-toggle input:checked + .cm-slider { background: #d4af7a; }
  .cm-toggle input:checked + .cm-slider:before { transform: translateX(20px); }
  .cm-toggle input:disabled + .cm-slider { opacity:.6; cursor:default; }

  .cm-footer { display:flex; gap:12px; margin-top:24px; justify-content:flex-end; }
  .cm-save {
    background: #d4af7a; color: #1a1a24; border: none;
    border-radius: 50px; padding: 10px 28px;
    font-size: 13px; font-weight: 700; cursor: pointer;
    transition: background .2s;
  }
  .cm-save:hover { background: #c9a060; }
  .cm-cancel {
    background: transparent; color: #888;
    border: 1.5px solid #ddd; border-radius: 50px;
    padding: 10px 20px; font-size: 13px; cursor: pointer;
    transition: border-color .2s, color .2s;
  }
  .cm-cancel:hover { border-color: #888; color: #333; }

  /* ── Cookie info badges on banner ── */
  .cookie-badge {
    display: inline-block;
    font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 50px;
    text-transform: uppercase; letter-spacing: .5px;
    margin-left: 6px;
  }
  .cookie-badge-active   { background: #d4edda; color: #1a6b3c; }
  .cookie-badge-inactive { background: #f8d7da; color: #721c24; }

  @media(max-width:600px) {
    #cookieBanner { padding: 14px 16px; }
    .cb-btns { width: 100%; justify-content: flex-end; }
  }
</style>

<!-- Banner HTML -->
<div id="cookieBanner">
  <div class="cookie-text">
    <strong>🍪 We use cookies</strong> to keep you signed in, personalise your experience
    and understand how our site is used.
    <a onclick="openCookieModal()">Manage preferences</a> or read our
    <a onclick="return false;">Privacy Policy</a>.
  </div>
  <div class="cb-btns">
    <button class="cb-btn cb-btn-set" onclick="openCookieModal()">⚙ Settings</button>
    <button class="cb-btn cb-btn-nec" onclick="acceptCookies('necessary')">Necessary Only</button>
    <button class="cb-btn cb-btn-all" onclick="acceptCookies('all')">✓ Accept All</button>
  </div>
</div>

<!-- Re-open button -->
<button id="reopenCookieBtn" onclick="reopenCookieBanner()">🍪 Cookie Settings</button>

<!-- Cookie Settings Modal -->
<div id="cookieModal">
  <div class="cm-box">
    <h3>🍪 Cookie Settings</h3>
    <p>
      FoodFusion uses cookies to improve your experience. Choose which categories
      you are happy with. Your preferences are saved for 1 year.
    </p>

    <!-- Necessary -->
    <div class="cm-row">
      <div class="cm-info">
        <h6>✅ Strictly Necessary</h6>
        <p>Keeps you logged in and secures your session. Always active — cannot be turned off.</p>
      </div>
      <label class="cm-toggle">
        <input type="checkbox" checked disabled>
        <span class="cm-slider"></span>
      </label>
    </div>

    <!-- Analytics -->
    <div class="cm-row">
      <div class="cm-info">
        <h6>📊 Analytics Cookies</h6>
        <p>Help us understand which pages are visited most so we can improve the site.</p>
      </div>
      <label class="cm-toggle">
        <input type="checkbox" id="cm_analytics" checked>
        <span class="cm-slider"></span>
      </label>
    </div>

    <!-- Preferences -->
    <div class="cm-row">
      <div class="cm-info">
        <h6>⚙️ Preference Cookies</h6>
        <p>Remember your filters, sort order and display preferences across visits.</p>
      </div>
      <label class="cm-toggle">
        <input type="checkbox" id="cm_preferences" checked>
        <span class="cm-slider"></span>
      </label>
    </div>

    <!-- Marketing -->
    <div class="cm-row">
      <div class="cm-info">
        <h6>📣 Marketing Cookies</h6>
        <p>Allow us to show recipe recommendations based on your browsing history.</p>
      </div>
      <label class="cm-toggle">
        <input type="checkbox" id="cm_marketing">
        <span class="cm-slider"></span>
      </label>
    </div>

    <div class="cm-footer">
      <button class="cm-cancel" onclick="closeCookieModal()">Cancel</button>
      <button class="cm-save"   onclick="savePreferences()">Save Preferences</button>
    </div>
  </div>
</div>

<script>
/* ══════════════════════════════
   COOKIE CONSENT JAVASCRIPT
   ══════════════════════════════ */

const COOKIE_NAME = 'ff_cookie_consent';
const COOKIE_DAYS = 365;

/* ── helpers ── */
function _setCookie(name, value, days) {
  const d = new Date();
  d.setTime(d.getTime() + days * 86400000);
  document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value))
                  + ';expires=' + d.toUTCString()
                  + ';path=/;SameSite=Lax';
}

function _getCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + '=([^;]*)'));
  if (!match) return null;
  try { return JSON.parse(decodeURIComponent(match[1])); } catch(e) { return null; }
}

function _deleteCookie(name) {
  document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
}

/* ── on page load: hide banner if already consented ── */
(function init() {
  const saved = _getCookie(COOKIE_NAME);
  if (saved) {
    _hideBanner();
    _applyConsent(saved);   // activate any scripts that need consent
  }
})();

/* ── accept all ── */
function acceptCookies(level) {
  const prefs = {
    level       : level,
    necessary   : true,
    analytics   : (level === 'all'),
    preferences : (level === 'all'),
    marketing   : false,
    timestamp   : new Date().toISOString()
  };
  _setCookie(COOKIE_NAME, prefs, COOKIE_DAYS);
  _hideBanner();
  _applyConsent(prefs);
  _showToast(level === 'all' ? '✅ All cookies accepted!' : '✅ Necessary cookies only.');
}

/* ── save custom preferences ── */
function savePreferences() {
  const prefs = {
    level       : 'custom',
    necessary   : true,
    analytics   : document.getElementById('cm_analytics').checked,
    preferences : document.getElementById('cm_preferences').checked,
    marketing   : document.getElementById('cm_marketing').checked,
    timestamp   : new Date().toISOString()
  };
  _setCookie(COOKIE_NAME, prefs, COOKIE_DAYS);
  closeCookieModal();
  _hideBanner();
  _applyConsent(prefs);
  _showToast('✅ Cookie preferences saved!');
}

/* ── apply consent: load scripts based on what was allowed ── */
function _applyConsent(prefs) {

  /* ── ANALYTICS: load Google Analytics if allowed ──
     Replace UA-XXXXXXXX-X with your real GA tracking ID           */
  if (prefs.analytics) {
    if (!document.getElementById('ga-script')) {
      /* Example: Google Analytics 4
      const s = document.createElement('script');
      s.id  = 'ga-script';
      s.src = 'https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX';
      s.async = true;
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-XXXXXXXXXX');
      */
      console.log('[Cookies] Analytics cookies enabled.');
    }
  }

  /* ── PREFERENCES: restore saved filter/sort preferences ── */
  if (prefs.preferences) {
    /* Example: restore last visited sort order
    const savedSort = localStorage.getItem('ff_sort');
    if (savedSort) { ... }
    */
    console.log('[Cookies] Preference cookies enabled.');
  }

  /* ── MARKETING: load recommendation scripts if allowed ── */
  if (prefs.marketing) {
    console.log('[Cookies] Marketing cookies enabled.');
  }

  /* Always enabled: session cookie (PHP handles this) */
  console.log('[Cookies] Consent applied:', prefs.level, prefs);
}

/* ── banner visibility ── */
function _hideBanner() {
  const b = document.getElementById('cookieBanner');
  const r = document.getElementById('reopenCookieBtn');
  if (b) b.classList.add('hidden');
  if (r) r.classList.add('visible');
}

function reopenCookieBanner() {
  const b = document.getElementById('cookieBanner');
  const r = document.getElementById('reopenCookieBtn');
  if (b) b.classList.remove('hidden');
  if (r) r.classList.remove('visible');
}

/* ── modal ── */
function openCookieModal() {
  /* pre-fill toggles from saved prefs */
  const saved = _getCookie(COOKIE_NAME);
  if (saved) {
    const a = document.getElementById('cm_analytics');
    const p = document.getElementById('cm_preferences');
    const m = document.getElementById('cm_marketing');
    if (a) a.checked = !!saved.analytics;
    if (p) p.checked = !!saved.preferences;
    if (m) m.checked = !!saved.marketing;
  }
  const modal = document.getElementById('cookieModal');
  if (modal) modal.classList.add('open');
}

function closeCookieModal() {
  const modal = document.getElementById('cookieModal');
  if (modal) modal.classList.remove('open');
}

/* close modal when clicking outside */
document.addEventListener('click', function(e) {
  const modal = document.getElementById('cookieModal');
  if (modal && e.target === modal) closeCookieModal();
});

/* ── toast ── */
function _showToast(msg) {
  const t = document.createElement('div');
  t.textContent = msg;
  Object.assign(t.style, {
    position    : 'fixed',
    bottom      : '80px',
    left        : '50%',
    transform   : 'translateX(-50%)',
    background  : '#1a1a24',
    color       : '#fff',
    padding     : '12px 28px',
    borderRadius: '50px',
    fontSize    : '13px',
    fontWeight  : '600',
    boxShadow   : '0 4px 20px rgba(0,0,0,.3)',
    zIndex      : '999999',
    opacity     : '1',
    transition  : 'opacity .4s',
    whiteSpace  : 'nowrap'
  });
  document.body.appendChild(t);
  setTimeout(() => {
    t.style.opacity = '0';
    setTimeout(() => t.remove(), 400);
  }, 3000);
}
</script>