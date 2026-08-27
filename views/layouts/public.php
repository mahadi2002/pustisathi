<!doctype html>
<html lang="bn" class="no-js">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $appName) ?> — <?= e($appName) ?></title>
<link rel="stylesheet" href="<?= e(asset('dist/app.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container">
    <a class="brand" href="/">
      <?= \App\Core\View::partial('partials/brand-mark') ?>
      পুষ্টিসাথী
    </a>

    <nav class="nav-links">
      <a href="/calculator">BMI Calculator</a>
      <a href="/foods">Food Search</a>
      <?php if (\App\Core\Session::userId() === null): ?>
        <a href="/login">Login</a>
        <a href="/register" class="btn btn-accent nav-cta">Register</a>
      <?php else: ?>
        <form method="post" action="/auth/logout" class="nav-logout-form">
          <?= csrf_field() ?>
          <button type="submit" class="link-button">Logout</button>
        </form>
      <?php endif; ?>
    </nav>

    <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="nav-links-mobile" aria-label="Menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
  </div>

  <div class="container">
    <nav class="nav-links-mobile" id="nav-links-mobile">
      <a href="/calculator">BMI Calculator</a>
      <a href="/foods">Food Search</a>
      <?php if (\App\Core\Session::userId() === null): ?>
        <a href="/login">Login</a>
        <a href="/register">Register</a>
      <?php else: ?>
        <form method="post" action="/auth/logout" class="nav-logout-form">
          <?= csrf_field() ?>
          <button type="submit" class="link-button">Logout</button>
        </form>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container page-main">
<?php if (!empty($notice)): ?>
  <div class="alert alert-<?= e($notice['type']) ?>"><?= e($notice['text']) ?></div>
<?php endif; ?>
<?= $content ?>
</main>

<footer class="container site-footer">
  <p>Privacy Policy | Terms &amp; Conditions | Contact Us<br>
  &copy; <?= date('Y') ?> PustiSathi — সর্বস্বত্ব সংরক্ষিত</p>
  <p class="text-muted">⚠️ প্রতিটি প্ল্যান সাধারণ নির্দেশনা — এটি চিকিৎসা পরামর্শ নয়। জটিল স্বাস্থ্য সমস্যায় ডাক্তার বা ডায়েটিশিয়ানের পরামর্শ নিন।</p>
</footer>

<script src="<?= e(asset('dist/app.js')) ?>" defer></script>
</body>
</html>
