<!doctype html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $appName) ?> — <?= e($appName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container">
    <a class="brand" href="/">পুষ্টিসাথী</a>
    <a class="price-pill" href="/subscribe">মাত্র ৳<?= e($dailyAmount) ?>/day</a>
    <nav class="nav-links">
      <a href="/calculator">BMI Calculator</a>
      <a href="/subscribe">Subscribe</a>
      <a href="/auth/login">Login</a>
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
  <p>⚠️ Daily ৳<?= e($dailyAmount) ?> (VAT/SD/SC-সহ) আপনার Robi/Airtel মোবাইল ব্যালেন্স থেকে সরাসরি কাটা হবে।
  Unsubscribe করতে STOP লিখে 16216 নম্বরে SMS করুন।</p>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
