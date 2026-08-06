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
    <a class="brand" href="/">
      <?= \App\Core\View::partial('partials/brand-mark') ?>
      পুষ্টিসাথী
    </a>
    <a class="price-pill" href="/subscribe" title="Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC)">
      <span class="price-pill-short">৳<?= e($dailyAmount) ?>/day</span>
      <span class="price-pill-full">Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC)</span>
    </a>

    <nav class="nav-links">
      <a href="/calculator">BMI Calculator</a>
      <a href="/foods">Food Search</a>
      <?php if (\App\Core\Session::userId() === null): ?>
        <a href="/subscribe">Subscribe / Login</a>
      <?php else: ?>
        <form method="post" action="/auth/logout" class="nav-logout-form">
          <?= csrf_field() ?>
          <button type="submit" class="link-button">Logout</button>
        </form>
      <?php endif; ?>
    </nav>

    <?= \App\Core\View::partial('partials/theme-toggle-button') ?>

    <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="nav-links-mobile" aria-label="Menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
  </div>

  <div class="container">
    <nav class="nav-links-mobile" id="nav-links-mobile">
      <a href="/calculator">BMI Calculator</a>
      <a href="/foods">Food Search</a>
      <?php if (\App\Core\Session::userId() === null): ?>
        <a href="/subscribe">Subscribe / Login</a>
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
  Robi &amp; Airtel Bangladesh<br>
  &copy; <?= date('Y') ?> PustiSathi — সর্বস্বত্ব সংরক্ষিত</p>
  <p>⚠️ Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC) আপনার Robi/Airtel মোবাইল ব্যালেন্স থেকে সরাসরি কাটা হবে।
  Unsubscribe করতে STOP লিখে 16216 নম্বরে SMS করুন।</p>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
