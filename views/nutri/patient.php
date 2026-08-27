<?php $this->layout('layouts/public', ['title' => 'Patient — ' . $patient['email']]); ?>

<p class="section-note"><a href="/nutri">&larr; Roster-এ ফিরে যান</a></p>

<section class="card">
  <h1><?= e($patient['email']) ?></h1>
  <p class="text-muted section-note">যুক্ত হয়েছে <?= e(bn_date($linkedAt)) ?></p>

  <?php if ($profile !== null): ?>
    <dl class="profile-summary-grid">
      <div><dt>বয়স</dt><dd><?= e((string) $profile['age']) ?></dd></div>
      <div><dt>উচ্চতা</dt><dd><?= e((string) $profile['height_cm']) ?> সেমি</dd></div>
      <div><dt>ওজন</dt><dd><?= e((string) $profile['weight_kg']) ?> কেজি</dd></div>
      <div><dt>বাজেট</dt><dd><?= e(ucfirst((string) $profile['budget_tier'])) ?></dd></div>
    </dl>
  <?php else: ?>
    <p class="text-muted">এই Patient এখনো তাদের প্রোফাইল দেননি।</p>
  <?php endif; ?>
</section>

<section class="card">
  <h2>নতুন Clinical Note</h2>
  <form method="post" action="/nutri/patients/<?= e((string) $patient['id']) ?>/notes">
    <?= csrf_field() ?>
    <div class="field">
      <label for="note" class="sr-only">নোট</label>
      <textarea id="note" name="note" rows="4" placeholder="এই Patient সম্পর্কে পর্যবেক্ষণ বা পরামর্শ লিখুন..." required><?= e(old('note')) ?></textarea>
      <?php if ($err = error_for('note')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
    </div>
    <button type="submit" class="btn">নোট যোগ করুন</button>
  </form>
</section>

<section class="card">
  <h2>পূর্ববর্তী নোট</h2>
  <?php if ($notes === []): ?>
    <p class="text-muted">এখনো কোনো নোট লেখা হয়নি।</p>
  <?php else: ?>
    <ul class="notes-list">
      <?php foreach ($notes as $note): ?>
        <li class="note-item">
          <p class="note-text"><?= nl2br(e($note['note'])) ?></p>
          <span class="text-muted note-date"><?= e(bn_date($note['created_at'], true)) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
