<?php
/** Variabel opsional sebelum include: $title, $active, $public, $base */
$base   = $base ?? '../';
$title  = $title ?? 'ModifTrack';
$active = $active ?? '';
$public = $public ?? false;
$shell  = !$public && !empty($_SESSION['user_id']);   // halaman internal memakai kerangka kuning
$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | ModifTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:ital,wght@1,700;1,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<?php if ($shell):
  $links = ['dashboard' => ['Home', 'dashboard.php'], 'kendaraan' => ['Garasi', 'kendaraan.php'],
            'katalog' => ['Katalog', 'katalog.php'], 'build' => ['Build Plan', 'build_plan.php']];
  if (($_SESSION['role'] ?? '') === 'admin') $links['admin'] = ['Admin', 'admin_katalog.php'];
?>
<div class="lp shell">
  <header class="lp-nav">
    <a class="lp-logo" href="<?= $base ?>pages/dashboard.php" aria-label="ModifTrack">MT</a>
    <nav aria-label="Navigasi utama">
      <?php foreach ($links as $k => [$label, $file]): ?>
        <a href="<?= $base ?>pages/<?= $file ?>" class="<?= $active === $k ? 'on' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="lp-login" href="<?= $base ?>auth/logout.php">Keluar (<?= e($_SESSION['username']) ?>)</a>
  </header>
  <main class="content">
<?php else: ?>
<main class="wrap">
<?php endif; ?>
<?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>