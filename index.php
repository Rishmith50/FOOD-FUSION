<?php
session_start();
include "db.php";

define('MAX_ATTEMPTS', 3);
define('LOCK_MINUTES', 3);

$message      = "";
$message_type = "error";

/* ═══ REGISTER ═══ */
if (isset($_POST['register'])) {
    $fname    = trim($_POST['first_name'] ?? '');
    $email    = trim($_POST['email']      ?? '');
    $password = $_POST['password']        ?? '';

    if ($fname === '' || $email === '' || $password === '') {
        $message = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("INSERT INTO users (first_name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fname, $email, $hashed);
        try {
            $stmt->execute();
            $message      = "Registration successful! You can sign in now.";
            $message_type = "success";
        } catch (mysqli_sql_exception $e) {
            $message = ($e->getCode() === 1062)
                ? "Email already registered. Please sign in."
                : "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

/* ═══ LOGIN (with lockout) ═══ */
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($email === '' || $password === '') {
        $message = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            /* ── check lock ── */
            if ($user['lock_until'] !== null) {
                $lockTime = strtotime($user['lock_until']);

                if (time() < $lockTime) {
                    /* still locked */
                    $remaining    = $lockTime - time();
                    $mins         = floor($remaining / 60);
                    $secs         = $remaining % 60;
                    $message      = "Account locked. Try again in {$mins}m {$secs}s.";
                    $message_type = "locked";
                } else {
                    /* ✅ FIX: ONE single clean UPDATE — no duplicates, no missing bind_param */
                    $rst = $conn->prepare("UPDATE users SET failed_attempts=0, lock_until=NULL WHERE user_id=?");
                    $rst->bind_param("i", $user['user_id']);
                    $rst->execute();
                    $user['failed_attempts'] = 0;
                    $user['lock_until']      = null;
                }
            }

            /* ── password check (only if not locked) ── */
            if ($message_type !== "locked") {
                if (password_verify($password, $user['password_hash'])) {
                    /* success */
                    $upd = $conn->prepare("UPDATE users SET failed_attempts=0, lock_until=NULL WHERE user_id=?");
                    $upd->bind_param("i", $user['user_id']);
                    $upd->execute();
                    $_SESSION['user']    = $user['first_name'];
                    $_SESSION['user_id'] = $user['user_id'];
                    header("Location: home1.php");
                    exit();
                } else {
                    /* wrong password */
                    $newAttempts = (int)$user['failed_attempts'] + 1;

                    if ($newAttempts >= MAX_ATTEMPTS) {
                        $lockUntil = date('Y-m-d H:i:s', time() + (LOCK_MINUTES * 60));
                        $upd = $conn->prepare("UPDATE users SET failed_attempts=?, lock_until=? WHERE user_id=?");
                        $upd->bind_param("isi", $newAttempts, $lockUntil, $user['user_id']);
                        $upd->execute();
                        $message      = "Account locked after " . MAX_ATTEMPTS . " failed attempts. Try again in " . LOCK_MINUTES . " minutes.";
                        $message_type = "locked";
                    } else {
                        $upd = $conn->prepare("UPDATE users SET failed_attempts=? WHERE user_id=?");
                        $upd->bind_param("ii", $newAttempts, $user['user_id']);
                        $upd->execute();
                        $left         = MAX_ATTEMPTS - $newAttempts;
                        $message      = "Invalid password. " . $left . " attempt" . ($left===1?"":"s") . " remaining before lockout.";
                        $message_type = "warning";
                    }
                }
            }
        } else {
            $message = "Invalid email or password.";
        }
    }
}

if (isset($_SESSION['user'])) { header("Location: home1.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FoodFusion – Sign In / Register</title>
  <style>
    *, *:before, *:after { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:'Open Sans',Helvetica,Arial,sans-serif; background:#f5f5f5; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    input, button { border:none; outline:none; background:none; font-family:'Open Sans',Helvetica,Arial,sans-serif; }

    .msg-banner { width:900px; max-width:95vw; margin-bottom:16px; padding:12px 20px; border-radius:10px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:10px; }
    .msg-error   { background:#fff0f0; border:1.5px solid #f5c6c6; color:#c0392b; }
    .msg-success { background:#f0fff4; border:1.5px solid #b2dfdb; color:#1a6b3c; }
    .msg-warning { background:#fff8e1; border:1.5px solid #ffe082; color:#b45309; }
    .msg-locked  { background:#1a1a2e; border:1.5px solid #e74c3c; color:#fff; border-radius:10px; animation:pulse-lock 1.5s infinite; }
    @keyframes pulse-lock { 0%,100%{box-shadow:0 0 0 0 rgba(231,76,60,.4)} 50%{box-shadow:0 0 0 8px rgba(231,76,60,0)} }

    .attempt-dots { display:flex; justify-content:center; gap:8px; margin-top:8px; }
    .dot { width:10px; height:10px; border-radius:50%; background:#e8e8e8; transition:background .3s; }
    .dot.used   { background:#e74c3c; }
    .dot.locked { background:#c0392b; animation:blink .8s infinite; }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
    #lockCountdown { font-size:22px; font-weight:800; color:#e74c3c; text-align:center; margin-top:6px; font-variant-numeric:tabular-nums; }

    .cont { border-radius:20px; overflow:hidden; position:relative; width:900px; max-width:95vw; height:550px; background:#fff; box-shadow:-10px -10px 15px rgba(255,255,255,.3),10px 10px 15px rgba(70,70,70,.15),inset -10px -10px 15px rgba(255,255,255,.3),inset 10px 10px 15px rgba(70,70,70,.15); }
    .form { position:relative; width:640px; height:100%; transition:transform 1.2s ease-in-out; padding:50px 30px 0; }
    .sub-cont { overflow:hidden; position:absolute; left:640px; top:0; width:900px; height:100%; padding-left:260px; background:#fff; transition:transform 1.2s ease-in-out; }
    .cont.s--signup .sub-cont { transform:translate3d(-640px,0,0); }
    button { display:block; margin:0 auto; width:260px; height:36px; border-radius:30px; color:#fff; font-size:15px; cursor:pointer; }

    .img { overflow:hidden; z-index:2; position:absolute; left:0; top:0; width:260px; height:100%; padding-top:360px; }
    .img:before { content:''; position:absolute; right:0; top:0; width:900px; height:100%; background-image:url("pablo-merchan-montes-0nT08Z-MhiE-unsplash.jpg"); opacity:.85; background-size:cover; transition:transform 1.2s ease-in-out; }
    .img:after  { content:''; position:absolute; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.55); }
    .cont.s--signup .img:before { transform:translate3d(640px,0,0); }

    .img__text { z-index:2; position:absolute; left:0; top:50px; width:100%; padding:0 20px; text-align:center; color:#fff; transition:transform 1.2s ease-in-out; }
    .img__text h2 { margin-bottom:10px; font-weight:normal; }
    .img__text p  { font-size:14px; line-height:1.5; }
    .cont.s--signup .img__text.m--up { transform:translateX(520px); }
    .img__text.m--in                  { transform:translateX(-520px); }
    .cont.s--signup .img__text.m--in  { transform:translateX(0); }

    .img__btn { overflow:hidden; z-index:2; position:relative; width:100px; height:36px; margin:0 auto; background:transparent; color:#fff; text-transform:uppercase; font-size:15px; cursor:pointer; }
    .img__btn:after { content:''; z-index:2; position:absolute; left:0; top:0; width:100%; height:100%; border:2px solid #fff; border-radius:30px; }
    .img__btn span { position:absolute; left:0; top:0; display:flex; justify-content:center; align-items:center; width:100%; height:100%; transition:transform 1.2s; }
    .img__btn span.m--in               { transform:translateY(-72px); }
    .cont.s--signup .img__btn span.m--in { transform:translateY(0); }
    .cont.s--signup .img__btn span.m--up { transform:translateY(72px); }

    h2 { width:100%; font-size:26px; text-align:center; }
    label { display:block; width:260px; margin:25px auto 0; text-align:center; }
    label span { font-size:12px; color:#cfcfcf; text-transform:uppercase; }
    input { display:block; width:100%; margin-top:5px; padding-bottom:5px; font-size:16px; border-bottom:1px solid rgba(0,0,0,.4); text-align:center; }
    input:disabled { opacity:.4; cursor:not-allowed; }
    .submit { margin-top:40px; margin-bottom:20px; background:#d4af7a; text-transform:uppercase; transition:background .2s,opacity .2s; }
    .submit:disabled { background:#aaa; cursor:not-allowed; opacity:.6; }
    .sign-in { transition-timing-function:ease-out; }
    .cont.s--signup .sign-in { transition-timing-function:ease-in-out; transition-duration:1.2s; transform:translate3d(640px,0,0); }
    .sign-up                 { transform:translate3d(-900px,0,0); }
    .cont.s--signup .sign-up { transform:translate3d(0,0,0); }

    /* Cookie */
    #cookieBanner { position:fixed; bottom:0; left:0; right:0; background:#1a1a24; color:#fff; padding:18px 40px; display:flex; align-items:center; justify-content:space-between; gap:20px; z-index:9999; box-shadow:0 -4px 24px rgba(0,0,0,.25); flex-wrap:wrap; transition:transform .4s ease; }
    #cookieBanner.hidden { transform:translateY(110%); }
    #cookieBanner .cookie-text { font-size:13px; color:rgba(255,255,255,.8); line-height:1.6; flex:1; min-width:260px; }
    #cookieBanner .cookie-text a { color:#d4af7a; text-decoration:underline; }
    #cookieBanner .cookie-text strong { color:#fff; }
    .cookie-btns { display:flex; gap:12px; flex-wrap:wrap; }
    .btn-accept-all { background:#d4af7a; color:#1a1a24; border:none; border-radius:50px; padding:10px 24px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; width:auto; height:auto; display:inline-block; margin:0; transition:background .2s; }
    .btn-accept-all:hover { background:#c9a060; }
    .btn-accept-necessary { background:transparent; color:rgba(255,255,255,.7); border:1.5px solid rgba(255,255,255,.3); border-radius:50px; padding:10px 24px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; width:auto; height:auto; display:inline-block; margin:0; transition:border-color .2s,color .2s; }
    .btn-accept-necessary:hover { border-color:#fff; color:#fff; }
    .btn-cookie-settings { background:transparent; color:rgba(255,255,255,.5); border:none; font-size:12px; cursor:pointer; text-decoration:underline; padding:10px 8px; width:auto; height:auto; display:inline-block; margin:0; }
    #cookieModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:10000; align-items:center; justify-content:center; }
    #cookieModal.open { display:flex; }
    .cookie-modal-box { background:#fff; border-radius:18px; padding:36px; width:480px; max-width:95vw; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.3); }
    .cookie-modal-box h3 { font-size:20px; font-weight:700; margin-bottom:6px; }
    .cookie-modal-box p  { font-size:13px; color:#888; margin-bottom:20px; line-height:1.6; }
    .cookie-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:14px 0; border-bottom:1px solid #f0f0f0; }
    .cookie-toggle-row:last-of-type { border-bottom:none; }
    .cookie-toggle-row .info h6 { font-size:14px; font-weight:700; margin-bottom:2px; color:#333; }
    .cookie-toggle-row .info p  { font-size:12px; color:#aaa; margin:0; }
    .toggle-switch { position:relative; width:44px; height:24px; flex-shrink:0; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; inset:0; background:#ddd; border-radius:24px; cursor:pointer; transition:.3s; }
    .toggle-slider:before { content:''; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; }
    .toggle-switch input:checked + .toggle-slider { background:#d4af7a; }
    .toggle-switch input:checked + .toggle-slider:before { transform:translateX(20px); }
    .toggle-switch input:disabled + .toggle-slider { opacity:.6; cursor:default; }
    .modal-footer-btns { display:flex; gap:12px; margin-top:24px; justify-content:flex-end; }
    .btn-save-prefs { background:#d4af7a; color:#1a1a24; border:none; border-radius:50px; padding:10px 28px; font-size:13px; font-weight:700; cursor:pointer; width:auto; height:auto; margin:0; }
    .btn-cancel-modal { background:transparent; color:#888; border:1.5px solid #ddd; border-radius:50px; padding:10px 20px; font-size:13px; cursor:pointer; width:auto; height:auto; margin:0; }
    #reopenCookie { position:fixed; bottom:12px; right:16px; font-size:11px; color:#aaa; background:transparent; border:none; cursor:pointer; text-decoration:underline; display:none; z-index:999; width:auto; height:auto; margin:0; padding:4px; }
    #reopenCookie.visible { display:block; }
    @media(max-width:900px) { .cont{height:auto;min-height:500px;} .form{width:100%;} .sub-cont{display:none;} #cookieBanner{padding:16px 20px;} }
  </style>
</head>
<body>

<?php if ($message !== ""): ?>
<div class="msg-banner msg-<?= $message_type ?>" style="margin-top:20px;">
  <?php $icons=['error'=>'⚠️','success'=>'✅','warning'=>'⚠️','locked'=>'🔒']; echo $icons[$message_type]??'⚠️'; ?>
  <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<?php
$failedDots = 0;
if ($message_type === 'warning' || $message_type === 'locked') {
    $email_for_dots = trim($_POST['email'] ?? '');
    if ($email_for_dots) {
        $ds = $conn->prepare("SELECT failed_attempts FROM users WHERE email=?");
        $ds->bind_param("s", $email_for_dots);
        $ds->execute();
        $dr = $ds->get_result()->fetch_assoc();
        if ($dr) $failedDots = (int)$dr['failed_attempts'];
    }
}
?>
<?php if ($message_type === 'warning'): ?>
<div class="attempt-dots">
  <?php for($d=1;$d<=MAX_ATTEMPTS;$d++): ?><div class="dot <?= $d<=$failedDots?'used':'' ?>"></div><?php endfor; ?>
</div>
<?php endif; ?>

<?php if ($message_type === 'locked'): ?>
<div class="attempt-dots">
  <?php for($d=1;$d<=MAX_ATTEMPTS;$d++): ?><div class="dot locked"></div><?php endfor; ?>
</div>
<div id="lockCountdown">--:--</div>
<script>
(function(){
  <?php
    $lockSecs = 0;
    $email_lock = trim($_POST['email'] ?? '');
    if ($email_lock) {
        $ls = $conn->prepare("SELECT lock_until FROM users WHERE email=?");
        $ls->bind_param("s", $email_lock); $ls->execute();
        $lr = $ls->get_result()->fetch_assoc();
        if ($lr && $lr['lock_until']) $lockSecs = max(0, strtotime($lr['lock_until']) - time());
    }
  ?>
  let secs = <?= $lockSecs ?>;
  const el = document.getElementById('lockCountdown');
  function tick(){ if(secs<=0){location.reload();return;} const m=Math.floor(secs/60),s=secs%60; el.textContent=m+'m '+(s<10?'0':'')+s+'s remaining'; secs--; setTimeout(tick,1000); }
  tick();
})();
</script>
<?php endif; ?>

<div class="cont" id="authCont">
  <div class="form sign-in">
    <h2>Welcome Back</h2>
    <form method="POST" action="index.php">
      <label><span>Email</span>
        <input type="email" name="email" required <?= $message_type==='locked'?'disabled':'' ?> value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </label>
      <label><span>Password</span>
        <input type="password" name="password" required <?= $message_type==='locked'?'disabled':'' ?>>
      </label>
      <button type="submit" name="login" class="submit" <?= $message_type==='locked'?'disabled':'' ?>>Sign In</button>
    </form>
  </div>

  <div class="sub-cont">
    <div class="img">
      <div class="img__text m--up"><h2>New here?</h2><p>Sign up and join the FoodFusion community!</p></div>
      <div class="img__text m--in"><h2>One of us?</h2><p>Already have an account? Sign in.</p></div>
      <div class="img__btn" id="toggleBtn"><span class="m--up">Sign Up</span><span class="m--in">Sign In</span></div>
    </div>
    <div class="form sign-up">
      <h2>Create Account</h2>
      <form method="POST" action="index.php">
        <label><span>Name</span><input type="text" name="first_name" placeholder="First Name" required></label>
        <label><span>Email</span><input type="email" name="email" placeholder="Email" required></label>
        <label><span>Password</span><input type="password" name="password" placeholder="Min 6 characters" required></label>
        <button type="submit" name="register" class="submit">Sign Up</button>
      </form>
      <?php if ($message_type==='success'): ?>
        <p style="text-align:center;margin-top:14px;font-size:13px;color:#1a6b3c;">✅ <?= htmlspecialchars($message) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="cookieBanner">
  <div class="cookie-text">
    <strong>🍪 We use cookies</strong> to personalise your experience, remember your login session and analyse site traffic.
    By clicking <strong>"Accept All"</strong> you consent to our use of cookies.
    <a href="#" onclick="openCookieModal();return false;">Manage preferences</a> or read our <a href="#" onclick="return false;">Privacy Policy</a>.
  </div>
  <div class="cookie-btns">
    <button class="btn-accept-necessary" onclick="acceptCookies('necessary')">Necessary Only</button>
    <button class="btn-accept-all"       onclick="acceptCookies('all')">Accept All</button>
    <button class="btn-cookie-settings"  onclick="openCookieModal()">⚙ Settings</button>
  </div>
</div>
<button id="reopenCookie" onclick="reopenBanner()">🍪 Cookie Settings</button>

<div id="cookieModal">
  <div class="cookie-modal-box">
    <h3>🍪 Cookie Preferences</h3>
    <p>Choose which cookies you want to allow. You can change these settings at any time.</p>
    <div class="cookie-toggle-row"><div class="info"><h6>Strictly Necessary</h6><p>Required for login sessions and security. Cannot be disabled.</p></div><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
    <div class="cookie-toggle-row"><div class="info"><h6>Analytics Cookies</h6><p>Help us understand how visitors use the site.</p></div><label class="toggle-switch"><input type="checkbox" id="analyticsToggle" checked><span class="toggle-slider"></span></label></div>
    <div class="cookie-toggle-row"><div class="info"><h6>Preference Cookies</h6><p>Remember your settings like language and filters.</p></div><label class="toggle-switch"><input type="checkbox" id="prefToggle" checked><span class="toggle-slider"></span></label></div>
    <div class="cookie-toggle-row"><div class="info"><h6>Marketing Cookies</h6><p>Used to show relevant content based on your interests.</p></div><label class="toggle-switch"><input type="checkbox" id="marketingToggle"><span class="toggle-slider"></span></label></div>
    <div class="modal-footer-btns">
      <button class="btn-cancel-modal" onclick="closeCookieModal()">Cancel</button>
      <button class="btn-save-prefs"   onclick="savePreferences()">Save Preferences</button>
    </div>
  </div>
</div>

<script>
document.getElementById('toggleBtn').addEventListener('click',function(){document.getElementById('authCont').classList.toggle('s--signup');});
<?php if($message_type==='success'): ?>document.getElementById('authCont').classList.add('s--signup');<?php endif; ?>
const COOKIE_KEY='ff_cookie_consent';
function setCookie(n,v,d){const e=new Date();e.setTime(e.getTime()+d*864e5);document.cookie=n+'='+encodeURIComponent(v)+';expires='+e.toUTCString()+';path=/;SameSite=Lax';}
function getCookie(n){const m=document.cookie.match(new RegExp('(?:^|; )'+n+'=([^;]*)'));return m?decodeURIComponent(m[1]):null;}
window.addEventListener('DOMContentLoaded',function(){if(getCookie(COOKIE_KEY))hideBanner();});
function acceptCookies(l){setCookie(COOKIE_KEY,JSON.stringify({level:l,necessary:true,analytics:l==='all',preferences:l==='all',marketing:false,timestamp:new Date().toISOString()}),365);hideBanner();showToast(l==='all'?'✅ All cookies accepted!':'✅ Necessary cookies only saved.');}
function savePreferences(){setCookie(COOKIE_KEY,JSON.stringify({level:'custom',necessary:true,analytics:document.getElementById('analyticsToggle').checked,preferences:document.getElementById('prefToggle').checked,marketing:document.getElementById('marketingToggle').checked,timestamp:new Date().toISOString()}),365);closeCookieModal();hideBanner();showToast('✅ Cookie preferences saved!');}
function hideBanner(){document.getElementById('cookieBanner').classList.add('hidden');document.getElementById('reopenCookie').classList.add('visible');}
function reopenBanner(){document.getElementById('cookieBanner').classList.remove('hidden');document.getElementById('reopenCookie').classList.remove('visible');}
function openCookieModal(){const s=getCookie(COOKIE_KEY);if(s){try{const p=JSON.parse(s);document.getElementById('analyticsToggle').checked=!!p.analytics;document.getElementById('prefToggle').checked=!!p.preferences;document.getElementById('marketingToggle').checked=!!p.marketing;}catch(e){}}document.getElementById('cookieModal').classList.add('open');}
function closeCookieModal(){document.getElementById('cookieModal').classList.remove('open');}
document.getElementById('cookieModal').addEventListener('click',function(e){if(e.target===this)closeCookieModal();});
function showToast(msg){const t=document.createElement('div');t.textContent=msg;Object.assign(t.style,{position:'fixed',bottom:'24px',left:'50%',transform:'translateX(-50%)',background:'#1a1a24',color:'#fff',padding:'12px 28px',borderRadius:'50px',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 20px rgba(0,0,0,.3)',zIndex:'99999',transition:'opacity .4s',opacity:'1'});document.body.appendChild(t);setTimeout(()=>{t.style.opacity='0';setTimeout(()=>t.remove(),400);},3000);}
</script>
</body>
</html>