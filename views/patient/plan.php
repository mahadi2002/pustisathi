<?php $this->layout('layouts/app', ['title' => 'আপনার Diet Plan']); ?>

<?php if ($plan === null): ?>
  <section class="card">
    <p>এখনো কোনো Plan তৈরি হয়নি।</p>
    <a class="btn" href="/app/onboarding">প্রোফাইল দিন</a>
  </section>
<?php else: ?>
  <?php
  $macros = json_decode((string) $plan['macro_targets_json'], true) ?: [];
  $conditionCodes = json_decode((string) $plan['condition_codes'], true) ?: [];
  ?>
  <section class="card">
    <h1>আপনার Diet Plan</h1>
    <p>দৈনিক লক্ষ্য: <strong><?= e((string) $plan['target_kcal']) ?> kcal</strong>
       — Protein <?= e((string) ($macros['protein_g'] ?? '—')) ?>g,
       Carb <?= e((string) ($macros['carb_g'] ?? '—')) ?>g,
       Fat <?= e((string) ($macros['fat_g'] ?? '—')) ?>g</p>
    <?php if ($conditionCodes !== []): ?>
      <p class="text-muted">বিবেচনায় নেওয়া হয়েছে: <?= e(implode(', ', array_map('condition_label', $conditionCodes))) ?></p>
    <?php endif; ?>
  </section>

  <?= \App\Core\View::partial('partials/plan-disclaimer') ?>

  <?php foreach (['breakfast' => 'সকালের নাস্তা', 'lunch' => 'দুপুরের খাবার', 'dinner' => 'রাতের খাবার', 'snack' => 'নাস্তা'] as $slot => $label): ?>
    <?php if (!empty($plan['meals'][$slot])): ?>
      <section class="card meal-card">
        <h3><?= e($label) ?></h3>
        <div class="compare-table-wrap">
          <table class="compare-table">
            <thead><tr><th>খাবার</th><th>পরিমাণ</th><th>kcal</th><th>Protein</th></tr></thead>
            <tbody>
              <?php foreach ($plan['meals'][$slot] as $item):
                $grams = (float) $item['portion_grams'];
                $kcal  = round($grams / 100 * (float) $item['per_100g_kcal']);
                $protein = round($grams / 100 * (float) $item['per_100g_protein_g'], 1);
              ?>
                <tr>
                  <td><?= e($item['name_bn']) ?> <span class="text-muted">(<?= e($item['name_en']) ?>)</span></td>
                  <td><?= e(number_format($grams, 0)) ?>g</td>
                  <td><?= e((string) $kcal) ?></td>
                  <td><?= e((string) $protein) ?>g</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    <?php endif; ?>
  <?php endforeach; ?>

  <form method="post" action="/app/plan/regenerate" class="field">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline btn-block">নতুন Plan তৈরি করুন</button>
  </form>
  <p class="text-muted section-note">প্রোফাইলের তথ্য না বদলালে নতুন Plan আগেরটার মতোই হবে — এই Engine Deterministic।</p>
<?php endif; ?>
