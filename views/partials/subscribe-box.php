<section class="card">
  <h2>আপনার Robi বা Airtel Number দিন</h2>
  <p>Instant Access পাবেন সব Nutrition Content-এ!</p>

  <form method="post" action="/subscribe/otp" class="field">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('partials/honeypot-fields') ?>
    <input type="hidden" name="next" value="<?= e($next ?? '/app/dashboard') ?>">
    <label for="mobile">Mobile Number</label>
    <input type="tel" id="mobile" name="mobile" placeholder="01XXXXXXXXX" value="<?= e(old('mobile')) ?>" required>
    <small class="hint">শুধু Robi (016/019) ও Airtel (017) Number</small>
    <?php if ($err = error_for('mobile')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <p>⚡ Daily মাত্র ৳<?= e($dailyAmount) ?> — যেকোনো সময় Unsubscribe করুন</p>
    <button type="submit" class="btn btn-block">OTP পাঠান →</button>
  </form>
</section>
