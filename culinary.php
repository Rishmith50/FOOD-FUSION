<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

include "db.php";

/* ── Handle Add Resource POST ── */
$toast_msg  = '';
$toast_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $title    = trim($_POST['title']         ?? '');
    $rtype    = trim($_POST['resource_type'] ?? '');
    $file_url = trim($_POST['file_url']      ?? '');
    $desc     = trim($_POST['description']   ?? '');

    if ($title && $rtype) {
        $ins = $conn->prepare(
            "INSERT INTO culinary_resources (title, resource_type, file_url, description)
             VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("ssss", $title, $rtype, $file_url, $desc);
        if ($ins->execute()) {
            header("Location: culinary_resources.php?added=1");
            exit();
        } else {
            $toast_msg  = "Error saving resource. Please try again.";
            $toast_type = 'danger';
        }
    } else {
        $toast_msg  = "Please fill in all required fields.";
        $toast_type = 'warning';
    }
}

if (isset($_GET['added'])) {
    $toast_msg  = "Resource added successfully!";
    $toast_type = 'success';
}

/* ── Filters ── */
$filter_type = trim($_GET['type']   ?? '');
$search      = trim($_GET['search'] ?? '');
$sort        = trim($_GET['sort']   ?? 'newest');

$where  = [];
$params = [];
$types  = '';

