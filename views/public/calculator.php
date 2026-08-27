<?php $this->layout('layouts/public', ['title' => 'BMI / BMR ক্যালকুলেটর']); ?>

<section class="card">
  <h1>BMI / BMR ক্যালকুলেটর</h1>
  <p>এটি শুধু আনুমানিক হিসাব — সংরক্ষিত হয় না। ব্যক্তিগত ডায়েট প্ল্যানের জন্য ফ্রি Account খুলুন।</p>

  <form id="bmi-form">
    <div class="field">
      <label for="height">উচ্চতা (সেমি)</label>
      <input type="number" id="height" min="50" max="250" step="0.1" required>
    </div>
    <div class="field">
      <label for="weight">ওজন (কেজি)</label>
      <input type="number" id="weight" min="10" max="300" step="0.1" required>
    </div>
    <div class="field">
      <label for="age">বয়স</label>
      <input type="number" id="age" min="1" max="120" required>
    </div>
    <div class="field">
      <label for="sex">লিঙ্গ</label>
      <select id="sex">
        <option value="male">পুরুষ</option>
        <option value="female">নারী</option>
      </select>
    </div>
    <div class="field">
      <label for="activity">Activity Level</label>
      <select id="activity">
        <option value="1.2">Sedentary (বসে কাজ)</option>
        <option value="1.375">Light (হালকা পরিশ্রম)</option>
        <option value="1.55" selected>Moderate (মাঝারি পরিশ্রম)</option>
        <option value="1.725">Active (কায়িক পরিশ্রম)</option>
        <option value="1.9">Very Active (ভারী পরিশ্রম)</option>
      </select>
    </div>
    <button type="submit" class="btn btn-block">হিসাব করুন</button>
  </form>

  <div id="bmi-result" class="card" hidden>
    <p>BMI: <strong id="bmi-value"></strong> (<span id="bmi-label"></span>)</p>
    <p>দৈনিক আনুমানিক ক্যালরি প্রয়োজন (BMR × Activity): <strong id="bmr-value"></strong> kcal</p>
  </div>

  <p>এই হিসাবের উপর ভিত্তি করে সম্পূর্ণ ব্যক্তিগত ডায়েট প্ল্যান পেতে ফ্রি Account খুলুন।</p>
  <?= \App\Core\View::partial('partials/login-cta') ?>
</section>
