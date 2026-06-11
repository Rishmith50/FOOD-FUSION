<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: index.php"); exit(); }
include "db.php";

$curUser = null;
$uStmt = $conn->prepare("SELECT user_id FROM users WHERE first_name = ? LIMIT 1");
$uStmt->bind_param("s", $_SESSION['user']);
$uStmt->execute();
if ($uRow = $uStmt->get_result()->fetch_assoc()) $curUser = (int)$uRow['user_id'];

$toast_msg = ''; $toast_type = 'success';

/* Submit recipe */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_recipe'])) {
    $title=$_POST['title']??''; $cuisine=$_POST['cuisine_type']??''; $diet=$_POST['dietary_pref']??'';
    $diff=$_POST['difficulty']??''; $ings=$_POST['ingredients']??''; $instr=$_POST['instructions']??'';
    $tip=$_POST['cooking_tip']??'';
    if (trim($title)&&trim($cuisine)&&trim($diet)&&trim($diff)&&trim($ings)&&trim($instr)&&$curUser) {
        $ins=$conn->prepare("INSERT INTO community_posts (user_id,title,cuisine_type,dietary_pref,difficulty,ingredients,instructions,cooking_tip) VALUES (?,?,?,?,?,?,?,?)");
        $ins->bind_param("isssssss",$curUser,$title,$cuisine,$diet,$diff,$ings,$instr,$tip);
        if ($ins->execute()) { header("Location: community.php?shared=1"); exit(); }
        else { $toast_msg="Error saving. Please try again."; $toast_type='danger'; }
    } else { $toast_msg="Please fill all required fields."; $toast_type='warning'; }
}

/* Like toggle */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_like']) && $curUser) {
    $pid=(int)($_POST['post_id']??0);
    $chk=$conn->prepare("SELECT like_id FROM community_likes WHERE post_id=? AND user_id=?");
    $chk->bind_param("ii",$pid,$curUser); $chk->execute();
    if ($chk->get_result()->num_rows>0) {
        $d=$conn->prepare("DELETE FROM community_likes WHERE post_id=? AND user_id=?");
        $d->bind_param("ii",$pid,$curUser); $d->execute();
    } else {
        $l=$conn->prepare("INSERT INTO community_likes (post_id,user_id) VALUES (?,?)");
        $l->bind_param("ii",$pid,$curUser); $l->execute();
    }
    header("Location: community.php#post-$pid"); exit();
}

/* Add comment */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_comment']) && $curUser) {
    $pid=(int)($_POST['post_id']??0); $comment=trim($_POST['comment']??'');
    if ($pid&&$comment) {
        $cm=$conn->prepare("INSERT INTO community_comments (post_id,user_id,comment) VALUES (?,?,?)");
        $cm->bind_param("iis",$pid,$curUser,$comment); $cm->execute();
    }
    header("Location: community.php#post-$pid"); exit();
}

if (isset($_GET['shared'])) { $toast_msg="Your recipe has been shared! 🎉"; $toast_type='success'; }

/* Filters */
$search=trim($_GET['search']??''); $cuisine=trim($_GET['cuisine']??'');
$diet=trim($_GET['diet']??''); $diff=trim($_GET['diff']??''); $sort=trim($_GET['sort']??'newest');
$where=["cp.status='approved'"]; $params=[]; $types='';
if ($search!=='') { $where[]="(cp.title LIKE ? OR cp.ingredients LIKE ?)"; $like="%$search%"; $params[]=$like; $params[]=$like; $types.='ss'; }
if ($cuisine!=='') { $where[]="cp.cuisine_type=?"; $params[]=$cuisine; $types.='s'; }
if ($diet!=='')    { $where[]="cp.dietary_pref=?";  $params[]=$diet;    $types.='s'; }
if ($diff!=='')    { $where[]="cp.difficulty=?";    $params[]=$diff;    $types.='s'; }
$whereSQL="WHERE ".implode(" AND ",$where);
if ($sort==='az') $orderSQL="ORDER BY cp.title ASC";
elseif ($sort==='za') $orderSQL="ORDER BY cp.title DESC";
elseif ($sort==='popular') $orderSQL="ORDER BY like_count DESC";
elseif ($sort==='oldest')  $orderSQL="ORDER BY cp.created_at ASC";
else $orderSQL="ORDER BY cp.created_at DESC";

