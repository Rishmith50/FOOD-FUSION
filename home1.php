<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php"); // change to your login page if different
    exit();
}
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

<title>Home - FoodFusion</title>
</head>
<body class="index-page">

<!-- ======= Header ======= -->
<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="container position-relative d-flex align-items-center justify-content-between">

    <a href="home.php" class="logo d-flex align-items-center me-auto me-xl-0">
      <!-- Image logo line is optional if you have a logo image -->
      <!-- <img src="assets/img/logo.png" alt=""> -->
      <h1 class="sitename">FOOD FUSION</h1><span>.</span>
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="#hero" class="active">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="educational.php">Educational Resources</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="community.php">Community</a></li>
        <li><a href="contact.php">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <a class="btn-getstarted" href="logout.php">logout</a>

  </div>
</header>
<!-- End Header -->

<main class="main">

  <!-- ======= Hero Section ======= -->
  <section id="hero" class="hero section light-background">
    <div class="container">

      <div class="row gy-4 justify-content-center justify-content-lg-between">

        <div class="col-lg-5 order-2 order-lg-1 d-flex flex-column justify-content-center">
          <h1 data-aos="fade-up">Enjoy Your Healthy<br>Delicious Food</h1>
          <p data-aos="fade-up" data-aos-delay="100">Cook, share and explore delicious recipes with the FoodFusion community.</p>
          <div class="d-flex" data-aos="fade-up" data-aos-delay="200">
            <a href="recipes.php" class="btn-get-started">Explore Our Recipies</a>
           
            </a>
          </div>
        </div>

        <div class="col-lg-5 order-1 order-lg-2 hero-img" data-aos="zoom-out">
          <img src="assets/img/hero-img.png" class="img-fluid animated" alt="">
        </div>

      </div>

    </div>
  </section><!-- End Hero Section -->

  <!-- You can paste more sections from the template here,
       e.g., About, Menu, Events, Chefs, Gallery, etc.
       They are all inside the same index.html file in the zip.
       Sections begin with <section id="..."> -->
</main>

<!-- ======= Footer or Logout link ======= -->
<footer>
  <div class="container text-center">
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</p>
    <p><a href="logout.php">Logout</a></p>
  </div>
</footer>

<!-- ======= Vendor JS Files ======= -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<!-- Add other vendor JS if the template uses them -->

<!-- ======= Main JS File ======= -->
<script src="assets/js/main.js"></script>

<div class="scroll-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</div>
<footer id="footer" style="background:#1f1f24;color:white;padding:40px 0;text-align:center;">

  <div class="container">

    <h3>FoodFusion</h3>
    <p>Cook • Share • Explore Delicious Recipes</p>

    <!-- Social Media -->
    <div style="margin:20px 0;">

      <a href="https://wa.me/919999999999" target="_blank" style="margin:10px;">
        <img src="pngwing.com (1).png" width="40">
      </a>

      <a href="https://www.instagram.com/foodf_usion18/" target="_blank" style="margin:10px;">
        <img src="pngwing.com.png" width="40">
      </a>

      <a href="https://facebook.com" target="_blank" style="margin:10px;">
        <img src="facebook.png" width="40">
      </a>

    </div>

    <!-- Contact Info -->
    <p>📞 +91 9876543210</p>
    <p>📧 contact@foodfusion.com</p>

    <p style="margin-top:20px;font-size:14px;">
      © 2026 FoodFusion. All Rights Reserved.
    </p>

  </div>

</footer>


<?php include 'cookie_consent.php'; ?>
</body>
</html>