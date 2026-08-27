<?php $this->layout('layouts/public', ['title' => 'Password Reset']); ?>

<section class="card">
  <h1>Password ভুলে গেছেন?</h1>
  <p>আপনার Email দিন — Reset করার একটি Link পাঠানো হবে।</p>

  <form method="post" action="/forgot-password" class="field">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('partials/honeypot-fields') ?>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
    <?php if ($err = error_for('email')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <button type="submit" class="btn btn-block">Reset Link পাঠান</button>
  </form>

  <p class="text-muted section-note"><a href="/login">Login-এ ফিরে যান</a></p>
</section>
