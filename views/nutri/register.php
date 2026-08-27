<?php $this->layout('layouts/public', ['title' => 'Nutritionist Registration']); ?>

<section class="card">
  <h1>Nutritionist হিসেবে Register করুন</h1>
  <p>Admin আপনার Credential/License তথ্য যাচাই করার পর Approve করবেন — এটি Self-service নয়।</p>
</section>

<section class="card">
  <form method="post" action="/nutri/register" class="field">
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

    <label for="credentials">Credential / License তথ্য</label>
    <textarea id="credentials" name="credentials" rows="4" required><?= e(old('credentials')) ?></textarea>
    <?php if ($err = error_for('credentials')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <button type="submit" class="btn btn-block">Register করুন</button>
  </form>

  <p class="text-muted section-note">আগে থেকেই Account থাকলে <a href="/login">Login করুন</a>।</p>
</section>
