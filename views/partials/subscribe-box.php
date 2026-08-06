<section class="card">
  <h2>আপনার Robi বা Airtel Number দিন</h2>
  <p>Instant Access পাবেন সব Nutrition Content-এ!</p>

  <form method="post" action="/subscribe/otp" class="field">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('partials/honeypot-fields') ?>
    <input type="hidden" name="next" value="<?= e($next ?? '/app/dashboard') ?>">
    <label for="mobile">Mobile Number</label>
    <input type="tel" id="mobile" name="mobile" placeholder="01XXXXXXXXX" value="<?= e(old('mobile')) ?>" required>
    <small class="hint">শুধু Robi (018) ও Airtel (016) Number</small>
    <?php if ($err = error_for('mobile')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>

    <p>⚡ Daily ৳<?= e($dailyAmount) ?> (Incl. VAT, SD &amp; SC) — যেকোনো সময় Unsubscribe করুন</p>
    <button type="submit" class="btn btn-accent btn-block">Subscribe Now</button>
  </form>
</section>
