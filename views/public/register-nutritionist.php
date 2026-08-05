<?php $this->layout('layouts/public', ['title' => 'Nutritionist Registration']); ?>

<section class="card">
  <h1>Nutritionist হিসেবে Register করুন</h1>
  <p>Submit করার পর আপনার Application Admin অনুমোদনের অপেক্ষায় থাকবে — এটি Self-service নয়।</p>

  <form method="post" action="/auth/register-nutritionist" class="field">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('partials/honeypot-fields') ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required>
    <?php if ($err = error_for('email')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" minlength="8" required>
    <?php if ($err = error_for('password')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <label for="credentials">Credential / License তথ্য</label>
    <textarea id="credentials" name="credentials" rows="4" required><?= e(old('credentials')) ?></textarea>
    <?php if ($err = error_for('credentials')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <button type="submit" class="btn btn-block">Application জমা দিন</button>
  </form>
</section>
