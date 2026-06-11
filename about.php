<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
$user = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- ======= Vendor CSS Files ======= -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- ======= Main CSS File ======= -->
  <link href="assets/css/main.css" rel="stylesheet">

  <title>About Us - FoodFusion</title>

  <style>
    /* ---- Page Banner ---- */
    .page-banner {
      position: relative;
      height: 420px;
      display: flex;
      align-items: center;
      overflow: hidden;
      margin-top: 0;
    }
    .page-banner img.banner-bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
    }
    .page-banner .banner-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to right, rgba(0,0,0,0.72) 40%, rgba(0,0,0,0.25));
    }
    .page-banner .banner-content {
      position: relative;
      z-index: 2;
      padding: 0 0 0 0;
    }
    .page-banner .breadcrumb-item a { color: var(--color-primary, #ce1212); }
    .page-banner .breadcrumb-item.active { color: rgba(255,255,255,0.7); }
    .page-banner .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.4); }

    /* ---- Stats Counter ---- */
    .stats-section {
      background: var(--color-secondary, #37373f);
    }
    .stat-box {
      padding: 40px 20px;
      text-align: center;
      border-right: 1px solid rgba(255,255,255,0.08);
    }
    .stat-box:last-child { border-right: none; }
    .stat-box .num {
      font-size: 48px;
      font-weight: 800;
      color: var(--color-primary, #ce1212);
      line-height: 1;
    }
    .stat-box .lbl {
      font-size: 13px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.55);
      margin-top: 6px;
    }

    /* ---- Team Card ---- */
    .team-card {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.07);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .team-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 32px rgba(0,0,0,0.13);
    }
    .team-card .team-icon {
      width: 100%;
      height: 180px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 64px;
      background: #fef5f5;
    }
    .team-card .team-info {
      padding: 20px;
      text-align: center;
    }
    .team-card .team-info h5 {
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--color-secondary, #37373f);
    }
    .team-card .team-info span {
      font-size: 13px;
      color: var(--color-primary, #ce1212);
      font-weight: 500;
    }

    /* ---- Value Cards ---- */
    .value-card {
      background: #fff;
      border-radius: 12px;
      padding: 36px 28px;
      height: 100%;
      border-bottom: 4px solid transparent;
      box-shadow: 0 2px 16px rgba(0,0,0,0.06);
      transition: border-color 0.3s ease, transform 0.3s ease;
    }
    .value-card:hover {
      border-bottom-color: var(--color-primary, #ce1212);
      transform: translateY(-4px);
    }
    .value-card .icon {
      font-size: 38px;
      margin-bottom: 16px;
      display: block;
    }
    .value-card h5 {
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--color-secondary, #37373f);
    }
    .value-card p {
      font-size: 14px;
      color: #777;
      line-height: 1.7;
      margin: 0;
    }

    /* ---- FAQ Accordion ---- */
    .faq-section .accordion-button:not(.collapsed) {
      color: var(--color-primary, #ce1212);
      background: #fff8f8;
      box-shadow: none;
    }
    .faq-section .accordion-button:focus {
      box-shadow: 0 0 0 0.2rem rgba(206,18,18,0.15);
    }
    .faq-section .accordion-button::after {
      filter: hue-rotate(0deg);
    }

    /* ---- CTA Strip ---- */
    .cta-strip {
      background: var(--color-primary, #ce1212);
    }
    .cta-strip h2 { color: #fff; font-weight: 800; }
    .cta-strip p  { color: rgba(255,255,255,0.82); }
    .cta-strip .btn-light {
      font-weight: 600;
      color: var(--color-primary, #ce1212);
      border: none;
      padding: 12px 36px;
      border-radius: 50px;
      transition: transform 0.2s;
    }
    .cta-strip .btn-light:hover { transform: scale(1.04); }

    /* ---- Section helpers ---- */
    .section-title-sm {
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--color-primary, #ce1212);
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .section-title-sm::before {
      content: '';
      display: inline-block;
      width: 28px; height: 2px;
      background: var(--color-primary, #ce1212);
    }

    /* ---- Mission image ---- */
    .mission-img-wrap {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 8px 40px rgba(0,0,0,0.14);
    }
    .mission-img-wrap img { width: 100%; display: block; }
    .mission-img-wrap .img-badge {
      position: absolute;
      bottom: 24px; left: 24px;
      background: var(--color-primary, #ce1212);
      color: #fff;
      padding: 14px 22px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      line-height: 1.4;
    }

    /* Mobile */
    @media (max-width: 768px) {
      .page-banner { height: 280px; }
      .stat-box { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.08); }
    }
  </style>
</head>

<body class="index-page">

<!-- ======= Header ======= -->
<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="container position-relative d-flex align-items-center justify-content-between">

    <a href="home1.php" class="logo d-flex align-items-center me-auto me-xl-0">
      <h1 class="sitename">FOOD FUSION</h1><span>.</span>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="home1.php">Home</a></li>
        <li><a href="about.php" class="active">About</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="educational.php">Educational Resources</a></li>
        <li><a href="community.php">Community</a></li>
        <li><a href="home1.php#contact">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <a class="btn-getstarted" href="logout.php">Logout</a>

  </div>
</header>
<!-- End Header -->

<main class="main">

  <!-- ======= Page Banner ======= -->
  <section class="page-banner">
    <img src="pablo-merchan-montes-0nT08Z-MhiE-unsplash.jpg" alt="About FoodFusion" class="banner-bg">
    <div class="banner-overlay"></div>
    <div class="banner-content container">
      <div data-aos="fade-up">
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="home1.php">Home</a></li>
            <li class="breadcrumb-item active">About Us</li>
          </ol>
        </nav>
        <h1 class="display-4 fw-bold text-white mb-3">About <span style="color:var(--color-primary,#ce1212);">FoodFusion</span></h1>
        <p class="lead text-white-50 mb-0" style="max-width:520px;">
          A community-driven culinary platform for home cooks, recipe lovers, and food explorers from around the world.
        </p>
      </div>
    </div>
  </section>
  <!-- End Page Banner -->

  <!-- ======= Stats Counter Section ======= -->
  <section class="stats-section py-0">
    <div class="container-fluid px-0">
      <div class="row g-0">
        <div class="col-6 col-md-3">
          <div class="stat-box" data-aos="fade-up" data-aos-delay="0">
            <div class="num purecounter" data-purecounter-start="0" data-purecounter-end="2400" data-purecounter-duration="2">0</div>
            <div class="lbl">Recipes</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-box" data-aos="fade-up" data-aos-delay="100">
            <div class="num purecounter" data-purecounter-start="0" data-purecounter-end="18" data-purecounter-duration="2">0</div>
            <div class="lbl">Cuisines</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-box" data-aos="fade-up" data-aos-delay="200">
            <div class="num purecounter" data-purecounter-start="0" data-purecounter-end="12000" data-purecounter-duration="2">0</div>
            <div class="lbl">Members</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-box" data-aos="fade-up" data-aos-delay="300">
            <div class="num purecounter" data-purecounter-start="0" data-purecounter-end="96" data-purecounter-duration="2">0</div>
            <div class="lbl">Countries</div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- End Stats -->

  <!-- ======= Mission Section ======= -->
  <section class="section light-background" id="about-mission">
    <div class="container">
      <div class="row gy-5 align-items-center">

        <div class="col-lg-6" data-aos="fade-right">
          <div class="mission-img-wrap">
            <img src="alex-munsell-Yr4n8O_3UPc-unsplash.jpg" alt="Our Mission">
            <div class="img-badge">
              🍽️ Since 2022<br>Inspiring Home Cooks
            </div>
          </div>
        </div>

        <div class="col-lg-6" data-aos="fade-left" data-aos-delay="100">
          <div class="ps-lg-4">
            <span class="section-title-sm">Who We Are</span>
            <h2 class="fw-bold mb-3" style="font-size:clamp(28px,4vw,42px);line-height:1.2;">
              More Than a Recipe Site —<br>
              <span style="color:var(--color-primary,#ce1212);">A Global Community</span>
            </h2>
            <p class="text-muted mb-3" style="line-height:1.85;">
              FoodFusion is a community-driven culinary platform created for people who enjoy cooking, sharing recipes and exploring different cuisines from around the world. The platform aims to inspire creativity in the kitchen and encourage people to cook delicious meals at home.
            </p>
            <p class="text-muted mb-4" style="line-height:1.85;">
              Our mission is to bring together food enthusiasts in a single space where they can discover recipes, learn cooking techniques and share their own culinary experiences with others. Whether you're a beginner or a seasoned home chef, FoodFusion has something for you.
            </p>

            <div class="row g-3">
              <div class="col-6">
                <div class="d-flex align-items-start gap-3">
                  <i class="bi bi-check-circle-fill fs-5" style="color:var(--color-primary,#ce1212);margin-top:2px;"></i>
                  <span class="fw-500 text-dark">Curated Global Recipes</span>
                </div>
              </div>
              <div class="col-6">
                <div class="d-flex align-items-start gap-3">
                  <i class="bi bi-check-circle-fill fs-5" style="color:var(--color-primary,#ce1212);margin-top:2px;"></i>
                  <span class="fw-500 text-dark">Community Cookbook</span>
                </div>
              </div>
              <div class="col-6">
                <div class="d-flex align-items-start gap-3">
                  <i class="bi bi-check-circle-fill fs-5" style="color:var(--color-primary,#ce1212);margin-top:2px;"></i>
                  <span class="fw-500 text-dark">Cooking Tutorials</span>
                </div>
              </div>
              <div class="col-6">
                <div class="d-flex align-items-start gap-3">
                  <i class="bi bi-check-circle-fill fs-5" style="color:var(--color-primary,#ce1212);margin-top:2px;"></i>
                  <span class="fw-500 text-dark">Mobile Responsive</span>
                </div>
              </div>
            </div>

            <a href="recipes.php" class="btn btn-danger rounded-pill px-4 py-2 mt-4 fw-600">
              Explore Recipes &nbsp;<i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>
  <!-- End Mission -->

  <!-- ======= Our Values Section ======= -->
  <section class="section" id="about-values">
    <div class="container">

      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-title-sm justify-content-center">What We Stand For</span>
        <h2 class="fw-bold" style="font-size:clamp(28px,4vw,40px);">Our Core Values</h2>
        <p class="text-muted mt-2" style="max-width:540px;margin:0 auto;">Everything we build at FoodFusion is guided by these principles.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
          <div class="value-card">
            <span class="icon">🌍</span>
            <h5>Culinary Diversity</h5>
            <p>We celebrate recipes from every culture, believing that food is the most universal language that connects people across borders.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="value-card">
            <span class="icon">🤝</span>
            <h5>Community First</h5>
            <p>Our platform thrives on the contributions of passionate home cooks who share their stories, tips, and treasured family recipes.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="value-card">
            <span class="icon">🎓</span>
            <h5>Continuous Learning</h5>
            <p>We provide tutorials, videos and culinary resources that help everyone improve their cooking skills, regardless of experience level.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
          <div class="value-card">
            <span class="icon">🌿</span>
            <h5>Sustainability</h5>
            <p>We promote mindful, sustainable cooking practices and educate our community on reducing food waste and eating responsibly.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
          <div class="value-card">
            <span class="icon">🔒</span>
            <h5>Privacy & Trust</h5>
            <p>Your data is handled with care. We implement strong security measures to protect every member of our growing community.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
          <div class="value-card">
            <span class="icon">📱</span>
            <h5>Accessibility</h5>
            <p>FoodFusion is designed to work seamlessly on any device so you can cook, explore, and connect from anywhere in the world.</p>
          </div>
        </div>
      </div>

    </div>
  </section>
  <!-- End Values -->

  <!-- ======= Meet the Team Section ======= -->
  <section class="section light-background" id="about-team">
    <div class="container">

      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-title-sm justify-content-center">The People Behind It</span>
        <h2 class="fw-bold" style="font-size:clamp(28px,4vw,40px);">Meet Our Team</h2>
        <p class="text-muted mt-2" style="max-width:500px;margin:0 auto;">Passionate food lovers and tech enthusiasts who built FoodFusion for you.</p>
      </div>

      <div class="row g-4 justify-content-center">
        <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="0">
          <div class="team-card">
            <div class="team-icon">👨‍🍳</div>
            <div class="team-info">
              <h5>Arjun Mehta</h5>
              <span>Founder & Head Chef</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
          <div class="team-card">
            <div class="team-icon">👩‍💻</div>
            <div class="team-info">
              <h5>Priya Sharma</h5>
              <span>Lead Developer</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="200">
          <div class="team-card">
            <div class="team-icon">🎨</div>
            <div class="team-info">
              <h5>Rohan Patel</h5>
              <span>UI/UX Designer</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
          <div class="team-card">
            <div class="team-icon">📢</div>
            <div class="team-info">
              <h5>Sneha Iyer</h5>
              <span>Community Manager</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
  <!-- End Team -->

  <!-- ======= FAQ Section ======= -->
  <section class="section faq-section" id="about-faq">
    <div class="container">

      <div class="row gy-5 align-items-start">

        <div class="col-lg-5" data-aos="fade-right">
          <span class="section-title-sm">Got Questions?</span>
          <h2 class="fw-bold mb-3" style="font-size:clamp(28px,4vw,40px);">
            Frequently Asked <span style="color:var(--color-primary,#ce1212);">Questions</span>
          </h2>
          <p class="text-muted" style="line-height:1.8;">
            Everything you need to know about FoodFusion. Can't find the answer? Reach out to us anytime.
          </p>
          <a href="home1.php#contact" class="btn btn-outline-danger rounded-pill px-4 py-2 mt-3">
            Contact Us &nbsp;<i class="bi bi-envelope"></i>
          </a>
        </div>

        <div class="col-lg-7" data-aos="fade-left" data-aos-delay="100">
          <div class="accordion" id="faqAccordion">

            <div class="accordion-item border-0 mb-3 rounded shadow-sm">
              <h2 class="accordion-header">
                <button class="accordion-button rounded fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                  Is FoodFusion free to use?
                </button>
              </h2>
              <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                  Yes! FoodFusion is completely free. Simply create an account and you get instant access to all recipes, culinary resources, and community features.
                </div>
              </div>
            </div>

            <div class="accordion-item border-0 mb-3 rounded shadow-sm">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                  Can I submit my own recipes?
                </button>
              </h2>
              <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                  Absolutely! Registered members can contribute their favourite recipes to the Community Cookbook. Share your culinary creations with thousands of food lovers.
                </div>
              </div>
            </div>

            <div class="accordion-item border-0 mb-3 rounded shadow-sm">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                  How are recipes categorised?
                </button>
              </h2>
              <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                  Recipes are categorised by cuisine type (Indian, Italian, Chinese, etc.), dietary preference (Vegetarian, Non-Vegetarian), and cooking difficulty (Easy, Medium, Hard).
                </div>
              </div>
            </div>

            <div class="accordion-item border-0 mb-3 rounded shadow-sm">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                  Is my data safe on FoodFusion?
                </button>
              </h2>
              <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                  We take security seriously. All passwords are hashed using industry-standard encryption and your personal data is never sold to third parties.
                </div>
              </div>
            </div>

            <div class="accordion-item border-0 rounded shadow-sm">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed rounded fw-600" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                  Can I use FoodFusion on my phone?
                </button>
              </h2>
              <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">
                  Yes! FoodFusion is fully mobile-responsive. Browse recipes, contribute to the community, and access all resources seamlessly on any device.
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>
  <!-- End FAQ -->

  <!-- ======= CTA Strip ======= -->
  <section class="cta-strip py-5" data-aos="zoom-in">
    <div class="container text-center">
      <h2 class="fw-bold mb-2">Ready to Start Cooking?</h2>
      <p class="mb-4">Join thousands of food lovers already sharing and exploring on FoodFusion.</p>
      <a href="recipes.php" class="btn btn-light rounded-pill px-5 py-2 fw-600 fs-6">
        Explore Recipes &nbsp;<i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </section>
  <!-- End CTA -->

</main>
<!-- End Main -->

<!-- ======= Footer ======= -->
<footer id="footer" style="background:#1f1f24;color:white;padding:40px 0;text-align:center;">
  <div class="container">
    <h3>FoodFusion</h3>
    <p>Cook • Share • Explore Delicious Recipes</p>

    <div style="margin:20px 0;">
      <a href="https://wa.me/919999999999" target="_blank" style="margin:10px;">
        <img src="pngwing.com (1).png" width="40" alt="WhatsApp">
      </a>
      <a href="https://www.instagram.com/foodf_usion18/" target="_blank" style="margin:10px;">
        <img src="pngwing.com.png" width="40" alt="Instagram">
      </a>
      <a href="https://facebook.com" target="_blank" style="margin:10px;">
        <img src="facebook.png" width="40" alt="Facebook">
      </a>
    </div>

    <p>📞 +91 9876543210</p>
    <p>📧 contact@foodfusion.com</p>

    <p style="margin-top:20px;font-size:14px;">
      © 2026 FoodFusion. All Rights Reserved.
    </p>
  </div>
</footer>
<!-- End Footer -->

<!-- ======= Scroll Top ======= -->
<div class="scroll-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</div>

<!-- ======= Vendor JS Files ======= -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

<!-- ======= Main JS File ======= -->
<script src="assets/js/main.js"></script>
<?php include 'cookie_consent.php'; ?>
</body>
</html>