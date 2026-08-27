<?php $this->layout('layouts/public', ['title' => 'Nutritionist Dashboard']); ?>

<section class="card">
  <h1>স্বাগতম, Nutritionist!</h1>
  <p>নিচে আপনার Patient Roster ও Clinical Notes — একটি Patient-এর কোড দিয়ে যুক্ত হন, এরপর তাদের Profile দেখুন ও নোট লিখুন।</p>
</section>

<section class="stat-grid">
  <div class="stat-card">
    <span class="stat-value"><?= e(bn_num($patientCount)) ?></span>
    <span class="stat-label">যুক্ত Patient</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e(bn_num($pendingNotes)) ?></span>
    <span class="stat-label">নোট বাকি আছে এমন Patient</span>
  </div>
</section>

<section class="card">
  <h2>কোড দিয়ে Patient যুক্ত করুন</h2>
  <p class="text-muted section-note">Patient তাদের Dashboard থেকে একটি ৮-অক্ষরের কোড দেখতে পাবেন — সেটি এখানে দিন।</p>
  <form method="post" action="/nutri/link" class="share-code-form">
    <?= csrf_field() ?>
    <input type="text" name="code" placeholder="যেমন A3F9C210" maxlength="8" minlength="8" required autocapitalize="characters" class="share-code-input">
    <button type="submit" class="btn btn-accent">যুক্ত করুন</button>
  </form>
</section>

<section class="card">
  <h2>আপনার Patient Roster</h2>
  <?php if ($roster === []): ?>
    <p class="text-muted">এখনো কোনো Patient যুক্ত হয়নি। উপরের ফর্মে কোড দিয়ে শুরু করুন।</p>
  <?php else: ?>
    <ul class="roster-list">
      <?php foreach ($roster as $p): ?>
        <li class="roster-item">
          <a href="/nutri/patients/<?= e((string) $p['id']) ?>" class="roster-link">
            <div class="roster-main">
              <span class="roster-email"><?= e($p['email']) ?></span>
              <span class="text-muted roster-meta">
                যুক্ত হয়েছে <?= e(bn_date($p['linked_at'])) ?>
                &middot;
                সর্বশেষ সক্রিয় <?= $p['last_active'] ? e(bn_date($p['last_active'], true)) : 'অজানা' ?>
              </span>
            </div>
            <?php if ((int) $p['notes_count'] === 0): ?>
              <span class="status-pill grace">নোট বাকি</span>
            <?php else: ?>
              <span class="status-pill active"><?= e(bn_num((int) $p['notes_count'])) ?> নোট</span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
