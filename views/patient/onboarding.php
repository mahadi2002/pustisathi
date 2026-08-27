<?php $this->layout('layouts/app', ['title' => 'প্রোফাইল']); ?>

<section class="card">
  <h1><?= $profile !== null ? 'প্রোফাইল Update করুন' : 'আপনার প্রোফাইল দিন' ?></h1>
  <p>এই তথ্যের ভিত্তিতেই আপনার Diet Plan তৈরি হবে। পরে যেকোনো সময় Update করতে পারবেন।</p>

  <form method="post" action="/app/onboarding" class="onboarding-form">
    <?= csrf_field() ?>

    <fieldset class="onboarding-fieldset">
      <legend><span class="onboarding-step-badge">১</span> শারীরিক তথ্য</legend>

      <div class="field">
        <label for="age">বয়স</label>
        <input type="number" id="age" name="age" min="1" max="120" value="<?= e(old('age', (string) ($profile['age'] ?? ''))) ?>" required>
        <?php if ($err = error_for('age')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="sex">লিঙ্গ</label>
        <select id="sex" name="sex" required>
          <?php foreach (['male' => 'পুরুষ', 'female' => 'নারী', 'other' => 'অন্যান্য'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($profile['sex'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="height_cm">উচ্চতা (সেমি)</label>
        <input type="number" id="height_cm" name="height_cm" min="50" max="250" step="0.1" value="<?= e(old('height_cm', (string) ($profile['height_cm'] ?? ''))) ?>" required>
        <?php if ($err = error_for('height_cm')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="weight_kg">ওজন (কেজি)</label>
        <input type="number" id="weight_kg" name="weight_kg" min="10" max="300" step="0.1" value="<?= e(old('weight_kg', (string) ($profile['weight_kg'] ?? ''))) ?>" required>
        <?php if ($err = error_for('weight_kg')): ?><span class="error"><?= e($err) ?></span><?php endif; ?>
      </div>

      <div class="field">
        <label for="activity_level">Activity Level</label>
        <select id="activity_level" name="activity_level" required>
          <?php foreach ([
            'sedentary' => 'Sedentary (বসে কাজ)',
            'light' => 'Light (হালকা পরিশ্রম)',
            'moderate' => 'Moderate (মাঝারি পরিশ্রম)',
            'active' => 'Active (কায়িক পরিশ্রম)',
            'very_active' => 'Very Active (ভারী পরিশ্রম)',
          ] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($profile['activity_level'] ?? 'moderate') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </fieldset>

    <fieldset class="onboarding-fieldset">
      <legend><span class="onboarding-step-badge">২</span> এলাকা ও বাজেট</legend>

      <div class="field">
        <label for="region_id">এলাকা</label>
        <select id="region_id" name="region_id">
          <option value="">— নির্বাচন করুন —</option>
          <?php foreach ($regions as $region): ?>
            <option value="<?= e((string) $region['id']) ?>" <?= (string) ($profile['region_id'] ?? '') === (string) $region['id'] ? 'selected' : '' ?>>
              <?= e($region['district']) ?>, <?= e($region['upazila']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="budget_tier">দৈনিক খাবারের বাজেট</label>
        <select id="budget_tier" name="budget_tier" required>
          <?php foreach (['low' => 'Low', 'mid' => 'Mid', 'high' => 'High'] as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($profile['budget_tier'] ?? 'mid') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </fieldset>

    <fieldset class="onboarding-fieldset">
      <legend><span class="onboarding-step-badge">৩</span> স্বাস্থ্য সংক্রান্ত অবস্থা</legend>

      <div class="field">
        <label>যদি থাকে, বেছে নিন (ঐচ্ছিক)</label>
        <?php foreach ($conditions as $code): ?>
          <label class="checkbox-row">
            <input type="checkbox" name="medical_flags[]" value="<?= e($code) ?>" <?= in_array($code, $flags, true) ? 'checked' : '' ?>>
            <?= e(condition_label($code)) ?>
          </label>
        <?php endforeach; ?>
        <small class="hint">এই তথ্য AES-256 দিয়ে Encrypt করে রাখা হয়।</small>
      </div>
    </fieldset>

    <button type="submit" class="btn btn-block">Diet Plan তৈরি করুন</button>
  </form>
</section>
