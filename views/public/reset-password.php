<?php $this->layout('layouts/public', ['title' => 'নতুন Password দিন']); ?>

<section class="card">
  <h1>নতুন Password দিন</h1>

  <form method="post" action="/reset-password/<?= e($token) ?>" class="field">
    <?= csrf_field() ?>

    <label for="password">নতুন Password</label>
    <input type="password" id="password" name="password" minlength="8" required autofocus>
    <small class="hint">কমপক্ষে ৮ অক্ষর</small>
    <?php if ($err = error_for('password')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <label for="password_confirmation">Password আবার লিখুন</label>
    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required>
    <?php if ($err = error_for('password_confirmation')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <button type="submit" class="btn btn-block">Password পরিবর্তন করুন</button>
  </form>
</section>
