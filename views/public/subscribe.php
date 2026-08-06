<?php $this->layout('layouts/public', ['title' => 'Subscribe করুন']); ?>

<?php if ($step === 'phone'): ?>
  <?= \App\Core\View::partial('partials/subscribe-box', ['next' => $next]) ?>
<?php else: ?>
  <section class="card">
    <h2>কোড দিন</h2>
    <p><?= e(substr((string) $mobile, 0, 5)) ?>XXXXXX নম্বরে একটি কোড পাঠানো হয়েছে।</p>
    <?= \App\Core\View::partial('partials/price-line') ?>

    <form method="post" action="/subscribe/verify" class="field">
      <?= csrf_field() ?>
      <label for="otp">৬-সংখ্যার কোড</label>
      <input type="text" inputmode="numeric" pattern="[0-9]*" id="otp" name="otp" maxlength="6" required autofocus>
      <?php if ($err = error_for('otp')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      <button type="submit" class="btn btn-block">Verify করুন</button>
    </form>

    <form method="post" action="/subscribe/otp" class="field">
      <?= csrf_field() ?>
      <?= \App\Core\View::partial('partials/honeypot-fields') ?>
      <input type="hidden" name="mobile" value="<?= e((string) $mobile) ?>">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <button type="submit" class="btn btn-outline btn-block">কোড আবার পাঠান</button>
    </form>
  </section>
<?php endif; ?>