if ($filter_type !== '') {
    $where[]  = "resource_type = ?";
    $params[] = $filter_type;
    $types   .= 's';
}
if ($search !== '') {
    $where[]  = "(title LIKE ? OR description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

if ($sort === 'az')         $orderSQL = "ORDER BY title ASC";
elseif ($sort === 'za')     $orderSQL = "ORDER BY title DESC";
elseif ($sort === 'oldest') $orderSQL = "ORDER BY created_at ASC";
else                        $orderSQL = "ORDER BY created_at DESC";

$sql  = "SELECT * FROM culinary_resources $whereSQL $orderSQL";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result    = $stmt->get_result();
$resources = $result->fetch_all(MYSQLI_ASSOC);
$total     = count($resources);

/* ── Count per type for stat cards ── */
$counts = ['Recipe Card' => 0, 'Tutorial' => 0, 'Video' => 0];
$cRes   = $conn->query("SELECT resource_type, COUNT(*) as cnt FROM culinary_resources GROUP BY resource_type");
while ($cRow = $cRes->fetch_assoc()) {
    if (isset($counts[$cRow['resource_type']])) $counts[$cRow['resource_type']] = (int)$cRow['cnt'];
}
$totalAll = array_sum($counts);

/* ── Helpers ── */
function typeBadgeClass($t) {
    if ($t === 'Recipe Card') return 'danger';
    if ($t === 'Tutorial')    return 'warning text-dark';
    if ($t === 'Video')       return 'primary';
    return 'secondary';
}
function typeEmoji($t) {
    if ($t === 'Recipe Card') return '📋';
    if ($t === 'Tutorial')    return '🎓';
    if ($t === 'Video')       return '🎬';
    return '📄';
}
function typeBg($t) {
    if ($t === 'Recipe Card') return 'linear-gradient(135deg,#fff0f0,#ffe0e0)';
    if ($t === 'Tutorial')    return 'linear-gradient(135deg,#fffbee,#fff0cc)';
    return 'linear-gradient(135deg,#eef4ff,#ddeeff)';
}
function isVideo($url) {
    if (!$url) return false;
    $u = strtolower($url);
    return str_contains($u,'youtube') || str_contains($u,'youtu.be') || str_contains($u,'vimeo') || str_ends_with($u,'.mp4');
}
function isDownloadable($url) {
    if (!$url) return false;
    return in_array(strtolower(pathinfo($url, PATHINFO_EXTENSION)), ['pdf','png','jpg','jpeg','zip','docx','pptx']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <title>Culinary Resources – FoodFusion</title>

  <style>
    .page-banner { position:relative; height:400px; display:flex; align-items:center; overflow:hidden; }
    .page-banner img.banner-bg { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center; }
    .page-banner .banner-overlay { position:absolute; inset:0; background:linear-gradient(to right,rgba(0,0,0,.82) 45%,rgba(0,0,0,.2)); }
    .page-banner .banner-content { position:relative; z-index:2; }
    .page-banner .breadcrumb-item a { color:var(--color-primary,#ce1212); }
    .page-banner .breadcrumb-item.active { color:rgba(255,255,255,.65); }
    .page-banner .breadcrumb-item+.breadcrumb-item::before { color:rgba(255,255,255,.35); }

    .section-title-sm { font-size:12px; font-weight:600; letter-spacing:2.5px; text-transform:uppercase; color:var(--color-primary,#ce1212); display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .section-title-sm::before { content:''; display:inline-block; width:28px; height:2px; background:var(--color-primary,#ce1212); }

    .stat-card { background:#fff; border-radius:16px; padding:28px 20px; text-align:center; box-shadow:0 2px 16px rgba(0,0,0,.06); border-bottom:4px solid transparent; transition:transform .3s,box-shadow .3s,border-color .3s; text-decoration:none; display:block; color:inherit; }
    .stat-card:hover,.stat-card.active-filter { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.10); }
    .stat-card.active-filter { border-bottom-color:var(--color-primary,#ce1212); }
    .stat-card .stat-icon { font-size:38px; margin-bottom:10px; display:block; }
    .stat-card .stat-num  { font-size:38px; font-weight:800; color:var(--color-primary,#ce1212); line-height:1; }
    .stat-card .stat-lbl  { font-size:12px; color:#999; letter-spacing:1px; text-transform:uppercase; margin-top:6px; }

    .filter-bar { background:#fff; border-bottom:1px solid #f0f0f0; position:sticky; top:70px; z-index:90; box-shadow:0 2px 12px rgba(0,0,0,.05); }
    .filter-bar .form-select,.filter-bar .form-control { border-radius:50px; border:1.5px solid #e8e8e8; font-size:13px; padding:8px 18px; transition:border-color .2s; }
    .filter-bar .form-select:focus,.filter-bar .form-control:focus { border-color:var(--color-primary,#ce1212); box-shadow:0 0 0 3px rgba(206,18,18,.08); }
    .btn-filter { background:var(--color-primary,#ce1212); color:#fff; border:none; border-radius:50px; padding:8px 24px; font-size:13px; font-weight:600; transition:background .2s,transform .15s; }
    .btn-filter:hover { background:#a30f0f; transform:scale(1.03); }
    .btn-reset { border:1.5px solid #ddd; background:transparent; border-radius:50px; padding:8px 20px; font-size:13px; color:#888; transition:border-color .2s,color .2s; text-decoration:none; display:inline-block; }
    .btn-reset:hover { border-color:var(--color-primary,#ce1212); color:var(--color-primary,#ce1212); }
    .result-chip { display:inline-flex; align-items:center; gap:6px; background:#fff4f4; border:1px solid #fcc; color:var(--color-primary,#ce1212); font-size:12px; font-weight:600; padding:4px 14px; border-radius:50px; }

    .resource-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,.07); transition:transform .3s,box-shadow .3s; height:100%; display:flex; flex-direction:column; }
    .resource-card:hover { transform:translateY(-6px); box-shadow:0 14px 36px rgba(0,0,0,.12); }
    .resource-card .card-thumb { height:190px; display:flex; align-items:center; justify-content:center; font-size:68px; position:relative; flex-shrink:0; }
    .resource-card .type-label { position:absolute; top:12px; left:12px; font-size:11px; font-weight:700; padding:5px 14px; border-radius:50px; }
    .resource-card .card-body-inner { padding:22px 22px 24px; display:flex; flex-direction:column; flex:1; }
    .resource-card .card-title { font-size:16px; font-weight:700; color:var(--color-secondary,#37373f); margin-bottom:8px; line-height:1.3; }
    .resource-card .card-meta { font-size:12px; color:#bbb; display:flex; align-items:center; gap:6px; margin-bottom:10px; }
    .resource-card .card-desc { font-size:13px; color:#888; line-height:1.7; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; flex:1; margin-bottom:16px; }

    .btn-action { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:11px; border-radius:50px; font-size:13px; font-weight:600; text-decoration:none; border:none; transition:background .2s,transform .15s; cursor:pointer; }
    .btn-action.primary { background:var(--color-primary,#ce1212); color:#fff; }
    .btn-action.primary:hover { background:#a30f0f; transform:scale(1.02); color:#fff; }
    .btn-action.outline { background:transparent; border:1.5px solid var(--color-primary,#ce1212); color:var(--color-primary,#ce1212); }
    .btn-action.outline:hover { background:var(--color-primary,#ce1212); color:#fff; }
    .btn-action.disabled { background:#f5f5f5; color:#bbb; cursor:default; }

    .how-card { background:#fff; border-radius:16px; padding:36px 28px; height:100%; box-shadow:0 2px 16px rgba(0,0,0,.06); border-top:4px solid var(--color-primary,#ce1212); text-align:center; transition:transform .3s,box-shadow .3s; }
    .how-card:hover { transform:translateY(-5px); box-shadow:0 12px 32px rgba(0,0,0,.10); }
    .how-card .how-icon { font-size:48px; margin-bottom:18px; display:block; }
    .how-card h5 { font-weight:700; margin-bottom:10px; color:var(--color-secondary,#37373f); }
    .how-card p  { font-size:14px; color:#888; line-height:1.75; margin:0 0 18px; }

    .technique-chip { display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); border-radius:50px; padding:8px 18px; font-size:13px; color:rgba(255,255,255,.85); transition:background .2s,border-color .2s; }
    .technique-chip:hover { background:rgba(206,18,18,.3); border-color:rgba(206,18,18,.5); }

    .empty-state { text-align:center; padding:80px 20px; }
    .empty-state .empty-icon { font-size:72px; margin-bottom:20px; }
    .empty-state h4 { font-weight:700; color:#444; margin-bottom:8px; }
    .empty-state p  { color:#aaa; font-size:14px; }

    .modal .form-label { font-size:13px; font-weight:600; color:#555; }
    .modal .form-control,.modal .form-select { border-radius:10px; border:1.5px solid #e8e8e8; font-size:14px; transition:border-color .2s; }
    .modal .form-control:focus,.modal .form-select:focus { border-color:var(--color-primary,#ce1212); box-shadow:0 0 0 3px rgba(206,18,18,.08); }
    .btn-submit-modal { background:var(--color-primary,#ce1212); color:#fff; border:none; border-radius:50px; padding:10px 28px; font-size:14px; font-weight:600; transition:background .2s,transform .15s; }
    .btn-submit-modal:hover { background:#a30f0f; transform:scale(1.03); }

    .cta-strip { background:var(--color-primary,#ce1212); }
    .cta-strip h2 { color:#fff; font-weight:800; }
    .cta-strip p  { color:rgba(255,255,255,.82); }
    .cta-strip .btn-light { font-weight:600; color:var(--color-primary,#ce1212); border:none; padding:12px 36px; border-radius:50px; transition:transform .2s; }
    .cta-strip .btn-light:hover { transform:scale(1.04); }
    .toast-container { z-index:9999; }

    @media(max-width:768px) { .page-banner { height:260px; } .filter-bar { position:static; } }
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
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="community.php">Community</a></li>
        <li><a href="contact.php">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>
    <a class="btn-getstarted" href="logout.php">Logout</a>
  </div>
</header>

<main class="main">

  <!-- ======= Banner ======= -->
  <section class="page-banner">
    <img src="pablo-merchan-montes-0nT08Z-MhiE-unsplash.jpg" alt="Culinary Resources" class="banner-bg">
    <div class="banner-overlay"></div>
    <div class="banner-content container">
      <div data-aos="fade-up">
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="home1.php">Home</a></li>
            <li class="breadcrumb-item active">Culinary Resources</li>
          </ol>
        </nav>
        <h1 class="display-4 fw-bold text-white mb-2">
          Culinary <span style="color:var(--color-primary,#ce1212);">Resources</span>
        </h1>
        <p class="lead text-white-50 mb-3" style="max-width:560px;">
          Download recipe cards, follow cooking tutorials and watch instructional videos on kitchen techniques and hacks.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge rounded-pill" style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px);font-size:13px;padding:8px 16px;">📋 Recipe Cards</span>
          <span class="badge rounded-pill" style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px);font-size:13px;padding:8px 16px;">🎓 Tutorials</span>
          <span class="badge rounded-pill" style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px);font-size:13px;padding:8px 16px;">🎬 Instructional Videos</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= Stat Cards ======= -->
  <section class="section light-background py-4">
    <div class="container">
      <div class="row g-4">
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="0">
          <a href="culinary_resources.php" class="stat-card <?= $filter_type==='' ? 'active-filter':'' ?>">
            <span class="stat-icon">📚</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $totalAll ?>" data-purecounter-duration="1"><?= $totalAll ?></div>
            <div class="stat-lbl">All Resources</div>
          </a>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="80">
          <a href="culinary_resources.php?type=Recipe+Card" class="stat-card <?= $filter_type==='Recipe Card' ? 'active-filter':'' ?>">
            <span class="stat-icon">📋</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $counts['Recipe Card'] ?>" data-purecounter-duration="1"><?= $counts['Recipe Card'] ?></div>
            <div class="stat-lbl">Recipe Cards</div>
          </a>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="160">
          <a href="culinary_resources.php?type=Tutorial" class="stat-card <?= $filter_type==='Tutorial' ? 'active-filter':'' ?>">
            <span class="stat-icon">🎓</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $counts['Tutorial'] ?>" data-purecounter-duration="1"><?= $counts['Tutorial'] ?></div>
            <div class="stat-lbl">Tutorials</div>
          </a>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="240">
          <a href="culinary_resources.php?type=Video" class="stat-card <?= $filter_type==='Video' ? 'active-filter':'' ?>">
            <span class="stat-icon">🎬</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $counts['Video'] ?>" data-purecounter-duration="1"><?= $counts['Video'] ?></div>
            <div class="stat-lbl">Videos</div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= Topics Covered ======= -->
  <section class="py-4" style="background:#1a1a1f;">
    <div class="container text-center">
      <p class="text-white-50 mb-3" style="font-size:12px;letter-spacing:2px;text-transform:uppercase;">Kitchen Techniques Covered</p>
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <?php
        $techniques = ['🔪 Knife Skills','🥘 Braising','🍳 Sautéing','🫕 Slow Cooking','🍞 Bread Baking',
                       '🥗 Meal Prep','🧁 Pastry Basics','🔥 Grilling','🫙 Food Preserving','🌿 Herb Usage',
                       '🧄 Flavour Building','🍜 Pasta Making','🥚 Egg Techniques','🫖 Stocks & Sauces'];
        foreach ($techniques as $t) echo "<span class='technique-chip'>$t</span>";
        ?>
      </div>
    </div>
  </section>

  <!-- ======= Filter Bar ======= -->
  <div class="filter-bar py-3">
    <div class="container">
      <form method="GET" action="culinary_resources.php" id="filterForm">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0" style="border-radius:50px 0 0 50px;border:1.5px solid #e8e8e8;">
                <i class="bi bi-search text-muted" style="font-size:13px;"></i>
              </span>
              <input type="text" name="search" class="form-control border-start-0 ps-0"
                     style="border-radius:0 50px 50px 0;" placeholder="Search resources…"
                     value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <select name="type" class="form-select">
              <option value="">All Types</option>
              <option value="Recipe Card" <?= $filter_type==='Recipe Card'?'selected':'' ?>>📋 Recipe Card</option>
              <option value="Tutorial"    <?= $filter_type==='Tutorial'?'selected':'' ?>>🎓 Tutorial</option>
              <option value="Video"       <?= $filter_type==='Video'?'selected':'' ?>>🎬 Video</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="sort" class="form-select">
              <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
              <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
              <option value="az"     <?= $sort==='az'?'selected':'' ?>>A → Z</option>
              <option value="za"     <?= $sort==='za'?'selected':'' ?>>Z → A</option>
            </select>
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn-filter flex-fill"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
            <?php if ($search || $filter_type): ?>
            <a href="culinary.php" class="btn-reset">✕ Clear</a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($search || $filter_type): ?>
        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
          <span class="text-muted" style="font-size:12px;">Active:</span>
          <?php if ($search):      ?><span class="result-chip">🔍 <?= htmlspecialchars($search) ?></span><?php endif; ?>
          <?php if ($filter_type): ?><span class="result-chip"><?= typeEmoji($filter_type) ?> <?= htmlspecialchars($filter_type) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- ======= Resources Grid ======= -->
  <section class="section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
        <div>
          <span class="section-title-sm">Browse &amp; Download</span>
          <h2 class="fw-bold mt-1 mb-0" style="font-size:clamp(22px,3vw,34px);">
            <?= $total ?> Resource<?= $total!==1?'s':'' ?> Found
          </h2>
        </div>
        <button class="btn btn-danger rounded-pill px-4 py-2 fw-600"
                data-bs-toggle="modal" data-bs-target="#addResourceModal">
          <i class="bi bi-plus-lg me-1"></i>Add Resource
        </button>
      </div>

      <?php if ($total > 0): ?>
      <div class="row g-4">
        <?php foreach ($resources as $i => $r): ?>
        <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= min($i%6,3)*80 ?>">
          <div class="resource-card">
            <div class="card-thumb" style="background:<?= typeBg($r['resource_type']) ?>;">
              <span><?= typeEmoji($r['resource_type']) ?></span>
              <span class="type-label badge bg-<?= typeBadgeClass($r['resource_type']) ?>">
                <?= htmlspecialchars($r['resource_type']) ?>
              </span>
            </div>
            <div class="card-body-inner">
              <h5 class="card-title"><?= htmlspecialchars($r['title']) ?></h5>
              <div class="card-meta">
                <i class="bi bi-calendar3"></i><?= date('d M Y', strtotime($r['created_at'])) ?>
              </div>
              <p class="card-desc">
                <?= $r['description'] ? htmlspecialchars($r['description']) : '<em style="color:#ccc;">No description available.</em>' ?>
              </p>
              <?php if ($r['file_url']): ?>
                <?php if (isVideo($r['file_url'])): ?>
                  <a href="<?= htmlspecialchars($r['file_url']) ?>" target="_blank" class="btn-action primary">
                    <i class="bi bi-play-circle-fill"></i>Watch Video
                  </a>
                <?php elseif (isDownloadable($r['file_url'])): ?>
                  <a href="<?= htmlspecialchars($r['file_url']) ?>" download class="btn-action primary">
                    <i class="bi bi-download"></i>Download
                  </a>
                <?php else: ?>
                  <a href="<?= htmlspecialchars($r['file_url']) ?>" target="_blank" class="btn-action outline">
                    <i class="bi bi-box-arrow-up-right"></i>Open Resource
                  </a>
                <?php endif; ?>
              <?php else: ?>
                <span class="btn-action disabled"><i class="bi bi-hourglass-split"></i>Coming Soon</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <div class="empty-state" data-aos="fade-up">
        <div class="empty-icon">📭</div>
        <h4>No resources found</h4>
        <p>Try a different search or filter, or be the first to add one!</p>
        <a href="culinary.php" class="btn btn-outline-danger rounded-pill px-4 py-2 mt-3 me-2">Clear Filters</a>
        <button class="btn btn-danger rounded-pill px-4 py-2 mt-3" data-bs-toggle="modal" data-bs-target="#addResourceModal">
          <i class="bi bi-plus-lg me-1"></i>Add Resource
        </button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ======= How It Works ======= -->
  <section class="section light-background">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-title-sm justify-content-center">How It Works</span>
        <h2 class="fw-bold" style="font-size:clamp(26px,3vw,38px);">Three Ways to Learn Cooking</h2>
        <p class="text-muted mt-2" style="max-width:500px;margin:0 auto;">Every resource is designed to help you cook better at home.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
          <div class="how-card">
            <span class="how-icon">📋</span>
            <h5>Recipe Cards</h5>
            <p>Downloadable, printable cards with ingredients, steps and tips — perfect to keep in your kitchen or save offline for quick reference.</p>
            <a href="culinary.php?type=Recipe+Card" class="btn btn-outline-danger rounded-pill px-4 fw-600" style="font-size:13px;">Browse Cards</a>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
          <div class="how-card">
            <span class="how-icon">🎓</span>
            <h5>Cooking Tutorials</h5>
            <p>Step-by-step written guides covering knife skills, sauce making, doughs, kitchen hacks and essential cooking techniques for every level.</p>
            <a href="culinary.php?type=Tutorial" class="btn btn-outline-danger rounded-pill px-4 fw-600" style="font-size:13px;">Browse Tutorials</a>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
          <div class="how-card">
            <span class="how-icon">🎬</span>
            <h5>Instructional Videos</h5>
            <p>Watch real techniques demonstrated step by step — cook along with expert home chefs and master new skills at your own pace.</p>
            <a href="culinary.php?type=Video" class="btn btn-outline-danger rounded-pill px-4 fw-600" style="font-size:13px;">Browse Videos</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= CTA ======= -->
  <section class="cta-strip py-5" data-aos="zoom-in">
    <div class="container text-center">
      <h2 class="fw-bold mb-2">Have a Resource to Share?</h2>
      <p class="mb-4">Contribute a recipe card, tutorial or instructional video to help the FoodFusion community cook better!</p>
      <button class="btn btn-light rounded-pill px-5 py-2 fw-600 fs-6" data-bs-toggle="modal" data-bs-target="#addResourceModal">
        <i class="bi bi-plus-circle me-2"></i>Add a Resource
      </button>
    </div>
  </section>

</main>

<!-- ======= Modal ======= -->
<div class="modal fade" id="addResourceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
      <div class="modal-header px-4 pt-4 border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-plus-square-fill me-2" style="color:var(--color-primary,#ce1212);"></i>Add a New Culinary Resource
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="culinary_resources.php">
        <div class="modal-body px-4 py-3">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Resource Title *</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Knife Skills for Beginners" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Resource Type *</label>
              <select name="resource_type" class="form-select" required>
                <option value="">Select type…</option>
                <option value="Recipe Card">📋 Recipe Card</option>
                <option value="Tutorial">🎓 Tutorial</option>
                <option value="Video">🎬 Video</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">File / Link URL <small class="text-muted fw-normal">(optional)</small></label>
              <input type="url" name="file_url" class="form-control" placeholder="https://… (PDF, YouTube link, etc.)">
              <div class="form-text">PDF files will show a Download button. YouTube/Vimeo links will show Watch Video.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Description <small class="text-muted fw-normal">(optional)</small></label>
              <textarea name="description" class="form-control" rows="4"
                        placeholder="What cooking technique or kitchen hack does this cover?"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 border-0">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_resource" class="btn-submit-modal"><i class="bi bi-send me-1"></i>Submit Resource</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======= Footer ======= -->
<footer id="footer" style="background:#1f1f24;color:white;padding:40px 0;text-align:center;">
  <div class="container">
    <h3>FoodFusion</h3>
    <p>Cook • Share • Explore Delicious Recipes</p>
    <div style="margin:20px 0;">
      <a href="https://wa.me/919999999999" target="_blank" style="margin:10px;"><img src="pngwing.com (1).png" width="40" alt="WhatsApp"></a>
      <a href="https://www.instagram.com/foodf_usion18/"       target="_blank" style="margin:10px;"><img src="pngwing.com.png"     width="40" alt="Instagram"></a>
      <a href="https://facebook.com"        target="_blank" style="margin:10px;"><img src="facebook.png"        width="40" alt="Facebook"></a>
    </div>
    <p>📞 +91 9876543210</p>
    <p>📧 contact@foodfusion.com</p>
    <p style="margin-top:20px;font-size:14px;">© 2026 FoodFusion. All Rights Reserved.</p>
  </div>
</footer>

<div class="scroll-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</div>

<?php if ($toast_msg): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="mainToast" class="toast align-items-center text-bg-<?= $toast_type ?> border-0 show" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-500"><?= htmlspecialchars($toast_msg) ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/aos/aos.js"></script>
<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
<script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
<script src="assets/js/main.js"></script>
<script>
  const toastEl = document.getElementById('mainToast');
  if (toastEl) setTimeout(() => bootstrap.Toast.getOrCreateInstance(toastEl).hide(), 4000);
  let st;
  const si = document.querySelector('input[name="search"]');
  if (si) si.addEventListener('input', () => { clearTimeout(st); st = setTimeout(() => document.getElementById('filterForm').submit(), 600); });
  document.querySelectorAll('#filterForm select').forEach(s => s.addEventListener('change', () => document.getElementById('filterForm').submit()));
</script>
<?php include 'cookie_consent.php'; ?>
</body>
</html>