$sql="SELECT cp.*, u.first_name AS contributor,
      COUNT(DISTINCT cl.like_id) AS like_count, COUNT(DISTINCT cc.comment_id) AS comment_count
      FROM community_posts cp
      LEFT JOIN users u ON cp.user_id=u.user_id
      LEFT JOIN community_likes cl ON cp.post_id=cl.post_id
      LEFT JOIN community_comments cc ON cp.post_id=cc.post_id
      $whereSQL GROUP BY cp.post_id $orderSQL";
$stmt=$conn->prepare($sql);
if ($params) $stmt->bind_param($types,...$params);
$stmt->execute();
$posts=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total=count($posts);

$likedIds=[];
if ($curUser) { $lRes=$conn->query("SELECT post_id FROM community_likes WHERE user_id=$curUser"); while($r=$lRes->fetch_assoc()) $likedIds[]=(int)$r['post_id']; }

$cuisines=$conn->query("SELECT DISTINCT cuisine_type FROM community_posts WHERE status='approved' ORDER BY cuisine_type")->fetch_all(MYSQLI_ASSOC);
$diets=$conn->query("SELECT DISTINCT dietary_pref FROM community_posts WHERE status='approved' ORDER BY dietary_pref")->fetch_all(MYSQLI_ASSOC);

$totalPosts=$conn->query("SELECT COUNT(*) AS c FROM community_posts WHERE status='approved'")->fetch_assoc()['c'];
$totalMembers=$conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalLikes=$conn->query("SELECT COUNT(*) AS c FROM community_likes")->fetch_assoc()['c'];
$totalComments=$conn->query("SELECT COUNT(*) AS c FROM community_comments")->fetch_assoc()['c'];

