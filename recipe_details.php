<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Check ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: recipes.php");
    exit();
}

$id = (int)$_GET['id'];

// Fetch recipe
$stmt = $conn->prepare("SELECT * FROM recipes WHERE recipe_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: recipes.php");
    exit();
}

$row = $result->fetch_assoc();
$user = htmlspecialchars($_SESSION['user']);

/* Helpers */
function diffBadge($d) {
    $d = strtolower($d);
    if ($d === 'easy') return 'success';
    if ($d === 'medium') return 'warning';
    if ($d === 'hard') return 'danger';
    return 'secondary';
}

function cuisineEmoji($c) {
    $c = strtolower($c);
    if ($c === 'indian') return '🇮🇳';
    if ($c === 'italian') return '🇮🇹';
    if ($c === 'chinese') return '🇨🇳';
    if ($c === 'mexican') return '🇲🇽';
    if ($c === 'american') return '🇺🇸';
    return '🍽️';
}

function diffTime($d) {
    $d = strtolower($d);
    if ($d === 'easy') return '20–30 min';
    if ($d === 'medium') return '30–60 min';
    return '60+ min';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($row['title']) ?></title>

<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/main.css" rel="stylesheet">

<style>
body { background:#f5f5f5; }

.card-box {
    background:white;
    border-radius:16px;
    padding:30px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
}

.ingredient {
    display:inline-block;
    padding:6px 14px;
    margin:4px;
    background:#ffeaea;
    border-radius:20px;
}

.step {
    margin-bottom:12px;
}
</style>
</head>

<body>

<div class="container py-5">

<h2 class="mb-3"><?= htmlspecialchars($row['title']) ?></h2>

<div class="mb-3">
    <span class="badge bg-<?= diffBadge($row['difficulty']) ?>">
        <?= htmlspecialchars($row['difficulty']) ?>
    </span>

    <span class="ms-2">
        <?= cuisineEmoji($row['cuisine_type']) ?> <?= htmlspecialchars($row['cuisine_type']) ?>
    </span>

    <span class="ms-3">
        ⏱ <?= diffTime($row['difficulty']) ?>
    </span>
</div>

<div class="row g-4">

<!-- LEFT -->
<div class="col-lg-8">

<div class="card-box mb-4">
    <h4>Ingredients</h4>

    <?php
    $ingredients = explode(",", $row['ingredients']);
    foreach ($ingredients as $ing):
        $ing = trim($ing);
        if ($ing):
    ?>
        <span class="ingredient"><?= htmlspecialchars($ing) ?></span>
    <?php endif; endforeach; ?>
</div>

<div class="card-box">
    <h4>Instructions</h4>

    <?php
    $steps = explode(".", $row['instructions']);
    $i = 1;
    foreach ($steps as $step):
        $step = trim($step);
        if ($step):
    ?>
        <div class="step">
            <b>Step <?= $i ?>:</b> <?= htmlspecialchars($step) ?>
        </div>
    <?php $i++; endif; endforeach; ?>
</div>

</div>

<!-- RIGHT -->
<div class="col-lg-4">

<div class="card-box">
    <h5>Recipe Details</h5>

    <p><b>Diet:</b> <?= htmlspecialchars($row['dietary_preference']) ?></p>
    <p><b>Difficulty:</b> <?= htmlspecialchars($row['difficulty']) ?></p>
    <p><b>Cook Time:</b> <?= diffTime($row['difficulty']) ?></p>
    <p><b>Ingredients:</b> <?= count(explode(",", $row['ingredients'])) ?></p>

</div>

<div class="mt-3">
    <a href="recipes.php" class="btn btn-danger w-100">⬅ Back to Recipes</a>
</div>

</div>

</div>

</div>
<?php include 'cookie_consent.php'; ?>

</body>
</html>