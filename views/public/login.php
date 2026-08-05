<?php $this->layout('layouts/public', ['title' => 'Login']); ?>

<section class="card">
  <h1>Login</h1>
  <p>Subscriber হলে Mobile Number দিয়ে নিচে যান — Nutritionist বা Admin হলে Email/Password ব্যবহার করুন।</p>

  <form method="post" action="/auth/login" class="field">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label for="mobile">Mobile Number (Subscriber)</label>
    <input type="tel" id="mobile" name="mobile" placeholder="01XXXXXXXXX">
    <button type="submit" class="btn btn-block">Continue</button>
  </form>

  <hr>

  <form method="post" action="/auth/login" class="field">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label for="email">Email (Nutritionist / Admin)</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password">
    <button type="submit" class="btn btn-outline btn-block">Login</button>
  </form>

  <p><a href="/auth/register-nutritionist">Nutritionist হিসেবে Register করুন</a></p>
</section>
