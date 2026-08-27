<?php $this->layout('layouts/public', ['title' => 'খাবারের পুষ্টিমান খুঁজুন']); ?>

<section class="card">
  <h1>খাবারের পুষ্টিমান খুঁজুন</h1>
  <p>নাম লিখে যেকোনো খাবারের প্রতি ১০০ গ্রামে ক্যালরি ও Macro দেখুন। Guest হিসেবে দিনে <?= e((string) config('app.foods.search_daily_cap_guest', 10)) ?> বার পর্যন্ত খুঁজতে পারবেন — Login করা থাকলে কোনো সীমা নেই।</p>

  <form method="get" action="/foods" id="food-search-form" class="field food-search-form">
    <label for="q" class="sr-only">খাবারের নাম</label>
    <input type="text" id="q" name="q" placeholder="যেমন: ভাত, ডিম, পালং শাক..." value="<?= e($query) ?>" autofocus autocomplete="off">
    <button type="submit" class="btn">খুঁজুন</button>
  </form>
  <p class="search-status" id="food-search-status"></p>
</section>

<div id="food-results">
<?php if ($capped): ?>
  <div class="alert alert-info">
    আজকের জন্য Free খোঁজার সীমা শেষ হয়ে গেছে।
    <a href="/login">Login করুন</a> — Unlimited Search সহ পুরো Diet Plan পাবেন।
  </div>
<?php elseif ($query !== '' && $results === []): ?>
  <div class="alert alert-info">"<?= e($query) ?>" এর সাথে মিলে এমন কোনো খাবার পাওয়া যায়নি।</div>
<?php elseif ($results !== []): ?>
  <div class="compare-table-wrap">
    <table class="compare-table">
      <thead>
        <tr>
          <th>খাবার</th>
          <th>ক্যালরি (kcal)</th>
          <th>Protein (g)</th>
          <th>Carb (g)</th>
          <th>Fat (g)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $food): ?>
          <tr>
            <td><?= e($food['name_bn']) ?> <span class="text-muted">(<?= e($food['name_en']) ?>)</span></td>
            <td><?= e(number_format((float) $food['per_100g_kcal'], 1)) ?></td>
            <td><?= e(number_format((float) $food['per_100g_protein_g'], 1)) ?></td>
            <td><?= e(number_format((float) $food['per_100g_carb_g'], 1)) ?></td>
            <td><?= e(number_format((float) $food['per_100g_fat_g'], 1)) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-muted section-note">মান প্রতি ১০০ গ্রামে, আনুমানিক — যাচাইকৃত তথ্যভাণ্ডার আসার আগ পর্যন্ত রেফারেন্স হিসেবে ব্যবহার করুন।</p>
<?php endif; ?>
</div>

<section class="card">
  <h2>সম্পূর্ণ Diet Plan চান?</h2>
  <p>শুধু পুষ্টিমান নয় — আপনার শরীর, বাজেট আর এলাকা বুঝে সাজানো সম্পূর্ণ দৈনিক খাবারের তালিকা পেতে ফ্রি Account খুলুন।</p>
  <?= \App\Core\View::partial('partials/login-cta') ?>
</section>
