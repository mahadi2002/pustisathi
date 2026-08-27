<?php $this->layout('layouts/app', ['title' => 'Dashboard']); ?>

<?php
$macros = $plan !== null ? (json_decode((string) $plan['macro_targets_json'], true) ?: []) : [];
$proteinG = (float) ($macros['protein_g'] ?? 0);
$carbG    = (float) ($macros['carb_g'] ?? 0);
$fatG     = (float) ($macros['fat_g'] ?? 0);
$proteinKcal = $proteinG * 4;
$carbKcal    = $carbG * 4;
$fatKcal     = $fatG * 9;
$totalKcal   = max(1.0, $proteinKcal + $carbKcal + $fatKcal);

$mealLabels = ['breakfast' => 'সকালের নাস্তা', 'lunch' => 'দুপুরের খাবার', 'dinner' => 'রাতের খাবার', 'snack' => 'নাস্তা'];
$regionLabel = null;
if (!empty($profile['region_id'])) {
    $region = \App\Core\Db::first('SELECT district, upazila FROM regions WHERE id = ?', [$profile['region_id']]);
    $regionLabel = $region !== null ? $region['district'] . ', ' . $region['upazila'] : null;
}
?>

<section class="stat-grid">
  <div class="stat-card">
    <span class="stat-value"><?= e((string) ($plan['target_kcal'] ?? '—')) ?></span>
    <span class="stat-label">Target kcal/day</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e((string) round($bmr)) ?></span>
    <span class="stat-label">BMR (kcal)</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e(number_format($bmi, 1)) ?></span>
    <span class="stat-label">BMI (<?= e($bmiCategory) ?>)</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e(number_format($activityMultiplier, 2)) ?>×</span>
    <span class="stat-label"><?= e($activityLabel) ?></span>
  </div>
</section>

<section class="card">
  <h2>দৈনিক Macro লক্ষ্য</h2>
  <div class="macro-bars">
    <div class="macro-bar-row">
      <div class="macro-bar-head">
        <span class="macro-name">Protein</span>
        <span class="macro-detail"><?= e(number_format($proteinG, 1)) ?>g &middot; <?= pct_step($proteinKcal, $totalKcal) ?>%</span>
      </div>
      <div class="macro-bar-track"><div class="macro-bar-fill protein w-pct-<?= pct_step($proteinKcal, $totalKcal) ?>"></div></div>
    </div>
    <div class="macro-bar-row">
      <div class="macro-bar-head">
        <span class="macro-name">Carbohydrate</span>
        <span class="macro-detail"><?= e(number_format($carbG, 1)) ?>g &middot; <?= pct_step($carbKcal, $totalKcal) ?>%</span>
      </div>
      <div class="macro-bar-track"><div class="macro-bar-fill carb w-pct-<?= pct_step($carbKcal, $totalKcal) ?>"></div></div>
    </div>
    <div class="macro-bar-row">
      <div class="macro-bar-head">
        <span class="macro-name">Fat</span>
        <span class="macro-detail"><?= e(number_format($fatG, 1)) ?>g &middot; <?= pct_step($fatKcal, $totalKcal) ?>%</span>
      </div>
      <div class="macro-bar-track"><div class="macro-bar-fill fat w-pct-<?= pct_step($fatKcal, $totalKcal) ?>"></div></div>
    </div>
  </div>
</section>

<?php if ($conditionTips !== []): ?>
  <section class="card">
    <h2>আপনার প্ল্যানে যা বিবেচনা করা হয়েছে</h2>
    <?php foreach ($conditionTips as $ct): ?>
      <div class="tip-card">
        <span class="tip-label"><?= e($ct['label']) ?></span>
        <?php if ($ct['tip']): ?><p><?= e($ct['tip']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?= \App\Core\View::partial('partials/plan-disclaimer') ?>

<?php if ($plan === null): ?>
  <section class="card">
    <p>এখনো কোনো Plan তৈরি হয়নি।</p>
    <a class="btn" href="/app/onboarding">প্রোফাইল দিন</a>
  </section>
<?php else: ?>
  <?php foreach ($mealLabels as $slot => $label): ?>
    <?php if (!empty($plan['meals'][$slot])): ?>
      <details class="meal-details" <?= $slot === 'breakfast' ? 'open' : '' ?>>
        <summary><?= e($label) ?></summary>
        <ul class="meal-items">
          <?php foreach ($plan['meals'][$slot] as $item): ?>
            <li><?= e($item['name_bn']) ?> — <?= e(number_format((float) $item['portion_grams'], 0)) ?>g</li>
          <?php endforeach; ?>
        </ul>
      </details>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if ($planAgeDays !== null): ?>
    <p class="text-muted section-note">
      <?= $planAgeDays <= 0 ? 'আজই তৈরি হয়েছে' : 'তৈরি হয়েছে ' . bn_num($planAgeDays) . ' দিন আগে' ?>
    </p>
  <?php endif; ?>

  <div class="button-row">
    <a class="btn btn-outline btn-block" href="/app/plan">সম্পূর্ণ Plan দেখুন</a>
    <form method="post" action="/app/plan/regenerate" class="btn-block">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-block">নতুন Plan তৈরি করুন</button>
    </form>
  </div>
<?php endif; ?>

<section class="card">
  <h2>আপনার প্রোফাইল</h2>
  <dl class="profile-summary-grid">
    <div><dt>বয়স</dt><dd><?= e((string) $profile['age']) ?></dd></div>
    <div><dt>উচ্চতা</dt><dd><?= e((string) $profile['height_cm']) ?> সেমি</dd></div>
    <div><dt>ওজন</dt><dd><?= e((string) $profile['weight_kg']) ?> কেজি</dd></div>
    <div><dt>বাজেট</dt><dd><?= e(ucfirst((string) $profile['budget_tier'])) ?></dd></div>
    <?php if ($regionLabel): ?><div><dt>এলাকা</dt><dd><?= e($regionLabel) ?></dd></div><?php endif; ?>
  </dl>
  <a class="btn btn-outline" href="/app/onboarding">প্রোফাইল Update করুন</a>
</section>

<section class="card">
  <h2>নিউট্রিশনিস্টের সাথে যুক্ত হন</h2>
  <p class="text-muted section-note">এই কোডটি আপনার Nutritionist কে দিন — তিনি এটি দিয়ে আপনার Profile দেখতে ও নোট রাখতে পারবেন।</p>
  <?php if (!empty($profile['share_code'])): ?>
    <div class="share-code-display">
      <code class="share-code-value"><?= e($profile['share_code']) ?></code>
    </div>
  <?php else: ?>
    <p class="text-muted">এখনো কোনো কোড তৈরি হয়নি।</p>
  <?php endif; ?>
  <form method="post" action="/app/share-code">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline">
      <?= !empty($profile['share_code']) ? 'নতুন কোড তৈরি করুন' : 'কোড তৈরি করুন' ?>
    </button>
  </form>
</section>
