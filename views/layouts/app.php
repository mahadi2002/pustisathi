<!doctype html>
<html lang="bn" class="no-js">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $appName) ?> — <?= e($appName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container">
    <a class="brand" href="/app/dashboard">পুষ্টিসাথী</a>
    <nav class="nav-links">
      <a href="/app/dashboard">Dashboard</a>
      <a href="/app/plan">Plan</a>
      <form method="post" action="/auth/logout" class="nav-logout-form">
        <?= csrf_field() ?>
        <button type="submit" class="link-button">Logout</button>
      </form>
    </nav>
    <?= \App\Core\View::partial('partials/theme-toggle-button') ?>
  </div>
</header>

<main class="container page-main">
<?php if (!empty($notice)): ?>
  <div class="alert alert-<?= e($notice['type']) ?>"><?= e($notice['text']) ?></div>
<?php endif; ?>
<?= $content ?>
</main>

<nav class="bottom-tabs">
  <a href="/app/dashboard" class="<?= ($currentPath ?? '') === '/app/dashboard' ? 'active' : '' ?>">🏠<br>Dashboard</a>
  <a href="/app/plan" class="<?= ($currentPath ?? '') === '/app/plan' ? 'active' : '' ?>">🍽️<br>Plan</a>
</nav>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
