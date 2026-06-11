<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "db.php";
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

include "db.php";

/* ── Filters from GET ── */
$search    = trim($_GET['search']    ?? '');
$cuisine   = trim($_GET['cuisine']   ?? '');
$diet      = trim($_GET['diet']      ?? '');
$diff      = trim($_GET['diff']      ?? '');
$sort      = trim($_GET['sort']      ?? 'newest');

/* ── Build dynamic query ── */
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(title LIKE ? OR ingredients LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($cuisine !== '') {
    $where[]  = "cuisine_type = ?";
    $params[] = $cuisine;
    $types   .= 's';
}
if ($diet !== '') {
    $where[]  = "dietary_preference = ?";
    $params[] = $diet;
    $types   .= 's';
}
if ($diff !== '') {
    $where[]  = "difficulty = ?";
    $params[] = $diff;
    $types   .= 's';
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$orderSQL = match($sort) {
    'az'     => "ORDER BY title ASC",
    'za'     => "ORDER BY title DESC",
    'oldest' => "ORDER BY created_at ASC",
    default  => "ORDER BY created_at DESC",
};

$sql  = "SELECT * FROM recipes $whereSQL $orderSQL";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result  = $stmt->get_result();
$recipes = $result->fetch_all(MYSQLI_ASSOC);
$total   = count($recipes);

/* ── Distinct filter options ── */
$cuisines = $conn->query("SELECT DISTINCT cuisine_type FROM recipes ORDER BY cuisine_type")->fetch_all(MYSQLI_ASSOC);
$diets    = $conn->query("SELECT DISTINCT dietary_preference FROM recipes ORDER BY dietary_preference")->fetch_all(MYSQLI_ASSOC);

/* ── Difficulty badge colour helper ── */
function diffBadge(string $d): string {
    return match(strtolower($d)) {
        'easy'   => 'success',
        'medium' => 'warning',
        'hard'   => 'danger',
        default  => 'secondary',
    };
}

/* ── Cuisine emoji helper ── */
function cuisineEmoji(string $c): string {
    return match(strtolower($c)) {
        'indian'    => '🇮🇳',
        'italian'   => '🇮🇹',
        'chinese'   => '🇨🇳',
        'mexican'   => '🇲🇽',
        'american'  => '🇺🇸',
        'dessert'   => '🍮',
        'beverage'  => '🥤',
        'snack'     => '🥪',
        default     => '🍽️',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Vendor CSS -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS -->
  <link href="assets/css/main.css" rel="stylesheet">

  <title>Recipes – FoodFusion</title>

  <style>
    /* ── Page Banner ── */
    .page-banner {
      position: relative;
      height: 360px;
      display: flex;
      align-items: center;
      overflow: hidden;
    }
    .page-banner img.banner-bg {
      position: absolute;
      inset: 0; width: 100%; height: 100%;
      object-fit: cover; object-position: center;
    }
    .page-banner .banner-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to right, rgba(0,0,0,0.75) 40%, rgba(0,0,0,0.2));
    }
    .page-banner .banner-content { position: relative; z-index: 2; }
    .page-banner .breadcrumb-item a { color: var(--color-primary, #ce1212); }
    .page-banner .breadcrumb-item.active { color: rgba(255,255,255,0.65); }
    .page-banner .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.35); }

    /* ── Section label ── */
    .section-title-sm {
      font-size: 12px; font-weight: 600;
      letter-spacing: 2.5px; text-transform: uppercase;
      color: var(--color-primary, #ce1212);
      display: flex; align-items: center; gap: 10px;
    }
    .section-title-sm::before {
      content: '';
      display: inline-block;
      width: 28px; height: 2px;
      background: var(--color-primary, #ce1212);
    }

    /* ── Filter Bar ── */
    .filter-bar {
      background: #fff;
      border-bottom: 1px solid #f0f0f0;
      position: sticky; top: 70px; z-index: 90;
      box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }
    .filter-bar .form-select,
    .filter-bar .form-control {
      border-radius: 50px;
      border: 1.5px solid #e8e8e8;
      font-size: 13px;
      padding: 8px 18px;
      transition: border-color 0.2s;
    }
    .filter-bar .form-select:focus,
    .filter-bar .form-control:focus {
      border-color: var(--color-primary, #ce1212);
      box-shadow: 0 0 0 3px rgba(206,18,18,0.08);
    }
    .filter-bar .btn-filter {
      background: var(--color-primary, #ce1212);
      color: #fff; border: none;
      border-radius: 50px;
      padding: 8px 24px;
      font-size: 13px; font-weight: 600;
      transition: background 0.2s, transform 0.15s;
    }
    .filter-bar .btn-filter:hover { background: #a30f0f; transform: scale(1.03); }
    .filter-bar .btn-reset {
      border: 1.5px solid #ddd; background: transparent;
      border-radius: 50px; padding: 8px 20px;
      font-size: 13px; color: #888;
      transition: border-color 0.2s, color 0.2s;
    }
    .filter-bar .btn-reset:hover { border-color: var(--color-primary, #ce1212); color: var(--color-primary, #ce1212); }

    /* ── Result count chip ── */
    .result-chip {
      display: inline-flex; align-items: center; gap: 6px;
      background: #fff4f4; border: 1px solid #fcc;
      color: var(--color-primary, #ce1212);
      font-size: 12px; font-weight: 600;
      padding: 4px 14px; border-radius: 50px;
    }

    /* ── Recipe Card ── */
    .recipe-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
      display: flex; flex-direction: column;
    }
    .recipe-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 14px 36px rgba(0,0,0,0.13);
    }
    .recipe-card .card-thumb {
      position: relative;
      height: 200px;
      background: linear-gradient(135deg, #fef5f5, #fff0e0);
      display: flex; align-items: center; justify-content: center;
      font-size: 72px;
      overflow: hidden;
      flex-shrink: 0;
    }
    .recipe-card .card-thumb .thumb-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.18), transparent);
    }
    .recipe-card .card-thumb .cuisine-badge {
      position: absolute; top: 12px; left: 12px;
      background: rgba(255,255,255,0.92);
      border-radius: 50px; padding: 4px 12px;
      font-size: 11px; font-weight: 600;
      color: var(--color-secondary, #37373f);
      backdrop-filter: blur(4px);
    }
    .recipe-card .card-thumb .diff-badge {
      position: absolute; top: 12px; right: 12px;
      border-radius: 50px; padding: 4px 12px;
      font-size: 11px; font-weight: 700;
    }
    .recipe-card .card-body-inner {
      padding: 20px 22px 24px;
      display: flex; flex-direction: column; flex: 1;
    }
    .recipe-card .card-title {
      font-size: 17px; font-weight: 700;
      color: var(--color-secondary, #37373f);
      margin-bottom: 8px; line-height: 1.3;
    }
    .recipe-card .card-meta {
      font-size: 12px; color: #aaa;
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }
    .recipe-card .card-meta span { display: flex; align-items: center; gap: 4px; }
    .recipe-card .card-ingredients {
      font-size: 13px; color: #888;
      line-height: 1.6;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin-bottom: 16px;
      flex: 1;
    }
    .recipe-card .btn-view {
      display: block; width: 100%;
      background: var(--color-primary, #ce1212);
      color: #fff; text-decoration: none;
      text-align: center; padding: 10px;
      border-radius: 50px; font-size: 13px; font-weight: 600;
      transition: background 0.2s, transform 0.15s;
      margin-top: auto;
    }
    .recipe-card .btn-view:hover { background: #a30f0f; transform: scale(1.02); }

    /* ── Empty state ── */
    .empty-state {
      text-align: center; padding: 80px 20px;
    }
    .empty-state .empty-icon { font-size: 72px; margin-bottom: 20px; }
    .empty-state h4 { font-weight: 700; color: #444; margin-bottom: 8px; }
    .empty-state p { color: #aaa; font-size: 14px; }

    /* ── Add Recipe Modal ── */
    .modal-header { border-bottom: 2px solid #f5f5f5; }
    .modal-footer { border-top: 2px solid #f5f5f5; }
    .modal .form-label { font-size: 13px; font-weight: 600; color: #555; }
    .modal .form-control,
    .modal .form-select {
      border-radius: 10px;
      border: 1.5px solid #e8e8e8;
      font-size: 14px;
      transition: border-color 0.2s;
    }
    .modal .form-control:focus,
    .modal .form-select:focus {
      border-color: var(--color-primary, #ce1212);
      box-shadow: 0 0 0 3px rgba(206,18,18,0.08);
    }
    .btn-add-recipe {
      background: var(--color-primary, #ce1212);
      color: #fff; border: none;
      border-radius: 50px; padding: 10px 28px;
      font-size: 14px; font-weight: 600;
      transition: background 0.2s, transform 0.15s;
    }
    .btn-add-recipe:hover { background: #a30f0f; transform: scale(1.03); }

    /* ── Toast ── */
    .toast-container { z-index: 9999; }

    /* ── CTA strip ── */
    .cta-strip { background: var(--color-primary, #ce1212); }
    .cta-strip h2 { color: #fff; font-weight: 800; }
    .cta-strip p  { color: rgba(255,255,255,0.82); }
    .cta-strip .btn-light {
      font-weight: 600; color: var(--color-primary, #ce1212);
      border: none; padding: 12px 36px;
      border-radius: 50px; transition: transform 0.2s;
    }
    .cta-strip .btn-light:hover { transform: scale(1.04); }

    @media (max-width: 768px) {
      .page-banner { height: 260px; }
      .filter-bar { position: static; }
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
        <li><a href="about.php">About</a></li>
        <li><a href="educational.php">Educational Resources</a></li>
        <li><a href="recipes.php" class="active">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="community.php">Community</a></li>
        <li><a href="contact.php">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <a class="btn-getstarted" href="logout.php">Logout</a>

  </div>
</header>
<!-- End Header -->

<?php
/* ── Handle Add Recipe POST ── */
$toast_msg  = '';
$toast_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipe'])) {

    $title   = trim($_POST['title'] ?? '');
    $cuisine = trim($_POST['cuisine_type'] ?? '');
    $diet    = trim($_POST['dietary_preference'] ?? '');
    $dif     = trim($_POST['difficulty'] ?? '');
    $ings    = trim($_POST['ingredients'] ?? '');
    $instr   = trim($_POST['instructions'] ?? '');

    if ($title && $cuisine && $diet && $dif && $ings && $instr) {

        $image_url = '';

        // 👉 Upload image AFTER validation
        if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] === 0) {

            $uploadDir = 'uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . basename($_FILES['recipe_image']['name']);
            $targetFile = $uploadDir . $fileName;

            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['recipe_image']['tmp_name'], $targetFile)) {
                    $image_url = $targetFile;
                } else {
                    $toast_msg = "Image upload failed!";
                    $toast_type = "danger";
                }
            } else {
                $toast_msg = "Only JPG, PNG allowed!";
                $toast_type = "warning";
            }
        }

        if (empty($toast_msg)) {
            $uid = null;

            $uStmt = $conn->prepare("SELECT user_id FROM users WHERE first_name = ? LIMIT 1");
            $uStmt->bind_param("s", $_SESSION['user']);
            $uStmt->execute();
            $uRes = $uStmt->get_result();

            if ($uRow = $uRes->fetch_assoc()) $uid = $uRow['user_id'];

            $ins = $conn->prepare(
                "INSERT INTO recipes (user_id, title, cuisine_type, dietary_preference, difficulty, ingredients, instructions, image_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $ins->bind_param("isssssss", $uid, $title, $cuisine, $diet, $dif, $ings, $instr, $image_url);

            if ($ins->execute()) {
                header("Location: recipes.php?added=1");
                exit();
            }
        }

    } else {
        $toast_msg  = "Please fill in all fields.";
        $toast_type = 'warning';
    }
}


    if ( $cuisine && $diet && $dif && $ings && $instr) {
        $uid = null;
        $uStmt = $conn->prepare("SELECT user_id FROM users WHERE first_name = ? LIMIT 1");
        $uStmt->bind_param("s", $_SESSION['user']);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        if ($uRow = $uRes->fetch_assoc()) $uid = $uRow['user_id'];

        $ins = $conn->prepare(
            "INSERT INTO recipes (user_id, title, cuisine_type, dietary_preference, difficulty, ingredients, instructions, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param("isssssss", $uid, $title, $cuisine, $diet, $dif, $ings, $instr, $image_url);

        if ($ins->execute()) {
            $loc = "recipes.php";
            $query = array_filter([
                'search'  => $search,
                'cuisine' => $cuisine,
                'diet'    => $diet,
                'diff'    => $diff,
                'sort'    => $sort,
                'added'   => 1
            ]);
            $loc .= '?' . http_build_query($query);
            header("Location: $loc");
            exit();
        } else {
            $toast_msg  = "Error saving recipe. Please try again.";
            $toast_type = 'danger';
        }
    } else {
        $toast_msg  = "Please fill in all fields.";
        $toast_type = 'warning';
    }


if (isset($_GET['added'])) {
    $toast_msg  = "Recipe added successfully! 🎉";
    $toast_type = 'success';
}
?>

<main class="main">

  <!-- ======= Page Banner ======= -->
  <section class="page-banner">
    <img src="alex-munsell-Yr4n8O_3UPc-unsplash.jpg" alt="Recipe Collection" class="banner-bg">
    <div class="banner-overlay"></div>
    <div class="banner-content container">
      <div data-aos="fade-up">
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="home1.php">Home</a></li>
            <li class="breadcrumb-item active">Recipes</li>
          </ol>
        </nav>
        <h1 class="display-4 fw-bold text-white mb-2">
          Recipe <span style="color:var(--color-primary,#ce1212);">Collection</span>
        </h1>
        <p class="lead text-white-50 mb-0" style="max-width:500px;">
          Discover <?= $total ?> delicious recipes from cuisines around the world.
        </p>
      </div>
    </div>
  </section>
  <!-- End Banner -->

  <!-- ======= Filter Bar ======= -->
  <div class="filter-bar py-3">
    <div class="container">
      <form method="GET" action="recipes.php" id="filterForm">
        <div class="row g-2 align-items-center">

          <!-- Search -->
          <div class="col-12 col-md-3">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0" style="border-radius:50px 0 0 50px;border:1.5px solid #e8e8e8;">
                <i class="bi bi-search text-muted" style="font-size:13px;"></i>
              </span>
              <input type="text" name="search" class="form-control border-start-0 ps-0"
                     style="border-radius:0 50px 50px 0;"
                     placeholder="Search recipes…"
                     value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>

          <!-- Cuisine -->
          <div class="col-6 col-md-2">
            <select name="cuisine" class="form-select">
              <option value="">All Cuisines</option>
              <?php foreach ($cuisines as $c): ?>
                <option value="<?= htmlspecialchars($c['cuisine_type']) ?>"
                  <?= $cuisine === $c['cuisine_type'] ? 'selected' : '' ?>>
                  <?= cuisineEmoji($c['cuisine_type']) ?> <?= htmlspecialchars($c['cuisine_type']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Diet -->
          <div class="col-6 col-md-2">
            <select name="diet" class="form-select">
              <option value="">All Diets</option>
              <?php foreach ($diets as $d): ?>
                <option value="<?= htmlspecialchars($d['dietary_preference']) ?>"
                  <?= $diet === $d['dietary_preference'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($d['dietary_preference']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Difficulty -->
          <div class="col-6 col-md-2">
            <select name="diff" class="form-select">
              <option value="">All Levels</option>
              <option value="Easy"   <?= $diff === 'Easy'   ? 'selected' : '' ?>>🟢 Easy</option>
              <option value="Medium" <?= $diff === 'Medium' ? 'selected' : '' ?>>🟡 Medium</option>
              <option value="Hard"   <?= $diff === 'Hard'   ? 'selected' : '' ?>>🔴 Hard</option>
            </select>
          </div>

          <!-- Sort -->
          <div class="col-6 col-md-2">
            <select name="sort" class="form-select">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
              <option value="az"     <?= $sort === 'az'     ? 'selected' : '' ?>>A → Z</option>
              <option value="za"     <?= $sort === 'za'     ? 'selected' : '' ?>>Z → A</option>
            </select>
          </div>

          <!-- Buttons -->
          <div class="col-12 col-md-1 d-flex gap-2">
            <button type="submit" class="btn-filter flex-fill">
              <i class="bi bi-funnel-fill me-1"></i>Filter
            </button>
          </div>

        </div>

        <!-- Active filters row -->
        <?php if ($search || $cuisine || $diet || $diff): ?>
        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
          <span class="text-muted" style="font-size:12px;">Active filters:</span>
          <?php if ($search):  ?><span class="result-chip">🔍 <?= htmlspecialchars($search) ?></span><?php endif; ?>
          <?php if ($cuisine): ?><span class="result-chip"><?= cuisineEmoji($cuisine) ?> <?= htmlspecialchars($cuisine) ?></span><?php endif; ?>
          <?php if ($diet):    ?><span class="result-chip">🥗 <?= htmlspecialchars($diet) ?></span><?php endif; ?>
          <?php if ($diff):    ?><span class="result-chip">📊 <?= htmlspecialchars($diff) ?></span><?php endif; ?>
          <a href="recipes.php" class="btn-reset ms-1">✕ Clear all</a>
        </div>
        <?php endif; ?>

      </form>
    </div>
  </div>
  <!-- End Filter Bar -->

  <!-- ======= Recipes Grid Section ======= -->
  <section class="section">
    <div class="container">

      <!-- Top row: count + Add Recipe button -->
      <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
        <div>
          <span class="section-title-sm">Our Collection</span>
          <h2 class="fw-bold mt-1 mb-0" style="font-size:clamp(22px,3vw,34px);">
            <?php if ($total > 0): ?>
              <?= $total ?> Recipe<?= $total !== 1 ? 's' : '' ?> Found
            <?php else: ?>
              No Recipes Found
            <?php endif; ?>
          </h2>
        </div>
        <button class="btn-add-recipe" data-bs-toggle="modal" data-bs-target="#addRecipeModal">
          <i class="bi bi-plus-lg me-1"></i> Add Recipe
        </button>
      </div>

      <!-- Grid -->
      <?php if ($total > 0): ?>
      <div class="row g-4">
        <?php foreach ($recipes as $i => $r): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3"
             data-aos="fade-up"
             data-aos-delay="<?= min($i % 8, 4) * 80 ?>">
          <div class="recipe-card">

            <!-- Thumb -->
            <div class="card-thumb">
  <?php if (!empty($r['image_url'])): ?>
    <img src="<?= htmlspecialchars($r['image_url']) ?>" alt="<?= htmlspecialchars($r['title']) ?>" 
         style="width:100%; height:100%; object-fit:cover;">
  <?php else: ?>
    <span><?= cuisineEmoji($r['cuisine_type']) ?></span>
  <?php endif; ?>

  <div class="thumb-overlay"></div>
  <span class="cuisine-badge"><?= htmlspecialchars($r['cuisine_type']) ?></span>
  <span class="diff-badge badge bg-<?= diffBadge($r['difficulty']) ?>">
    <?= htmlspecialchars($r['difficulty']) ?>
  </span>
</div>
            <!-- Body -->
            <div class="card-body-inner">
              <h5 class="card-title"><?= htmlspecialchars($r['title']) ?></h5>

              <div class="card-meta">
                <span><i class="bi bi-tag"></i> <?= htmlspecialchars($r['dietary_preference']) ?></span>
                <span><i class="bi bi-clock"></i>
                  <?php
                    $d = strtolower($r['difficulty']);
                    echo $d === 'easy' ? '20–30 min' : ($d === 'medium' ? '30–60 min' : '60+ min');
                  ?>
                </span>
              </div>

              <p class="card-ingredients">
                <i class="bi bi-basket2 me-1" style="color:var(--color-primary,#ce1212);"></i>
                <?= htmlspecialchars($r['ingredients']) ?>
              </p>

              <a href="recipe_details.php?id=<?= $r['recipe_id'] ?>" class="btn-view">
                View Recipe &nbsp;<i class="bi bi-arrow-right"></i>
              </a>
            </div>

          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <!-- Empty State -->
      <div class="empty-state" data-aos="fade-up">
        <div class="empty-icon">🍽️</div>
        <h4>No recipes match your filters</h4>
        <p>Try adjusting your search or filters, or add a brand-new recipe!</p>
        <a href="recipes.php" class="btn btn-outline-danger rounded-pill px-4 py-2 mt-3 me-2">
          Clear Filters
        </a>
        <button class="btn btn-danger rounded-pill px-4 py-2 mt-3"
                data-bs-toggle="modal" data-bs-target="#addRecipeModal">
          <i class="bi bi-plus-lg me-1"></i> Add Recipe
        </button>
      </div>
      <?php endif; ?>

    </div>
  </section>
  <!-- End Recipes Grid -->

  <!-- ======= CTA Strip ======= -->
  <section class="cta-strip py-5" data-aos="zoom-in">
    <div class="container text-center">
      <h2 class="fw-bold mb-2">Have a Recipe to Share?</h2>
      <p class="mb-4">Add your own dish to the FoodFusion community cookbook!</p>
      <button class="btn btn-light rounded-pill px-5 py-2 fw-600 fs-6"
              data-bs-toggle="modal" data-bs-target="#addRecipeModal">
        <i class="bi bi-plus-circle me-2"></i>Add Your Recipe
      </button>
    </div>
  </section>
  <!-- End CTA -->

</main>

<!-- ======= Add Recipe Modal ======= -->
<div class="modal fade" id="addRecipeModal" tabindex="-1" aria-labelledby="addRecipeLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:18px;display:flex;flex-direction:column;max-height:90vh;">

      <div class="modal-header px-4 pt-4">
        <h5 class="modal-title fw-bold" id="addRecipeLabel">
          <i class="bi bi-journal-plus me-2" style="color:var(--color-primary,#ce1212);"></i>
          Add a New Recipe
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" enctype="multipart/form-data" action="recipes.php<?= ($search||$cuisine||$diet||$diff||$sort!=='newest') ? '?'.http_build_query(array_filter(['search'=>$search,'cuisine'=>$cuisine,'diet'=>$diet,'diff'=>$diff,'sort'=>$sort])) : '' ?>">
        <div class="modal-body px-4 py-3" style="overflow-y:auto;flex:1 1 auto;">

          <div class="row g-3">

            <!-- Title -->
            <div class="col-12"> 
    <label class="form-label">Recipe Title *</label> 
    <input type="text" name="title" class="form-control" placeholder="e.g. Butter Chicken Masala" required> 
</div>
<div class="col-12 mt-3"> 
    <label class="form-label">Image URL</label> 
    <input type="file" name="recipe_image" class="form-control" accept="image/*"> 
</div>

            <!-- Cuisine + Diet -->
            <div class="col-md-6">
              <label class="form-label">Cuisine Type *</label>
              <select name="cuisine_type" class="form-select" required>
                <option value="">Select cuisine…</option>
                <option>Indian</option>
                <option>Italian</option>
                <option>Chinese</option>
                <option>Mexican</option>
                <option>American</option>
                <option>Dessert</option>
                <option>Beverage</option>
                <option>Snack</option>
                <option>Other</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Dietary Preference *</label>
              <select name="dietary_preference" class="form-select" required>
                <option value="">Select preference…</option>
                <option>Vegetarian</option>
                <option>Non-Vegetarian</option>
                <option>Vegan</option>
                <option>Gluten-Free</option>
              </select>
            </div>

            <!-- Difficulty -->
            <div class="col-md-6">
              <label class="form-label">Difficulty Level *</label>
              <select name="difficulty" class="form-select" required>
                <option value="">Select level…</option>
                <option value="Easy">🟢 Easy</option>
                <option value="Medium">🟡 Medium</option>
                <option value="Hard">🔴 Hard</option>
              </select>
            </div>

            <!-- Ingredients -->
            <div class="col-12">
              <label class="form-label">Ingredients * <small class="text-muted fw-normal">(comma-separated)</small></label>
              <textarea name="ingredients" class="form-control" rows="3"
                        placeholder="e.g. Chicken, butter, cream, garlic, spices" required></textarea>
            </div>

            <!-- Instructions -->
            <div class="col-12">
              <label class="form-label">Instructions * <small class="text-muted fw-normal">(use full stops to separate steps)</small></label>
              <textarea name="instructions" class="form-control" rows="5"
                        placeholder="Heat oil in a pan. Add garlic and fry for 1 minute. Add chicken and cook until golden…" required></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer px-4 pb-4 border-0" style="flex-shrink:0;background:#fff;border-radius:0 0 18px 18px;">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_recipe" class="btn-add-recipe">
            <i class="bi bi-send me-1"></i> Submit Recipe
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
<!-- End Modal -->

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

<!-- Scroll Top -->
<div class="scroll-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</div>

<!-- Toast Notification -->
<?php if ($toast_msg): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="mainToast" class="toast align-items-center text-bg-<?= $toast_type ?> border-0 show" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-500"><?= $toast_msg ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ======= Vendor JS ======= -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

<!-- Main JS -->
<script src="assets/js/main.js"></script>

<script>
  // Auto-dismiss toast after 4s
  const toastEl = document.getElementById('mainToast');
  if (toastEl) {
    setTimeout(() => {
      const t = bootstrap.Toast.getOrCreateInstance(toastEl);
      t.hide();
    }, 4000);
  }

  // Live search — auto-submit on typing pause
  let searchTimer;
  const searchInput = document.querySelector('input[name="search"]');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        document.getElementById('filterForm').submit();
      }, 600);
    });
  }

  // Auto-submit selects on change
  document.querySelectorAll('#filterForm select').forEach(sel => {
    sel.addEventListener('change', () => {
      document.getElementById('filterForm').submit();
    });
  });
</script>
<?php include 'cookie_consent.php'; ?>
</body>
</html>