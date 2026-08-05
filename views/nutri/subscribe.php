<?php $this->layout('layouts/public', ['title' => 'Nutritionist Subscription']); ?>

<section class="card">
  <h1>আপনার Subscription চালু করুন</h1>
  <p>Approve হয়ে গেছে — এখন Patient দেখতে ও Plan বানাতে, আপনাকেও একজন Subscriber-এর মতোই ৳<?= e($dailyAmount) ?>/day (VAT/SD/SC-সহ) Subscription চালু করতে হবে, ঠিক রোগীদের মতোই।</p>
</section>

<?php if ($step === 'phone'): ?>
  <section class="card">
    <h2>আপনার Robi বা Airtel Number দিন</h2>
    <form method="post" action="/nutri/subscribe/otp" class="field">
      <?= csrf_field() ?>
      <label for="mobile">Mobile Number</label>
      <input type="tel" id="mobile" name="mobile" placeholder="01XXXXXXXXX" value="<?= e(old('mobile')) ?>" required>
      <small class="hint">শুধু Robi (018) ও Airtel (016) Number</small>
      <?php if ($err = error_for('mobile')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      <button type="submit" class="btn btn-block">OTP পাঠান →</button>
    </form>
  </section>
<?php else: ?>
  <section class="card">
    <h2>কোড দিন</h2>
    <p><?= e(substr((string) $mobile, 0, 5)) ?>XXXXXX নম্বরে একটি কোড পাঠানো হয়েছে।</p>
    <form method="post" action="/nutri/subscribe/verify" class="field">
      <?= csrf_field() ?>
      <label for="otp">৬-সংখ্যার কোড</label>
      <input type="text" inputmode="numeric" pattern="[0-9]*" id="otp" name="otp" maxlength="6" required autofocus>
      <?php if ($err = error_for('otp')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      <button type="submit" class="btn btn-block">Verify করুন</button>
    </form>
    <form method="post" action="/nutri/subscribe/otp" class="field">
      <?= csrf_field() ?>
      <input type="hidden" name="mobile" value="<?= e((string) $mobile) ?>">
      <button type="submit" class="btn btn-outline btn-block">কোড আবার পাঠান</button>
    </form>
  </section>
<?php endif; ?>