function diffBadge($d){ $d=strtolower($d); if($d==='easy') return 'success'; if($d==='medium') return 'warning'; if($d==='hard') return 'danger'; return 'secondary'; }
function cEmoji($c){ $c=strtolower($c); $m=['indian'=>'🇮🇳','italian'=>'🇮🇹','chinese'=>'🇨🇳','mexican'=>'🇲🇽','american'=>'🇺🇸','dessert'=>'🍮','beverage'=>'🥤','snack'=>'🥪']; return $m[$c]??'🍽️'; }
function cookTime($d){ $d=strtolower($d); if($d==='easy') return '20–30 min'; if($d==='medium') return '30–60 min'; return '60+ min'; }
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
  <title>Community Cookbook – FoodFusion</title>
  <style>
    .page-banner{position:relative;height:420px;display:flex;align-items:center;overflow:hidden;}
    .page-banner img.banner-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;}
    .page-banner .banner-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(0,0,0,.85) 45%,rgba(0,0,0,.2));}
    .page-banner .banner-content{position:relative;z-index:2;}
    .page-banner .breadcrumb-item a{color:var(--color-primary,#ce1212);}
    .page-banner .breadcrumb-item.active{color:rgba(255,255,255,.65);}
    .page-banner .breadcrumb-item+.breadcrumb-item::before{color:rgba(255,255,255,.35);}
    .section-title-sm{font-size:12px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--color-primary,#ce1212);display:flex;align-items:center;gap:10px;margin-bottom:10px;}
    .section-title-sm::before{content:'';display:inline-block;width:28px;height:2px;background:var(--color-primary,#ce1212);}
    .stats-bar{background:var(--color-secondary,#37373f);}
    .stat-box{padding:34px 20px;text-align:center;border-right:1px solid rgba(255,255,255,.08);}
    .stat-box:last-child{border-right:none;}
    .stat-box .num{font-size:38px;font-weight:800;color:var(--color-primary,#ce1212);line-height:1;}
    .stat-box .lbl{font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.5);margin-top:6px;}
    .filter-bar{background:#fff;border-bottom:1px solid #f0f0f0;position:sticky;top:70px;z-index:90;box-shadow:0 2px 12px rgba(0,0,0,.05);}
    .filter-bar .form-select,.filter-bar .form-control{border-radius:50px;border:1.5px solid #e8e8e8;font-size:13px;padding:8px 18px;transition:border-color .2s;}
    .filter-bar .form-select:focus,.filter-bar .form-control:focus{border-color:var(--color-primary,#ce1212);box-shadow:0 0 0 3px rgba(206,18,18,.08);}
    .btn-filter{background:var(--color-primary,#ce1212);color:#fff;border:none;border-radius:50px;padding:8px 24px;font-size:13px;font-weight:600;transition:background .2s,transform .15s;}
    .btn-filter:hover{background:#a30f0f;transform:scale(1.03);}
    .btn-reset{border:1.5px solid #ddd;background:transparent;border-radius:50px;padding:8px 20px;font-size:13px;color:#888;transition:all .2s;text-decoration:none;display:inline-block;}
    .btn-reset:hover{border-color:var(--color-primary,#ce1212);color:var(--color-primary,#ce1212);}
    .result-chip{display:inline-flex;align-items:center;gap:6px;background:#fff4f4;border:1px solid #fcc;color:var(--color-primary,#ce1212);font-size:12px;font-weight:600;padding:4px 14px;border-radius:50px;}
    /* post card */
    .post-card{background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 2px 18px rgba(0,0,0,.07);transition:transform .3s,box-shadow .3s;height:100%;display:flex;flex-direction:column;}
    .post-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(0,0,0,.12);}
    .post-card .card-thumb{height:180px;display:flex;align-items:center;justify-content:center;font-size:64px;position:relative;flex-shrink:0;background:linear-gradient(135deg,#fef5f5,#fff0e0);}
    .post-card .card-thumb .overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.14),transparent);}
    .badge-cuisine{position:absolute;top:12px;left:12px;background:rgba(255,255,255,.92);border-radius:50px;padding:4px 12px;font-size:11px;font-weight:600;color:#333;backdrop-filter:blur(4px);}
    .badge-diff{position:absolute;top:12px;right:12px;border-radius:50px;padding:4px 12px;font-size:11px;font-weight:700;}
    .badge-author{position:absolute;bottom:10px;left:12px;background:rgba(0,0,0,.55);border-radius:50px;padding:4px 12px;font-size:11px;color:#fff;backdrop-filter:blur(4px);}
    .post-card .card-body-inner{padding:18px 20px 20px;display:flex;flex-direction:column;flex:1;}
    .post-title{font-size:15px;font-weight:700;color:var(--color-secondary,#37373f);margin-bottom:6px;line-height:1.3;}
    .post-meta{font-size:12px;color:#bbb;display:flex;flex-wrap:wrap;gap:12px;margin-bottom:8px;}
    .post-meta span{display:flex;align-items:center;gap:4px;}
    .post-ings{font-size:12px;color:#999;line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1;margin-bottom:10px;}
    .post-tip{background:#fff8f8;border-left:3px solid var(--color-primary,#ce1212);border-radius:0 8px 8px 0;padding:8px 12px;font-size:12px;color:#888;margin-bottom:10px;display:none;}
    .post-tip.show{display:block;}
    .action-bar{display:flex;align-items:center;gap:8px;margin-top:auto;flex-wrap:wrap;}
    .btn-like{display:flex;align-items:center;gap:5px;background:transparent;border:1.5px solid #e8e8e8;border-radius:50px;padding:7px 14px;font-size:12px;font-weight:600;color:#aaa;transition:all .2s;cursor:pointer;}
    .btn-like:hover,.btn-like.liked{background:#fff4f4;border-color:var(--color-primary,#ce1212);color:var(--color-primary,#ce1212);}
    .btn-cmt{display:flex;align-items:center;gap:5px;background:transparent;border:1.5px solid #e8e8e8;border-radius:50px;padding:7px 14px;font-size:12px;font-weight:600;color:#aaa;transition:all .2s;cursor:pointer;}
    .btn-cmt:hover{border-color:#888;color:#666;}
    .btn-view{flex:1;background:var(--color-primary,#ce1212);color:#fff;border:none;border-radius:50px;padding:8px 14px;font-size:12px;font-weight:600;text-decoration:none;text-align:center;transition:background .2s;min-width:100px;}
    .btn-view:hover{background:#a30f0f;color:#fff;}
    .comments-panel{border-top:1px solid #f5f5f5;padding:14px 20px 16px;display:none;}
    .comments-panel.open{display:block;}
    .comment-item{display:flex;gap:10px;margin-bottom:10px;}
    .c-avatar{width:30px;height:30px;border-radius:50%;background:var(--color-primary,#ce1212);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;}
    .c-bubble{background:#f8f8f8;border-radius:0 10px 10px 10px;padding:8px 12px;flex:1;}
    .c-bubble .c-name{font-size:11px;font-weight:700;color:#333;margin-bottom:2px;}
    .c-bubble .c-text{font-size:13px;color:#666;line-height:1.5;}
    .c-bubble .c-time{font-size:10px;color:#ccc;margin-top:3px;}
    .add-cmt-form{display:flex;gap:8px;margin-top:10px;}
    .add-cmt-form input{flex:1;border:1.5px solid #e8e8e8;border-radius:50px;padding:8px 16px;font-size:13px;outline:none;transition:border-color .2s;}
    .add-cmt-form input:focus{border-color:var(--color-primary,#ce1212);}
    .add-cmt-form button{background:var(--color-primary,#ce1212);color:#fff;border:none;border-radius:50px;padding:8px 18px;font-size:12px;font-weight:600;transition:background .2s;}
    .add-cmt-form button:hover{background:#a30f0f;}
    .empty-state{text-align:center;padding:80px 20px;}
    .empty-state .empty-icon{font-size:72px;margin-bottom:20px;}
    .empty-state h4{font-weight:700;color:#444;margin-bottom:8px;}
    .empty-state p{color:#aaa;font-size:14px;}
    .modal .form-label{font-size:13px;font-weight:600;color:#555;}
    .modal .form-control,.modal .form-select{border-radius:10px;border:1.5px solid #e8e8e8;font-size:14px;transition:border-color .2s;}
    .modal .form-control:focus,.modal .form-select:focus{border-color:var(--color-primary,#ce1212);box-shadow:0 0 0 3px rgba(206,18,18,.08);}
    .diff-label{display:flex;align-items:center;gap:10px;padding:12px 16px;border:1.5px solid #e8e8e8;border-radius:12px;cursor:pointer;transition:all .2s;flex:1;}
    .diff-label:hover,.diff-label.selected{border-color:var(--color-primary,#ce1212);background:#fff8f8;}
    .btn-submit-modal{background:var(--color-primary,#ce1212);color:#fff;border:none;border-radius:50px;padding:10px 28px;font-size:14px;font-weight:600;transition:background .2s,transform .15s;}
    .btn-submit-modal:hover{background:#a30f0f;transform:scale(1.03);}
    .char-count{font-size:11px;color:#bbb;text-align:right;margin-top:3px;}
    .char-count.warn{color:#e67e22;} .char-count.over{color:#e74c3c;font-weight:700;}
    .cta-strip{background:var(--color-primary,#ce1212);}
    .cta-strip h2{color:#fff;font-weight:800;} .cta-strip p{color:rgba(255,255,255,.82);}
    .cta-strip .btn-light{font-weight:600;color:var(--color-primary,#ce1212);border:none;padding:12px 36px;border-radius:50px;transition:transform .2s;}
    .cta-strip .btn-light:hover{transform:scale(1.04);}
    .toast-container{z-index:9999;}
    @media(max-width:768px){.page-banner{height:280px;}.filter-bar{position:static;}.stat-box{border-right:none;border-bottom:1px solid rgba(255,255,255,.08);}}
  </style>
</head>
<body class="index-page">

<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="container position-relative d-flex align-items-center justify-content-between">
    <a href="home1.php" class="logo d-flex align-items-center me-auto me-xl-0"><h1 class="sitename">FOOD FUSION</h1><span>.</span></a>
    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="home1.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="recipes.php">Recipes</a></li>
        <li><a href="culinary.php">Culinary Resources</a></li>
        <li><a href="educational.php" class="active">Educational Resources</a></li>
        <li><a href="community.php" class="active">Community</a></li>
        <li><a href="contact.php">Contact us</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>
    <a class="btn-getstarted" href="logout.php">Logout</a>
  </div>
</header>

<main class="main">

  <section class="page-banner">
    <img src="pablo-merchan-montes-0nT08Z-MhiE-unsplash.jpg" alt="Community Cookbook" class="banner-bg">
    <div class="banner-overlay"></div>
    <div class="banner-content container">
      <div data-aos="fade-up">
        <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="home1.php">Home</a></li><li class="breadcrumb-item active">Community Cookbook</li></ol></nav>
        <h1 class="display-4 fw-bold text-white mb-2">Community <span style="color:var(--color-primary,#ce1212);">Cookbook</span></h1>
        <p class="lead text-white-50 mb-4" style="max-width:560px;">Share your favourite recipes, cooking tips and culinary experiences with the FoodFusion community.</p>
        <div class="d-flex flex-wrap gap-3">
          <button class="btn btn-danger rounded-pill px-4 py-2 fw-600" data-bs-toggle="modal" data-bs-target="#shareModal"><i class="bi bi-plus-circle me-2"></i>Share Your Recipe</button>
          <a href="#cookbook-grid" class="btn btn-outline-light rounded-pill px-4 py-2 fw-600"><i class="bi bi-grid me-2"></i>Browse All</a>
        </div>
      </div>
    </div>
  </section>

  <div class="stats-bar">
    <div class="container"><div class="row g-0">
      <div class="col-6 col-md-3"><div class="stat-box" data-aos="fade-up" data-aos-delay="0"><div class="num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $totalPosts ?>" data-purecounter-duration="2"><?= $totalPosts ?></div><div class="lbl">Recipes Shared</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-box" data-aos="fade-up" data-aos-delay="100"><div class="num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $totalMembers ?>" data-purecounter-duration="2"><?= $totalMembers ?></div><div class="lbl">Members</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-box" data-aos="fade-up" data-aos-delay="200"><div class="num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $totalLikes ?>" data-purecounter-duration="2"><?= $totalLikes ?></div><div class="lbl">Total Likes</div></div></div>
      <div class="col-6 col-md-3"><div class="stat-box" data-aos="fade-up" data-aos-delay="300"><div class="num purecounter" data-purecounter-start="0" data-purecounter-end="<?= $totalComments ?>" data-purecounter-duration="2"><?= $totalComments ?></div><div class="lbl">Comments</div></div></div>
    </div></div>
  </div>

  <div class="filter-bar py-3" id="cookbook-grid">
    <div class="container">
      <form method="GET" action="community.php" id="filterForm">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md-3">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0" style="border-radius:50px 0 0 50px;border:1.5px solid #e8e8e8;"><i class="bi bi-search text-muted" style="font-size:13px;"></i></span>
              <input type="text" name="search" class="form-control border-start-0 ps-0" style="border-radius:0 50px 50px 0;" placeholder="Search recipes…" value="<?= htmlspecialchars($search) ?>">
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="cuisine" class="form-select">
              <option value="">All Cuisines</option>
              <?php foreach($cuisines as $c): ?><option value="<?= htmlspecialchars($c['cuisine_type']) ?>" <?= $cuisine===$c['cuisine_type']?'selected':'' ?>><?= cEmoji($c['cuisine_type']).' '.htmlspecialchars($c['cuisine_type']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="diet" class="form-select">
              <option value="">All Diets</option>
              <?php foreach($diets as $d): ?><option value="<?= htmlspecialchars($d['dietary_pref']) ?>" <?= $diet===$d['dietary_pref']?'selected':'' ?>><?= htmlspecialchars($d['dietary_pref']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="diff" class="form-select">
              <option value="">All Levels</option>
              <option value="Easy" <?= $diff==='Easy'?'selected':'' ?>>🟢 Easy</option>
              <option value="Medium" <?= $diff==='Medium'?'selected':'' ?>>🟡 Medium</option>
              <option value="Hard" <?= $diff==='Hard'?'selected':'' ?>>🔴 Hard</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="sort" class="form-select">
              <option value="newest"  <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
              <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Liked</option>
              <option value="oldest"  <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
              <option value="az"      <?= $sort==='az'?'selected':'' ?>>A → Z</option>
            </select>
          </div>
          <div class="col-12 col-md-1 d-flex gap-2">
            <button type="submit" class="btn-filter flex-fill"><i class="bi bi-funnel-fill"></i></button>
          </div>
        </div>
        <?php if ($search||$cuisine||$diet||$diff): ?>
        <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
          <span class="text-muted" style="font-size:12px;">Active:</span>
          <?php if($search):  ?><span class="result-chip">🔍 <?= htmlspecialchars($search) ?></span><?php endif; ?>
          <?php if($cuisine): ?><span class="result-chip"><?= cEmoji($cuisine).' '.htmlspecialchars($cuisine) ?></span><?php endif; ?>
          <?php if($diet):    ?><span class="result-chip">🥗 <?= htmlspecialchars($diet) ?></span><?php endif; ?>
          <?php if($diff):    ?><span class="result-chip">📊 <?= htmlspecialchars($diff) ?></span><?php endif; ?>
          <a href="community.php" class="btn-reset">✕ Clear</a>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <section class="section">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
        <div>
          <span class="section-title-sm">Member Recipes</span>
          <h2 class="fw-bold mt-1 mb-0" style="font-size:clamp(22px,3vw,34px);"><?= $total ?> Recipe<?= $total!==1?'s':'' ?> in the Cookbook</h2>
        </div>
        <button class="btn btn-danger rounded-pill px-4 py-2 fw-600" data-bs-toggle="modal" data-bs-target="#shareModal"><i class="bi bi-plus-lg me-1"></i>Share Recipe</button>
      </div>

      <?php if ($total > 0): ?>
      <div class="row g-4">
        <?php foreach ($posts as $i => $p):
          $isLiked = in_array((int)$p['post_id'], $likedIds);
          $cmtStmt=$conn->prepare("SELECT cc.*,u.first_name FROM community_comments cc LEFT JOIN users u ON cc.user_id=u.user_id WHERE cc.post_id=? ORDER BY cc.created_at ASC");
          $cmtStmt->bind_param("i",$p['post_id']); $cmtStmt->execute();
          $comments=$cmtStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= min($i%6,3)*80 ?>">
          <div class="post-card" id="post-<?= $p['post_id'] ?>">
            <div class="card-thumb">
              <span><?= cEmoji($p['cuisine_type']) ?></span>
              <div class="overlay"></div>
              <span class="badge-cuisine"><?= htmlspecialchars($p['cuisine_type']) ?></span>
              <span class="badge-diff badge bg-<?= diffBadge($p['difficulty']) ?>"><?= htmlspecialchars($p['difficulty']) ?></span>
              <span class="badge-author"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($p['contributor']??'Member') ?></span>
            </div>
            <div class="card-body-inner">
              <h5 class="post-title"><?= htmlspecialchars($p['title']) ?></h5>
              <div class="post-meta">
                <span><i class="bi bi-tag"></i> <?= htmlspecialchars($p['dietary_pref']) ?></span>
                <span><i class="bi bi-clock"></i> <?= cookTime($p['difficulty']) ?></span>
                <span><i class="bi bi-calendar3"></i> <?= date('d M Y',strtotime($p['created_at'])) ?></span>
              </div>
              <p class="post-ings"><i class="bi bi-basket2 me-1" style="color:var(--color-primary,#ce1212);"></i><?= htmlspecialchars($p['ingredients']) ?></p>
              <?php if ($p['cooking_tip']): ?>
              <div class="post-tip" id="tip-<?= $p['post_id'] ?>">💡 <em><?= htmlspecialchars($p['cooking_tip']) ?></em></div>
              <?php endif; ?>
              <div class="action-bar">
                <form method="POST" action="community.php" style="margin:0;">
                  <input type="hidden" name="post_id" value="<?= $p['post_id'] ?>">
                  <button type="submit" name="toggle_like" class="btn-like <?= $isLiked?'liked':'' ?>">
                    <i class="bi <?= $isLiked?'bi-heart-fill':'bi-heart' ?>"></i> <?= $p['like_count'] ?>
                  </button>
                </form>
                <button class="btn-cmt" onclick="toggleComments(<?= $p['post_id'] ?>)">
                  <i class="bi bi-chat"></i> <?= $p['comment_count'] ?>
                </button>
                <?php if ($p['cooking_tip']): ?>
                <button class="btn-cmt" onclick="document.getElementById('tip-<?= $p['post_id'] ?>').classList.toggle('show')" title="Tip" style="padding:7px 10px;">💡</button>
                <?php endif; ?>
                <a href="recipe_details.php?id=<?= $p['post_id'] ?>" class="btn-view">View Recipe <i class="bi bi-arrow-right"></i></a>
              </div>
            </div>
            <div class="comments-panel" id="comments-<?= $p['post_id'] ?>">
              <?php if (count($comments)>0): ?>
                <?php foreach ($comments as $cm): ?>
                <div class="comment-item">
                  <div class="c-avatar"><?= strtoupper(substr($cm['first_name']??'M',0,1)) ?></div>
                  <div class="c-bubble">
                    <div class="c-name"><?= htmlspecialchars($cm['first_name']??'Member') ?></div>
                    <div class="c-text"><?= htmlspecialchars($cm['comment']) ?></div>
                    <div class="c-time"><?= date('d M Y, H:i',strtotime($cm['created_at'])) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php else: ?><p style="font-size:13px;color:#ccc;text-align:center;padding:8px 0;">No comments yet. Be first!</p><?php endif; ?>
              <form method="POST" action="community.php#post-<?= $p['post_id'] ?>" class="add-cmt-form">
                <input type="hidden" name="post_id" value="<?= $p['post_id'] ?>">
                <input type="text" name="comment" placeholder="Add a comment…" maxlength="500" required>
                <button type="submit" name="add_comment"><i class="bi bi-send-fill"></i></button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state" data-aos="fade-up">
        <div class="empty-icon">📖</div>
        <h4>No recipes found</h4>
        <p>Try different filters or be the first to share a recipe with the community!</p>
        <a href="community.php" class="btn btn-outline-danger rounded-pill px-4 py-2 mt-3 me-2">Clear Filters</a>
        <button class="btn btn-danger rounded-pill px-4 py-2 mt-3 fw-600" data-bs-toggle="modal" data-bs-target="#shareModal"><i class="bi bi-plus-lg me-1"></i>Share a Recipe</button>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="cta-strip py-5" data-aos="zoom-in">
    <div class="container text-center">
      <h2 class="fw-bold mb-2">Have a Recipe to Share?</h2>
      <p class="mb-4">Join the FoodFusion community and share your favourite dishes with fellow food lovers!</p>
      <button class="btn btn-light rounded-pill px-5 py-2 fw-600 fs-6 me-3" data-bs-toggle="modal" data-bs-target="#shareModal"><i class="bi bi-pencil-square me-2"></i>Share Your Recipe</button>
      <a href="recipes.php" class="btn btn-outline-light rounded-pill px-5 py-2 fw-600 fs-6"><i class="bi bi-grid me-2"></i>All Recipes</a>
    </div>
  </section>

</main>

<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:18px;display:flex;flex-direction:column;max-height:90vh;">
      <div class="modal-header px-4 pt-4 border-0">
        <div>
          <h5 class="modal-title fw-bold mb-1"><i class="bi bi-journal-plus me-2" style="color:var(--color-primary,#ce1212);"></i>Share Your Recipe</h5>
          <p class="text-muted mb-0" style="font-size:13px;">Posting as <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="community.php">
        <div class="modal-body px-4 py-3" style="overflow-y:auto;flex:1 1 auto;">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Recipe Title *</label>
              <input type="text" name="title" id="tInp" class="form-control" placeholder="Give your recipe a name…" maxlength="150" required>
              <div class="char-count" id="tCnt">0 / 150</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cuisine Type *</label>
              <select name="cuisine_type" class="form-select" required>
                <option value="">Select…</option>
                <option>Indian</option><option>Italian</option><option>Chinese</option><option>Mexican</option><option>American</option><option>Dessert</option><option>Beverage</option><option>Snack</option><option>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Dietary Preference *</label>
              <select name="dietary_pref" class="form-select" required>
                <option value="">Select…</option>
                <option>Vegetarian</option><option>Non-Vegetarian</option><option>Vegan</option><option>Gluten-Free</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Difficulty *</label>
              <div class="d-flex gap-3">
                <label class="diff-label flex-fill"><input type="radio" name="difficulty" value="Easy" class="diff-radio d-none" required><span>🟢</span><div><div style="font-weight:700;font-size:13px;">Easy</div><div style="font-size:11px;color:#aaa;">20–30 min</div></div></label>
                <label class="diff-label flex-fill"><input type="radio" name="difficulty" value="Medium" class="diff-radio d-none"><span>🟡</span><div><div style="font-weight:700;font-size:13px;">Medium</div><div style="font-size:11px;color:#aaa;">30–60 min</div></div></label>
                <label class="diff-label flex-fill"><input type="radio" name="difficulty" value="Hard" class="diff-radio d-none"><span>🔴</span><div><div style="font-weight:700;font-size:13px;">Hard</div><div style="font-size:11px;color:#aaa;">60+ min</div></div></label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Ingredients * <small class="text-muted fw-normal">(comma-separated)</small></label>
              <textarea name="ingredients" id="iInp" class="form-control" rows="3" placeholder="e.g. Paneer, butter, cream, tomato, onion, spices" maxlength="1000" required></textarea>
              <div class="char-count" id="iCnt">0 / 1000</div>
            </div>
            <div class="col-12">
              <label class="form-label">Instructions * <small class="text-muted fw-normal">(full stops separate steps)</small></label>
              <textarea name="instructions" id="nInp" class="form-control" rows="6" placeholder="Heat oil in a pan. Add onions and fry. Add tomatoes. Simmer for 10 minutes." maxlength="3000" required></textarea>
              <div class="char-count" id="nCnt">0 / 3000</div>
            </div>
            <div class="col-12">
              <label class="form-label">Pro Tip <small class="text-muted fw-normal">(optional)</small></label>
              <input type="text" name="cooking_tip" class="form-control" placeholder="Share a secret trick that makes this special…" maxlength="300">
            </div>
          </div>
        </div>
        <div class="modal-footer px-4 pb-4 border-0" style="flex-shrink:0;background:#fff;border-radius:0 0 18px 18px;">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="submit_recipe" class="btn-submit-modal"><i class="bi bi-send me-1"></i>Share with Community</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer id="footer" style="background:#1f1f24;color:white;padding:40px 0;text-align:center;">
  <div class="container">
    <h3>FoodFusion</h3><p>Cook • Share • Explore Delicious Recipes</p>
    <div style="margin:20px 0;">
      <a href="https://wa.me/919999999999" target="_blank" style="margin:10px;"><img src="pngwing.com (1).png" width="40" alt="WhatsApp"></a>
      <a href="https://www.instagram.com/foodf_usion18/" target="_blank" style="margin:10px;"><img src="pngwing.com.png" width="40" alt="Instagram"></a>
      <a href="https://facebook.com" target="_blank" style="margin:10px;"><img src="facebook.png" width="40" alt="Facebook"></a>
    </div>
    <p>📞 +91 9876543210</p><p>📧 contact@foodfusion.com</p>
    <p style="margin-top:20px;font-size:14px;">© 2026 FoodFusion. All Rights Reserved.</p>
  </div>
</footer>

<div class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></div>

<?php if ($toast_msg): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="mainToast" class="toast align-items-center text-bg-<?= $toast_type ?> border-0 show" role="alert">
    <div class="d-flex"><div class="toast-body fw-500"><?= htmlspecialchars($toast_msg) ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
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
const toastEl=document.getElementById('mainToast');
if(toastEl) setTimeout(()=>bootstrap.Toast.getOrCreateInstance(toastEl).hide(),5000);
let st; const si=document.querySelector('input[name="search"]');
if(si) si.addEventListener('input',()=>{clearTimeout(st);st=setTimeout(()=>document.getElementById('filterForm').submit(),600);});
document.querySelectorAll('#filterForm select').forEach(s=>s.addEventListener('change',()=>document.getElementById('filterForm').submit()));
function charCount(iId,cId,max){const el=document.getElementById(iId),ct=document.getElementById(cId);if(!el||!ct)return;el.addEventListener('input',()=>{const l=el.value.length;ct.textContent=l+' / '+max;ct.className='char-count'+(l>max*.9?(l>=max?' over':' warn'):'');});}
charCount('tInp','tCnt',150);charCount('iInp','iCnt',1000);charCount('nInp','nCnt',3000);
document.querySelectorAll('.diff-radio').forEach(r=>r.addEventListener('change',function(){document.querySelectorAll('.diff-label').forEach(l=>l.classList.remove('selected'));this.closest('.diff-label').classList.add('selected');}));
function toggleComments(pid){const p=document.getElementById('comments-'+pid);if(p)p.classList.toggle('open');}
window.addEventListener('load',()=>{const h=window.location.hash;if(h&&h.startsWith('#post-')){const p=document.getElementById('comments-'+h.replace('#post-',''));if(p)p.classList.add('open');}});
</script>
</body>
</html>