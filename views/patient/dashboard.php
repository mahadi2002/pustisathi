<?php $this->layout('layouts/app', ['title' => 'Dashboard']); ?>

<?php
$macros = $plan !== null ? (json_decode((string) $plan['macro_targets_json'], true) ?: []) : [];
$proteinKcal = ((float) ($macros['protein_g'] ?? 0)) * 4;
$carbKcal    = ((float) ($macros['carb_g'] ?? 0)) * 4;
$fatKcal     = ((float) ($macros['fat_g'] ?? 0)) * 9;
$totalKcal   = max(1.0, $proteinKcal + $carbKcal + $fatKcal);
?>

<section class="card dashboard-summary">
  <div class="plate-meter plate-pct-<?= pct_step($proteinKcal, $totalKcal) ?>">
    <div class="plate-fill"></div>
    <div class="plate-value"><?= e((string) ($plan['target_kcal'] ?? '—')) ?><br><small>kcal/day</small></div>
  </div>
  <div class="macro-legend">
    <p><strong>Protein:</strong> <?= e((string) ($macros['protein_g'] ?? '—')) ?> g</p>
    <p><strong>Carb:</strong> <?= e((string) ($macros['carb_g'] ?? '—')) ?> g</p>
    <p><strong>Fat:</strong> <?= e((string) ($macros['fat_g'] ?? '—')) ?> g</p>
  </div>
</section>

<?= \App\Core\View::partial('partials/plan-disclaimer') ?>

<?php if ($plan === null): ?>
  <section class="card">
    <p>এখনো কোনো Plan তৈরি হয়নি।</p>
    <a class="btn" href="/app/onboarding">প্রোফাইল দিন</a>
  </section>
<?php else: ?>
  <?php foreach (['breakfast' => 'সকালের নাস্তা', 'lunch' => 'দুপুরের খাবার', 'dinner' => 'রাতের খাবার', 'snack' => 'নাস্তা'] as $slot => $label): ?>
    <?php if (!empty($plan['meals'][$slot])): ?>
      <section class="card meal-card">
        <h3><?= e($label) ?></h3>
        <ul class="meal-items">
          <?php foreach ($plan['meals'][$slot] as $item): ?>
            <li><?= e($item['name_bn']) ?> — <?= e(number_format((float) $item['portion_grams'], 0)) ?>g</li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
  <?php endforeach; ?>

  <a class="btn btn-outline btn-block" href="/app/plan">সম্পূর্ণ Plan দেখুন</a>
<?php endif; ?>
