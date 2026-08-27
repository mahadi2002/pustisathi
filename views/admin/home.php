<?php $this->layout('layouts/public', ['title' => 'Admin']); ?>

<section class="card">
  <h1>স্বাগতম, Admin!</h1>
  <p>নিচে সাইটের বর্তমান অবস্থার একটি লাইভ Snapshot দেখতে পাচ্ছেন। খাবারের তথ্যভাণ্ডার পরিচালনা ও Nutritionist Approve করার মতো পূর্ণাঙ্গ Admin Panel এখনো তৈরি হচ্ছে।</p>
</section>

<section class="stat-grid">
  <div class="stat-card">
    <span class="stat-value"><?= e((string) ($stats['patients'] ?? 0)) ?></span>
    <span class="stat-label">Patient</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e((string) ($stats['nutritionists'] ?? 0)) ?></span>
    <span class="stat-label">Nutritionist</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e((string) ($stats['nutritionists_pending'] ?? 0)) ?></span>
    <span class="stat-label">Approval বাকি</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e((string) ($stats['food_items'] ?? 0)) ?></span>
    <span class="stat-label">Food Item</span>
  </div>
  <div class="stat-card">
    <span class="stat-value"><?= e((string) ($stats['diet_plans'] ?? 0)) ?></span>
    <span class="stat-label">তৈরি হওয়া Diet Plan</span>
  </div>
</section>

<section class="card">
  <h2>শীঘ্রই আসছে</h2>
  <ul class="roadmap-list">
    <li>খাবারের তথ্যভাণ্ডার CRUD ও CSV Import</li>
    <li>রোগ-ভিত্তিক নির্দেশনা (Condition Rules) পরিচালনা</li>
    <li>Nutritionist Approval Queue</li>
  </ul>
</section>
