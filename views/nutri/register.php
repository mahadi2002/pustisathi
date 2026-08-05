<?php $this->layout('layouts/public', ['title' => 'Nutritionist Registration']); ?>

<section class="card">
  <h1>Nutritionist হিসেবে Register করুন</h1>
  <p>Admin আপনার Credential/License তথ্য যাচাই করার পর Approve করবেন — এটি Self-service নয়। Patient দের Profile ও Plan দেখতে, আপনাকেও একজন সাধারণ Subscriber-এর মতোই ৳<?= e($dailyAmount) ?>/day (VAT/SD/SC-সহ) Subscription চালু রাখতে হবে।</p>
</section>

<?php if ($step === 'phone'): ?>
  <section class="card">
    <form method="post" action="/nutri/register/otp" class="field">
      <?= csrf_field() ?>
      <?= \App\Core\View::partial('partials/honeypot-fields') ?>

      <label for="mobile">আপনার Robi বা Airtel Number</label>
      <input type="tel" id="mobile" name="mobile" placeholder="01XXXXXXXXX" value="<?= e(old('mobile')) ?>" required>
      <small class="hint">শুধু Robi (018) ও Airtel (016) Number</small>
      <?php if ($err = error_for('mobile')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

      <label for="credentials">Credential / License তথ্য</label>
      <textarea id="credentials" name="credentials" rows="4" required><?= e(old('credentials')) ?></textarea>
      <?php if ($err = error_for('credentials')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

      <button type="submit" class="btn btn-block">OTP পাঠান →</button>
    </form>
  </section>
<?php else: ?>
  <section class="card">
    <h2>কোড দিন</h2>
    <p><?= e(substr((string) $mobile, 0, 5)) ?>XXXXXX নম্বরে একটি কোড পাঠানো হয়েছে।</p>

    <form method="post" action="/nutri/register/verify" class="field">
      <?= csrf_field() ?>
      <label for="otp">৬-সংখ্যার কোড</label>
      <input type="text" inputmode="numeric" pattern="[0-9]*" id="otp" name="otp" maxlength="6" required autofocus>
      <?php if ($err = error_for('otp')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      <button type="submit" class="btn btn-block">Verify করুন</button>
    </form>

    <form method="post" action="/nutri/register/otp" class="field">
      <?= csrf_field() ?>
      <input type="hidden" name="mobile" value="<?= e((string) $mobile) ?>">
      <input type="hidden" name="credentials" value="<?= e((string) $credentials) ?>">
      <button type="submit" class="btn btn-outline btn-block">কোড আবার পাঠান</button>
    </form>
  </section>
<?php endif; ?>
