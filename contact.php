<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
include "db.php";

$success_msg = "";
$error_msg   = "";
$name = $email = $subject = $message_text = "";

/* ══════════════════════════════════════════
   HANDLE CONTACT FORM SUBMISSION → SAVE TO DB
══════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {

    $name         = trim($_POST['name']    ?? '');
    $email        = trim($_POST['email']   ?? '');
    $subject      = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message_text) {
        $error_msg = "Name, Email and Message are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        /* contact_messages table has: message_id, name, email, message, submitted_at
           We'll store subject inside the message field for compatibility */
        $full_message = $subject ? "[Subject: $subject]\n$message_text" : $message_text;

        $stmt = $conn->prepare(
            "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $name, $email, $full_message);

        if ($stmt->execute()) {
            $success_msg = "Thank you, $name! Your message has been received. We'll get back to you shortly.";
            $name = $email = $subject = $message_text = ""; // clear fields
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
    }
}

/* ══════════════════════════════════════════
   FETCH RECENT MESSAGES (admin view – last 5)
══════════════════════════════════════════ */
$recent = $conn->query(
    "SELECT * FROM contact_messages ORDER BY submitted_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$total_messages = $conn->query("SELECT COUNT(*) as c FROM contact_messages")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us – FoodFusion</title>

  <!-- Yummy Bootstrap Template Assets -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    /* ── Hero Banner ── */
    .contact-hero {
      background: url('assets/img/events-1.jpg') center/cover no-repeat;
      position: relative; min-height: 340px;
      display: flex; align-items: flex-end; padding-bottom: 60px;
      margin-top: 80px;
    }
    .contact-hero::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(to top,rgba(0,0,0,.82) 0%,rgba(0,0,0,.2) 100%);
    }
    .contact-hero .hero-text { position: relative; z-index: 2; }

    /* ── Info Cards ── */
    .info-card {
      background: #fff; border-radius: 18px;
      border: 1px solid #f0e9e2; padding: 30px 24px;
      text-align: center; height: 100%;
      transition: transform .3s, box-shadow .3s;
    }
    .info-card:hover { transform: translateY(-5px); box-shadow: 0 14px 32px rgba(0,0,0,.09); }
    .info-card .ic-icon {
      width: 60px; height: 60px; border-radius: 50%;
      background: rgba(206,18,18,.1); color: #ce1212;
      font-size: 24px; display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }
    .info-card h5 { font-size: 16px; font-weight: 700; margin-bottom: 6px; color: #1a1612; }
    .info-card p  { font-size: 14px; color: #9b8677; margin: 0; line-height: 1.7; }
    .info-card a  { color: #ce1212; text-decoration: none; font-weight: 500; }
    .info-card a:hover { text-decoration: underline; }

    /* ── Contact Form Card ── */
    .form-card {
      background: #fff; border-radius: 24px;
      border: 1px solid #f0e9e2; padding: 48px;
      box-shadow: 0 8px 32px rgba(0,0,0,.06);
    }
    .form-label  { font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #8b6347; margin-bottom: 8px; }
    .form-control, .form-select {
      border-radius: 14px; border: 1.5px solid #e8e0d8;
      padding: 13px 18px; font-size: 15px;
      transition: border-color .25s, box-shadow .25s;
    }
    .form-control:focus, .form-select:focus {
      border-color: #ce1212; box-shadow: 0 0 0 3px rgba(206,18,18,.1);
      outline: none;
    }
    .form-control::placeholder { color: rgba(155,134,119,.55); }
    textarea.form-control { resize: vertical; min-height: 140px; }

    .btn-send {
      background: #ce1212; color: #fff; border: none;
      border-radius: 50px; padding: 15px 44px;
      font-size: 16px; font-weight: 600;
      transition: background .25s, transform .2s, box-shadow .25s;
      width: 100%;
    }
    .btn-send:hover { background: #a50e0e; transform: translateY(-2px); box-shadow: 0 10px 24px rgba(206,18,18,.28); color: #fff; }
    .btn-send:active { transform: translateY(0); }

    /* ── Alert banners ── */
    .alert-success-custom {
      background: rgba(34,139,34,.1); border: 1px solid rgba(34,139,34,.25);
      color: #1a6b1a; border-radius: 14px; padding: 18px 22px;
      display: flex; align-items: center; gap: 12px;
      animation: slideDown .4s ease;
    }
    .alert-error-custom {
      background: rgba(206,18,18,.09); border: 1px solid rgba(206,18,18,.25);
      color: #a50e0e; border-radius: 14px; padding: 18px 22px;
      display: flex; align-items: center; gap: 12px;
    }
    @keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }

    /* ── Map placeholder / Sidebar ── */
    .sidebar-card {
      background: #fff; border-radius: 20px;
      border: 1px solid #f0e9e2; overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,.06);
    }
    .sidebar-card .map-placeholder {
      background: linear-gradient(135deg,#f9f0e8,#f0e9e2);
      height: 220px; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: #9b8677; font-size: 14px;
    }
    .sidebar-card .map-placeholder i { font-size: 3rem; margin-bottom: 10px; color: #ce1212; }
    .sidebar-card .sc-body { padding: 28px; }
    .sidebar-card h5 { font-weight: 700; font-size: 17px; margin-bottom: 18px; color: #1a1612; }
    .sc-row { display: flex; gap: 14px; margin-bottom: 16px; align-items: flex-start; }
    .sc-row i { font-size: 18px; color: #ce1212; flex-shrink: 0; margin-top: 2px; }
    .sc-row p { font-size: 14px; color: #9b8677; margin: 0; line-height: 1.65; }

    /* ── Recent Messages ── */
    .msg-table-wrap {
      background: #fff; border-radius: 20px;
      border: 1px solid #f0e9e2; overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,.05);
    }
    .msg-table-wrap .table-header {
      padding: 20px 24px; border-bottom: 1px solid #f0e9e2;
      display: flex; align-items: center; justify-content: space-between;
    }
    .msg-table-wrap .table-header h5 { font-weight: 700; font-size: 17px; margin: 0; }
    .msg-table-wrap table { margin: 0; }
    .msg-table-wrap thead th {
      background: #faf6f0; font-size: 11px; font-weight: 700;
      letter-spacing: 1px; text-transform: uppercase;
      color: #8b6347; border: none; padding: 14px 20px;
    }
    .msg-table-wrap tbody td {
      font-size: 14px; color: #5c3d2e; border-color: #f5ede6;
      padding: 14px 20px; vertical-align: middle;
    }
    .msg-table-wrap tbody tr:hover { background: #faf6f0; }
    .msg-preview { max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #9b8677; }

    /* ── Section Heading ── */
    .section-title-line { display: flex; align-items: center; gap: 14px; margin-bottom: 36px; }
    .section-title-line::before { content: ''; display: block; width: 4px; height: 36px; background: #ce1212; border-radius: 4px; }
    .section-title-line h2 { font-size: 28px; font-weight: 700; margin: 0; color: #1a1612; }

    .char-count { font-size: 12px; color: #9b8677; text-align: right; margin-top: 4px; }
  </style>
</head>
<body>

<!-- ======= Header (Yummy template) ======= -->
<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="container position-relative d-flex align-items-center justify-content-between">

    <a href="home1.php" class="logo d-flex align-items-center me-auto me-xl-0">
      <h1 class="sitename">FOOD FUSION</h1><span>.</span>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="home1.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="educational.php">Educational Resources</a></li>
        <li><a href="community.php">Community</a></li>
        <li><a href="contact.php" class="active">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <a class="btn-getstarted" href="logout.php">Logout</a>
  </div>
</header>

<!-- ======= Hero Banner ======= -->
<div class="contact-hero">
  <div class="container hero-text" data-aos="fade-up">
    <p style="color:#ce1212;font-size:12px;letter-spacing:3px;text-transform:uppercase;font-weight:600;margin-bottom:10px;">
      ▸ Get In Touch
    </p>
    <h1 style="color:#fff;font-size:clamp(36px,5vw,62px);font-weight:800;line-height:1.1;margin-bottom:14px;">
      Contact Us
    </h1>
    <p style="color:rgba(255,255,255,.65);font-size:16px;max-width:520px;">
      Have a recipe idea, a question or feedback? We'd love to hear from you.
      Every message is read and replied to personally.
    </p>
  </div>
</div>

<main class="main">
<div class="container" style="padding-top:60px;padding-bottom:80px;">

  <!-- ── INFO CARDS ── -->
  <div class="row g-4 mb-5" data-aos="fade-up">
    <div class="col-md-4">
      <div class="info-card">
        <div class="ic-icon"><i class="bi bi-telephone-fill"></i></div>
        <h5>Call Us</h5>
        <p><a href="tel:+919876543210">+91 9876543210</a><br>Mon – Sat, 9am – 6pm</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="info-card">
        <div class="ic-icon"><i class="bi bi-envelope-fill"></i></div>
        <h5>Email Us</h5>
        <p><a href="mailto:contact@foodfusion.com">contact@foodfusion.com</a><br>We reply within 24 hours</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="info-card">
        <div class="ic-icon"><i class="bi bi-geo-alt-fill"></i></div>
        <h5>Visit Us</h5>
        <p>FoodFusion HQ<br>Mumbai, Maharashtra 400001</p>
      </div>
    </div>
  </div>

  <!-- ── MAIN CONTENT: FORM + SIDEBAR ── -->
  <div class="row g-5 mb-5">

    <!-- LEFT: CONTACT FORM -->
    <div class="col-lg-7" data-aos="fade-up">
      <div class="form-card">

        <div class="section-title-line" style="margin-bottom:28px;">
          <h2>Send Us a Message</h2>
        </div>

        <?php if ($success_msg): ?>
        <div class="alert-success-custom mb-4">
          <i class="bi bi-check-circle-fill" style="font-size:20px;"></i>
          <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="alert-error-custom mb-4">
          <i class="bi bi-exclamation-circle-fill" style="font-size:20px;"></i>
          <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="contactForm" novalidate>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label">Your Name <span style="color:#ce1212">*</span></label>
              <input type="text" name="name" class="form-control"
                     placeholder="e.g. Priya Sharma"
                     value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Email Address <span style="color:#ce1212">*</span></label>
              <input type="email" name="email" class="form-control"
                     placeholder="you@example.com"
                     value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Subject</label>
              <select name="subject" class="form-select">
                <option value="" <?= !$subject?'selected':'' ?>>-- Choose a subject --</option>
                <option value="Recipe Enquiry"      <?= $subject==='Recipe Enquiry'      ?'selected':'' ?>>🍽️ Recipe Enquiry</option>
                <option value="Community Cookbook"  <?= $subject==='Community Cookbook'  ?'selected':'' ?>>📖 Community Cookbook</option>
                <option value="Culinary Resources"  <?= $subject==='Culinary Resources'  ?'selected':'' ?>>🎓 Culinary Resources</option>
                <option value="Technical Support"   <?= $subject==='Technical Support'   ?'selected':'' ?>>🛠️ Technical Support</option>
                <option value="Partnership"         <?= $subject==='Partnership'         ?'selected':'' ?>>🤝 Partnership</option>
                <option value="General Feedback"    <?= $subject==='General Feedback'    ?'selected':'' ?>>💬 General Feedback</option>
                <option value="Other"               <?= $subject==='Other'               ?'selected':'' ?>>📌 Other</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Your Message <span style="color:#ce1212">*</span></label>
              <textarea name="message" class="form-control" id="msgTextarea"
                        placeholder="Tell us what's on your mind…"
                        maxlength="1000" required><?= htmlspecialchars($message_text) ?></textarea>
              <div class="char-count"><span id="charCount">0</span> / 1000 characters</div>
            </div>

            <!-- Logged-in user info note -->
            <div class="col-12">
              <div style="background:#faf6f0;border-radius:12px;padding:12px 16px;font-size:13px;color:#8b6347;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-person-check-fill" style="color:#ce1212;"></i>
                Sending as <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>
              </div>
            </div>

            <div class="col-12" style="margin-top:8px;">
              <button type="submit" name="send_message" class="btn-send">
                <i class="bi bi-send-fill me-2"></i> Send Message
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- RIGHT: SIDEBAR -->
    <div class="col-lg-5" data-aos="fade-up" data-aos-delay="150">

      <!-- Map Placeholder -->
      <div class="sidebar-card mb-4">
        <div class="map-placeholder">
          <i class="bi bi-pin-map-fill"></i>
          <span>Mumbai, Maharashtra</span>
          <small style="margin-top:4px;opacity:.7;">FoodFusion HQ</small>
        </div>
        <div class="sc-body">
          <h5>Our Details</h5>
          <div class="sc-row">
            <i class="bi bi-geo-alt-fill"></i>
            <p>FoodFusion HQ, Andheri East,<br>Mumbai – 400069, Maharashtra</p>
          </div>
          <div class="sc-row">
            <i class="bi bi-telephone-fill"></i>
            <p><a href="tel:+919876543210" style="color:#ce1212;text-decoration:none;">+91 9876543210</a></p>
          </div>
          <div class="sc-row">
            <i class="bi bi-envelope-fill"></i>
            <p><a href="mailto:contact@foodfusion.com" style="color:#ce1212;text-decoration:none;">contact@foodfusion.com</a></p>
          </div>
          <div class="sc-row">
            <i class="bi bi-clock-fill"></i>
            <p>Mon – Sat: 9:00 AM – 6:00 PM<br>Sunday: Closed</p>
          </div>
        </div>
      </div>

      <!-- Social Media -->
      <div class="sidebar-card">
        <div class="sc-body">
          <h5>Follow Us</h5>
          <div class="d-flex gap-3">
            <a href="https://wa.me/919999999999" target="_blank"
               style="width:48px;height:48px;border-radius:50%;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);display:flex;align-items:center;justify-content:center;transition:transform .25s;"
               onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
              <img src="pngwing.com (1).png" width="26">
            </a>
            <a href="https://www.instagram.com/foodf_usion18/" target="_blank"
               style="width:48px;height:48px;border-radius:50%;background:rgba(228,64,95,.1);border:1px solid rgba(228,64,95,.3);display:flex;align-items:center;justify-content:center;transition:transform .25s;"
               onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
              <img src="pngwing.com.png" width="26">
            </a>
            <a href="https://facebook.com" target="_blank"
               style="width:48px;height:48px;border-radius:50%;background:rgba(66,103,178,.1);border:1px solid rgba(66,103,178,.3);display:flex;align-items:center;justify-content:center;transition:transform .25s;"
               onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
              <img src="facebook.png" width="26">
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── RECENT MESSAGES (Admin View) ── -->
  <?php if (!empty($recent)): ?>
  <div data-aos="fade-up">
    <div class="section-title-line">
      <h2>Recent Enquiries
        <span style="font-size:15px;font-weight:400;color:#9b8677;margin-left:8px;">(<?= $total_messages ?> total)</span>
      </h2>
    </div>
    <div class="msg-table-wrap">
      <div class="table-header">
        <h5><i class="bi bi-inbox-fill text-danger me-2"></i>Latest Messages</h5>
        <span style="font-size:13px;color:#9b8677;">Showing 5 most recent</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Message Preview</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $i => $msg): ?>
            <tr>
              <td><span style="font-size:12px;color:#c0b0a0;"><?= $msg['message_id'] ?></span></td>
              <td style="font-weight:600;"><?= htmlspecialchars($msg['name']) ?></td>
              <td>
                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"
                   style="color:#ce1212;text-decoration:none;font-size:13px;">
                  <?= htmlspecialchars($msg['email']) ?>
                </a>
              </td>
              <td>
                <div class="msg-preview"><?= htmlspecialchars($msg['message']) ?></div>
              </td>
              <td style="font-size:12px;color:#9b8677;white-space:nowrap;">
                <?= date('d M Y, H:i', strtotime($msg['submitted_at'])) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->
</main>

<!-- ======= Footer (same as home1.php) ======= -->
<footer id="footer" style="background:#1f1f24;color:white;padding:40px 0;text-align:center;">
  <div class="container">
    <h3>FoodFusion</h3>
    <p>Cook • Share • Explore Delicious Recipes</p>
    <div style="margin:20px 0;">
      <a href="https://wa.me/919999999999" target="_blank" style="margin:10px;">
        <img src="pngwing.com (1).png" width="40">
      </a>
      <a href="https://instagram.com" target="_blank" style="margin:10px;">
        <img src="pngwing.com.png" width="40">
      </a>
      <a href="https://facebook.com" target="_blank" style="margin:10px;">
        <img src="facebook.png" width="40">
      </a>
    </div>
    <p>📞 +91 9876543210</p>
    <p>📧 contact@foodfusion.com</p>
    <p style="margin-top:20px;font-size:14px;">© 2026 FoodFusion. All Rights Reserved.</p>
  </div>
</footer>

<div class="scroll-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</div>

<!-- Vendor JS (Yummy template) -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="assets/js/main.js"></script>

<script>
// ── Character counter ──
const ta    = document.getElementById('msgTextarea');
const count = document.getElementById('charCount');
if (ta && count) {
  count.textContent = ta.value.length;
  ta.addEventListener('input', () => {
    count.textContent = ta.value.length;
    count.style.color = ta.value.length > 900 ? '#ce1212' : '#9b8677';
  });
}

// ── Client-side validation ──
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
  const name = this.querySelector('[name="name"]').value.trim();
  const email = this.querySelector('[name="email"]').value.trim();
  const msg = this.querySelector('[name="message"]').value.trim();
  if (!name || !email || !msg) {
    e.preventDefault();
    alert('Please fill in all required fields (Name, Email, Message).');
  }
});
</script>
<?php include 'cookie_consent.php'; ?>
</body>
</html>