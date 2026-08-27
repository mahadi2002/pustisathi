<?php $this->layout('layouts/public', ['title' => 'Register করুন']); ?>

<section class="card">
  <h1>Account তৈরি করুন</h1>
  <p>সম্পূর্ণ ফ্রি — শুধু Email আর Password দিয়ে শুরু করুন।</p>

  <form method="post" action="/register" class="field">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('partials/honeypot-fields') ?>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
    <?php if ($err = error_for('email')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" minlength="8" required>
    <small class="hint">কমপক্ষে ৮ অক্ষর</small>
    <?php if ($err = error_for('password')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <label for="password_confirmation">Password আবার লিখুন</label>
    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required>
    <?php if ($err = error_for('password_confirmation')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <button type="submit" class="btn btn-block">Account তৈরি করুন</button>
  </form>

  <p class="text-muted section-note">আগে থেকেই Account থাকলে <a href="/login">Login করুন</a>।</p>
</section>
