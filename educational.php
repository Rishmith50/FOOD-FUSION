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
            "INSERT INTO educational_resources (title, resource_type, file_url, description)
             VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("ssss", $title, $rtype, $file_url, $desc);
        if ($ins->execute()) {
            header("Location: educational_resources.php?added=1");
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

$sql  = "SELECT * FROM educational_resources $whereSQL $orderSQL";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result    = $stmt->get_result();
$resources = $result->fetch_all(MYSQLI_ASSOC);
$total     = count($resources);

/* ── Counts per type ── */
$counts = ['PDF' => 0, 'Infographic' => 0, 'Video' => 0];
$cRes   = $conn->query("SELECT resource_type, COUNT(*) as cnt FROM educational_resources GROUP BY resource_type");
while ($cRow = $cRes->fetch_assoc()) {
    if (isset($counts[$cRow['resource_type']])) $counts[$cRow['resource_type']] = (int)$cRow['cnt'];
}
$totalAll = array_sum($counts);

/* ── Helpers ── */
function eduEmoji($t) {
    if ($t === 'PDF')         return '📄';
    if ($t === 'Infographic') return '📊';
    if ($t === 'Video')       return '🎬';
    return '📁';
}
function eduBg($t) {
    if ($t === 'PDF')         return 'linear-gradient(135deg,#eefaf4,#d4f5e2)';
    if ($t === 'Infographic') return 'linear-gradient(135deg,#eef4ff,#ddeeff)';
    return 'linear-gradient(135deg,#f5f0ff,#e8d8ff)';
}
function eduBadge($t) {
    if ($t === 'PDF')         return 'success';
    if ($t === 'Infographic') return 'primary';
    return 'purple';
}
function isVideo($url) {
    if (!$url) return false;
    $u = strtolower($url);
    return str_contains($u,'youtube') || str_contains($u,'youtu.be') || str_contains($u,'vimeo') || str_ends_with($u,'.mp4');
}
function isDownloadable($url) {
    if (!$url) return false;
    return in_array(strtolower(pathinfo($url, PATHINFO_EXTENSION)), ['pdf','png','jpg','jpeg','zip','docx','pptx','svg']);
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
  <title>Educational Resources – FoodFusion</title>

  <style>
    /* ── accent colour for this page: green ── */
    :root { --edu-green:#1a8a4a; --edu-green-light:#e8f8ee; --edu-green-dark:#136337; }

    .page-banner { position:relative; height:400px; display:flex; align-items:center; overflow:hidden; }
    .page-banner img.banner-bg { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center; }
    .page-banner .banner-overlay { position:absolute; inset:0; background:linear-gradient(to right,rgba(10,40,20,.88) 45%,rgba(10,40,20,.25)); }
    .page-banner .banner-content { position:relative; z-index:2; }
    .page-banner .breadcrumb-item a { color:#4cd980; }
    .page-banner .breadcrumb-item.active { color:rgba(255,255,255,.65); }
    .page-banner .breadcrumb-item+.breadcrumb-item::before { color:rgba(255,255,255,.35); }

    .section-title-sm { font-size:12px; font-weight:600; letter-spacing:2.5px; text-transform:uppercase; color:var(--edu-green); display:flex; align-items:center; gap:10px; margin-bottom:10px; }
    .section-title-sm::before { content:''; display:inline-block; width:28px; height:2px; background:var(--edu-green); }

    /* stat cards */
    .stat-card { background:#fff; border-radius:16px; padding:28px 20px; text-align:center; box-shadow:0 2px 16px rgba(0,0,0,.06); border-bottom:4px solid transparent; transition:transform .3s,box-shadow .3s,border-color .3s; text-decoration:none; display:block; color:inherit; }
    .stat-card:hover,.stat-card.active-filter { transform:translateY(-4px); box-shadow:0 10px 28px rgba(0,0,0,.10); }
    .stat-card.active-filter { border-bottom-color:var(--edu-green); }
    .stat-card .stat-icon { font-size:38px; margin-bottom:10px; display:block; }
    .stat-card .stat-num  { font-size:38px; font-weight:800; color:var(--edu-green); line-height:1; }
    .stat-card .stat-lbl  { font-size:12px; color:#999; letter-spacing:1px; text-transform:uppercase; margin-top:6px; }

    /* filter bar */
    .filter-bar { background:#fff; border-bottom:1px solid #f0f0f0; position:sticky; top:70px; z-index:90; box-shadow:0 2px 12px rgba(0,0,0,.05); }
    .filter-bar .form-select,.filter-bar .form-control { border-radius:50px; border:1.5px solid #e8e8e8; font-size:13px; padding:8px 18px; transition:border-color .2s; }
    .filter-bar .form-select:focus,.filter-bar .form-control:focus { border-color:var(--edu-green); box-shadow:0 0 0 3px rgba(26,138,74,.10); }
    .btn-filter { background:var(--edu-green); color:#fff; border:none; border-radius:50px; padding:8px 24px; font-size:13px; font-weight:600; transition:background .2s,transform .15s; }
    .btn-filter:hover { background:var(--edu-green-dark); transform:scale(1.03); }
    .btn-reset { border:1.5px solid #ddd; background:transparent; border-radius:50px; padding:8px 20px; font-size:13px; color:#888; transition:border-color .2s,color .2s; text-decoration:none; display:inline-block; }
    .btn-reset:hover { border-color:var(--edu-green); color:var(--edu-green); }
    .result-chip { display:inline-flex; align-items:center; gap:6px; background:var(--edu-green-light); border:1px solid #b2dfc4; color:var(--edu-green); font-size:12px; font-weight:600; padding:4px 14px; border-radius:50px; }

    /* resource card */
    .resource-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,.07); transition:transform .3s,box-shadow .3s; height:100%; display:flex; flex-direction:column; }
    .resource-card:hover { transform:translateY(-6px); box-shadow:0 14px 36px rgba(0,0,0,.12); }
    .resource-card .card-thumb { height:190px; display:flex; align-items:center; justify-content:center; font-size:68px; position:relative; flex-shrink:0; }
    .resource-card .type-label { position:absolute; top:12px; left:12px; font-size:11px; font-weight:700; padding:5px 14px; border-radius:50px; }
    .resource-card .card-body-inner { padding:22px 22px 24px; display:flex; flex-direction:column; flex:1; }
    .resource-card .card-title { font-size:16px; font-weight:700; color:var(--color-secondary,#37373f); margin-bottom:8px; line-height:1.3; }
    .resource-card .card-meta { font-size:12px; color:#bbb; display:flex; align-items:center; gap:6px; margin-bottom:10px; }
    .resource-card .card-desc { font-size:13px; color:#888; line-height:1.7; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; flex:1; margin-bottom:16px; }

    .btn-action { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:11px; border-radius:50px; font-size:13px; font-weight:600; text-decoration:none; border:none; transition:background .2s,transform .15s; cursor:pointer; }
    .btn-action.primary  { background:var(--edu-green); color:#fff; }
    .btn-action.primary:hover  { background:var(--edu-green-dark); transform:scale(1.02); color:#fff; }
    .btn-action.outline  { background:transparent; border:1.5px solid var(--edu-green); color:var(--edu-green); }
    .btn-action.outline:hover  { background:var(--edu-green); color:#fff; }
    .btn-action.disabled { background:#f5f5f5; color:#bbb; cursor:default; }

    /* topic badge - purple for video */
    .badge.bg-purple { background:#7c3aed !important; color:#fff; }

    /* how cards */
    .how-card { background:#fff; border-radius:16px; padding:36px 28px; height:100%; box-shadow:0 2px 16px rgba(0,0,0,.06); border-top:4px solid var(--edu-green); text-align:center; transition:transform .3s,box-shadow .3s; }
    .how-card:hover { transform:translateY(-5px); box-shadow:0 12px 32px rgba(0,0,0,.10); }
    .how-card .how-icon { font-size:48px; margin-bottom:18px; display:block; }
    .how-card h5 { font-weight:700; margin-bottom:10px; color:var(--color-secondary,#37373f); }
    .how-card p  { font-size:14px; color:#888; line-height:1.75; margin:0 0 18px; }
    .how-card .btn-green { background:var(--edu-green); color:#fff; border:none; border-radius:50px; padding:10px 24px; font-size:13px; font-weight:600; transition:background .2s,transform .2s; text-decoration:none; display:inline-block; }
    .how-card .btn-green:hover { background:var(--edu-green-dark); transform:scale(1.04); color:#fff; }

    /* energy topic pills */
    .topic-pill { display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); border-radius:50px; padding:8px 18px; font-size:13px; color:rgba(255,255,255,.85); transition:background .2s,border-color .2s; }
    .topic-pill:hover { background:rgba(26,138,74,.3); border-color:rgba(26,138,74,.5); }

    /* impact stats */
    .impact-card { background:var(--edu-green); border-radius:16px; padding:32px 24px; text-align:center; color:#fff; }
    .impact-card .impact-num { font-size:40px; font-weight:800; line-height:1; }
    .impact-card .impact-lbl { font-size:13px; opacity:.8; margin-top:6px; letter-spacing:.5px; }

    /* empty */
    .empty-state { text-align:center; padding:80px 20px; }
    .empty-state .empty-icon { font-size:72px; margin-bottom:20px; }
    .empty-state h4 { font-weight:700; color:#444; margin-bottom:8px; }
    .empty-state p  { color:#aaa; font-size:14px; }

    /* modal */
    .modal .form-label { font-size:13px; font-weight:600; color:#555; }
    .modal .form-control,.modal .form-select { border-radius:10px; border:1.5px solid #e8e8e8; font-size:14px; transition:border-color .2s; }
    .modal .form-control:focus,.modal .form-select:focus { border-color:var(--edu-green); box-shadow:0 0 0 3px rgba(26,138,74,.10); }
    .btn-submit-modal { background:var(--edu-green); color:#fff; border:none; border-radius:50px; padding:10px 28px; font-size:14px; font-weight:600; transition:background .2s,transform .15s; }
    .btn-submit-modal:hover { background:var(--edu-green-dark); transform:scale(1.03); }

    /* cta strip */
    .cta-strip { background:var(--edu-green); }
    .cta-strip h2 { color:#fff; font-weight:800; }
    .cta-strip p  { color:rgba(255,255,255,.82); }
    .cta-strip .btn-light { font-weight:600; color:var(--edu-green); border:none; padding:12px 36px; border-radius:50px; transition:transform .2s; }
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
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="educational.php" class="active">Educational Resources</a></li>
        <li><a href="community.php">Community</a></li>
        <li><a href="home1.php#contact">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>
    <a class="btn-getstarted" href="logout.php">Logout</a>
  </div>
</header>

<main class="main">

  <!-- ======= Banner ======= -->
  <section class="page-banner">
    <img src="alex-munsell-Yr4n8O_3UPc-unsplash.jpg" alt="Educational Resources" class="banner-bg">
    <div class="banner-overlay"></div>
    <div class="banner-content container">
      <div data-aos="fade-up">
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="home1.php">Home</a></li>
            <li class="breadcrumb-item active">Educational Resources</li>
          </ol>
        </nav>
        <h1 class="display-4 fw-bold text-white mb-2">
          Educational <span style="color:#4cd980;">Resources</span>
        </h1>
        <p class="lead mb-3" style="color:rgba(255,255,255,.75);max-width:580px;">
          Downloadable PDFs, infographics and videos on <strong style="color:#4cd980;">renewable energy</strong> — helping our community cook and live more sustainably.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge rounded-pill" style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px);font-size:13px;padding:8px 16px;">📄 PDF Guides</span>
          <span class="badge rounded-pill" style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px);font-size:13px;padding:8px 16px;">📊 Infographics</span>
          <span class="badge rounded-pill" style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px);font-size:13px;padding:8px 16px;">🎬 Videos</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= Stat Cards ======= -->
  <section class="section light-background py-4">
    <div class="container">
      <div class="row g-4">
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="0">
          <a href="educational_resources.php" class="stat-card <?= $filter_type==='' ? 'active-filter':'' ?>">
            <span class="stat-icon">🌱</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $totalAll ?>" data-purecounter-duration="1"><?= $totalAll ?></div>
            <div class="stat-lbl">All Resources</div>
          </a>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="80">
          <a href="educational_resources.php?type=PDF" class="stat-card <?= $filter_type==='PDF' ? 'active-filter':'' ?>">
            <span class="stat-icon">📄</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $counts['PDF'] ?>" data-purecounter-duration="1"><?= $counts['PDF'] ?></div>
            <div class="stat-lbl">PDF Guides</div>
          </a>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="160">
          <a href="educational_resources.php?type=Infographic" class="stat-card <?= $filter_type==='Infographic' ? 'active-filter':'' ?>">
            <span class="stat-icon">📊</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $counts['Infographic'] ?>" data-purecounter-duration="1"><?= $counts['Infographic'] ?></div>
            <div class="stat-lbl">Infographics</div>
          </a>
        </div>
        <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="240">
          <a href="educational_resources.php?type=Video" class="stat-card <?= $filter_type==='Video' ? 'active-filter':'' ?>">
            <span class="stat-icon">🎬</span>
            <div class="stat-num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $counts['Video'] ?>" data-purecounter-duration="1"><?= $counts['Video'] ?></div>
            <div class="stat-lbl">Videos</div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= Renewable Energy Topics ======= -->
  <section class="py-4" style="background:#0d2b1a;">
    <div class="container text-center">
      <p class="text-white-50 mb-3" style="font-size:12px;letter-spacing:2px;text-transform:uppercase;">Renewable Energy Topics Covered</p>
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <?php
        $topics = ['☀️ Solar Energy','💨 Wind Power','💧 Hydropower','🌊 Tidal Energy','🌋 Geothermal',
                   '🌿 Biomass Energy','🔋 Energy Storage','⚡ Smart Grids','🏠 Green Kitchens',
                   '♻️ Waste-to-Energy','🌡️ Energy Efficiency','🚗 EV & Clean Transport'];
        foreach ($topics as $t) echo "<span class='topic-pill'>$t</span>";
        ?>
      </div>
    </div>
  </section>

  <!-- ======= Filter Bar ======= -->
  <div class="filter-bar py-3">
    <div class="container">
      <form method="GET" action="educational_resources.php" id="filterForm">
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
              <option value="PDF"         <?= $filter_type==='PDF'?'selected':'' ?>>📄 PDF</option>
              <option value="Infographic" <?= $filter_type==='Infographic'?'selected':'' ?>>📊 Infographic</option>
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
            <a href="educational_resources.php" class="btn-reset">✕ Clear</a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($search || $filter_type): ?>
        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
          <span class="text-muted" style="font-size:12px;">Active:</span>
          <?php if ($search):      ?><span class="result-chip">🔍 <?= htmlspecialchars($search) ?></span><?php endif; ?>
          <?php if ($filter_type): ?><span class="result-chip"><?= eduEmoji($filter_type) ?> <?= htmlspecialchars($filter_type) ?></span><?php endif; ?>
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
        <button class="btn rounded-pill px-4 py-2 fw-600"
                style="background:var(--edu-green);color:#fff;border:none;"
                data-bs-toggle="modal" data-bs-target="#addResourceModal">
          <i class="bi bi-plus-lg me-1"></i>Add Resource
        </button>
      </div>

      <?php if ($total > 0): ?>
      <div class="row g-4">
        <?php foreach ($resources as $i => $r): ?>
        <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= min($i%6,3)*80 ?>">
          <div class="resource-card">
            <div class="card-thumb" style="background:<?= eduBg($r['resource_type']) ?>;">
              <span><?= eduEmoji($r['resource_type']) ?></span>
              <span class="type-label badge bg-<?= eduBadge($r['resource_type']) ?>">
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
        <div class="empty-icon">🌱</div>
        <h4>No resources found</h4>
        <p>Try a different search or filter, or be the first to add a renewable energy resource!</p>
        <a href="educational_resources.php" class="btn btn-outline-success rounded-pill px-4 py-2 mt-3 me-2">Clear Filters</a>
        <button class="btn rounded-pill px-4 py-2 mt-3 fw-600"
                style="background:var(--edu-green);color:#fff;border:none;"
                data-bs-toggle="modal" data-bs-target="#addResourceModal">
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
        <span class="section-title-sm justify-content-center">Resource Types</span>
        <h2 class="fw-bold" style="font-size:clamp(26px,3vw,38px);">Three Ways to Learn About Clean Energy</h2>
        <p class="text-muted mt-2" style="max-width:540px;margin:0 auto;">
          Each format is designed to make renewable energy topics easy to understand and share.
        </p>
      </div>
      <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
          <div class="how-card">
            <span class="how-icon">📄</span>
            <h5>PDF Guides</h5>
            <p>Detailed downloadable documents covering solar, wind, hydro and other renewable energy topics — great for research and offline reading.</p>
            <a href="educational.php?type=PDF" class="btn-green">Browse PDFs</a>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
          <div class="how-card">
            <span class="how-icon">📊</span>
            <h5>Infographics</h5>
            <p>Visual data representations that make complex energy statistics, comparisons and environmental impact easy to understand at a glance.</p>
            <a href="educational.php?type=Infographic" class="btn-green">Browse Infographics</a>
          </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
          <div class="how-card">
            <span class="how-icon">🎬</span>
            <h5>Videos</h5>
            <p>Engaging video content explaining renewable energy concepts, technology demonstrations and sustainability practices in everyday life.</p>
            <a href="educational.php?type=Video" class="btn-green">Browse Videos</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= Why It Matters ======= -->
  <section class="section" style="background:#0d2b1a;">
    <div class="container">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="section-title-sm justify-content-center" style="color:#4cd980;">
          <span style="background:#4cd980;width:28px;height:2px;display:inline-block;"></span>
          Why It Matters
        </span>
        <h2 class="fw-bold text-white" style="font-size:clamp(26px,3vw,38px);">Sustainability Starts in the Kitchen</h2>
        <p style="color:rgba(255,255,255,.6);max-width:560px;margin:12px auto 0;">
          FoodFusion believes that understanding renewable energy makes us better, more conscious cooks and citizens.
        </p>
      </div>
      <div class="row g-4">
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="0">
          <div class="impact-card">
            <div class="impact-num">73%</div>
            <div class="impact-lbl">of kitchen energy can be replaced by renewables</div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="80">
          <div class="impact-card">
            <div class="impact-num">2.4T</div>
            <div class="impact-lbl">tonnes of CO₂ saved annually by solar cooking</div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="160">
          <div class="impact-card">
            <div class="impact-num">40%</div>
            <div class="impact-lbl">energy reduction with induction & smart appliances</div>
          </div>
        </div>
        <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="240">
          <div class="impact-card">
            <div class="impact-num">196</div>
            <div class="impact-lbl">countries committed to clean energy targets</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= CTA ======= -->
  <section class="cta-strip py-5" data-aos="zoom-in">
    <div class="container text-center">
      <h2 class="fw-bold mb-2">Share Your Knowledge</h2>
      <p class="mb-4">Contribute a PDF, infographic or video on renewable energy to help our community learn and live sustainably!</p>
      <button class="btn btn-light rounded-pill px-5 py-2 fw-600 fs-6"
              data-bs-toggle="modal" data-bs-target="#addResourceModal">
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
          <i class="bi bi-plus-square-fill me-2" style="color:var(--edu-green);"></i>
          Add a New Educational Resource
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="educational_resources.php">
        <div class="modal-body px-4 py-3">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Resource Title *</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Introduction to Solar Energy" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Resource Type *</label>
              <select name="resource_type" class="form-select" required>
                <option value="">Select type…</option>
                <option value="PDF">📄 PDF Guide</option>
                <option value="Infographic">📊 Infographic</option>
                <option value="Video">🎬 Video</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">File / Link URL <small class="text-muted fw-normal">(optional)</small></label>
              <input type="url" name="file_url" class="form-control" placeholder="https://… (PDF, YouTube link, image, etc.)">
              <div class="form-text">PDF/image files will show a Download button. YouTube/Vimeo links will show Watch Video.</div>
            </div>
            <div class="col-12">
              <label class="form-label">Description <small class="text-muted fw-normal">(optional)</small></label>
              <textarea name="description" class="form-control" rows="4"
                        placeholder="What renewable energy topic does this resource cover?"></textarea>
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