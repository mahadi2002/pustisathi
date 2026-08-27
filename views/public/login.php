<?php $this->layout('layouts/public', ['title' => 'Login করুন']); ?>

<section class="card">
  <h1>Login করুন</h1>

  <form method="post" action="/login" class="field">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next ?? '') ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
    <?php if ($err = error_for('email')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <?php if ($err = error_for('password')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <button type="submit" class="btn btn-block">Login করুন</button>
  </form>

  <p class="text-muted section-note">
    <a href="/forgot-password">Password ভুলে গেছেন?</a><br>
    Account নেই? <a href="/register">Register করুন</a>
  </p>
</section>
