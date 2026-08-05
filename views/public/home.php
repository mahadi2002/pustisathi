<?php $this->layout('layouts/public', ['title' => 'শরীরভেদে সঠিক ডায়েট প্ল্যান']); ?>

<section class="hero">
  <h1>আপনার শরীর, বাজেট আর এলাকা বুঝে প্রতিদিনের ডায়েট প্ল্যান</h1>
  <p>বয়স, ওজন, বাজেট আর আপনার এলাকায় যা পাওয়া যায় — সব মিলিয়ে পুষ্টিসাথী বানিয়ে দেয় বাস্তবসম্মত খাবারের তালিকা। ডায়াবেটিস, কিডনি সমস্যা, হৃদরোগ বা গর্ভাবস্থার মতো অবস্থার জন্য আলাদা নির্দেশনাও আছে।</p>
  <a class="btn btn-accent" href="/subscribe">🚀 এখনই Start করুন — <span class="price-tag">৳<?= e($dailyAmount) ?>/day</span> (VAT/SD/SC-সহ)</a>
</section>

<h2 class="section-title">কীভাবে কাজ করে</h2>
<p class="section-sub">তিনটি ধাপ — প্রোফাইল দিন, প্ল্যান পান, প্রতিদিন লগ করুন।</p>

<div class="step-list">
  <div class="step-card">
    <div class="step-badge">১</div>
    <h3>প্রোফাইল দিন</h3>
    <p>বয়স, ওজন, উচ্চতা, এলাকা, বাজেট আর কোনো স্বাস্থ্য সমস্যা থাকলে জানান — মাত্র একবার।</p>
  </div>
  <div class="step-card">
    <div class="step-badge">২</div>
    <h3>প্ল্যান পান</h3>
    <p>আপনার এলাকায় যা পাওয়া যায় আর আপনার বাজেটের মধ্যে থেকে, দিনভর খাবারের তালিকা তৈরি হয় আপনার জন্য।</p>
  </div>
  <div class="step-card">
    <div class="step-badge">৩</div>
    <h3>প্রতিদিন Track করুন</h3>
    <p>যা খেলেন তা Log করুন, Nutrient হিসাব দেখুন, আর চাইলে PDF করে সংরক্ষণ করুন।</p>
  </div>
</div>

<h2 class="section-title">যা যা পাবেন</h2>
<p class="section-sub">শুধু একটা তালিকা নয় — আপনার নিজের অবস্থা বুঝে বানানো প্ল্যান।</p>

<div class="feature-grid">
  <div class="feature-card">
    <span class="feature-icon">🍽️</span>
    <h3>ব্যক্তিগত Diet Plan Generator</h3>
    <p>BMR আর Activity Level হিসাব করে, আপনার দৈনিক ক্যালরি ও Macro লক্ষ্য অনুযায়ী খাবার সাজানো হয়।</p>
  </div>
  <div class="feature-card">
    <span class="feature-icon">💰</span>
    <h3>বাজেট-ভিত্তিক প্ল্যান</h3>
    <p>Low, Mid বা High বাজেট বেছে নিন — প্ল্যান সেই অনুযায়ী খাবার বাছবে, বাস্তবতার বাইরে যাবে না।</p>
  </div>
  <div class="feature-card">
    <span class="feature-icon">🩺</span>
    <h3>রোগ-ভিত্তিক নির্দেশনা</h3>
    <p>ডায়াবেটিস, কিডনি সমস্যা, হৃদরোগ, গর্ভাবস্থা — প্রতিটির জন্য আলাদা বিধিনিষেধ ও অগ্রাধিকার মেনে চলা প্ল্যান।</p>
  </div>
  <div class="feature-card">
    <span class="feature-icon">📍</span>
    <h3>এলাকা অনুযায়ী বিকল্প</h3>
    <p>আপনার জেলা/উপজেলায় যে খাবার সহজে পাওয়া যায়, প্ল্যান সেটাকেই অগ্রাধিকার দেয় — মৌসুমও মাথায় রেখে।</p>
  </div>
  <div class="feature-card">
    <span class="feature-icon">📒</span>
    <h3>দৈনিক Food Log</h3>
    <p>যা খেলেন তা লিখে রাখুন, আর সেদিনের মোট Calorie ও Nutrient হিসাব সাথে সাথে দেখুন।</p>
  </div>
  <div class="feature-card">
    <span class="feature-icon">🧑‍⚕️</span>
    <h3>Nutritionist-এর সহায়তা</h3>
    <p>চাইলে একজন Approved Nutritionist আপনার প্রোফাইল দেখে নিজে প্ল্যান বানিয়ে/সংশোধন করে দিতে পারবেন।</p>
  </div>
</div>

<h2 class="section-title">Free বনাম Subscriber</h2>
<div class="compare-table-wrap">
  <table class="compare-table">
    <thead>
      <tr><th>Feature</th><th>Free</th><th>Subscriber (৳<?= e($dailyAmount) ?>/day)</th></tr>
    </thead>
    <tbody>
      <tr><td>BMI / BMR ক্যালকুলেটর</td><td class="yes">✓</td><td class="yes">✓</td></tr>
      <tr><td>খাবারের পুষ্টিমান খোঁজা</td><td class="locked">দিনে সীমিত সংখ্যক বার</td><td class="yes">✓</td></tr>
      <tr><td>ব্যক্তিগত Diet Plan</td><td class="locked">—</td><td class="yes">✓</td></tr>
      <tr><td>রোগ-ভিত্তিক নির্দেশনা</td><td class="locked">—</td><td class="yes">✓</td></tr>
      <tr><td>দৈনিক Food Log</td><td class="locked">—</td><td class="yes">✓</td></tr>
      <tr><td>Plan History + PDF Export</td><td class="locked">—</td><td class="yes">✓</td></tr>
    </tbody>
  </table>
</div>

<section class="card">
  <h2>বিনামূল্যে এখনই যা করতে পারেন</h2>
  <ul>
    <li><a href="/calculator">BMI / BMR ক্যালকুলেটর</a> — কোনো Sign up ছাড়াই</li>
    <li>খাবারের পুষ্টিমান খুঁজুন (দিনে সীমিত সংখ্যক বার)</li>
  </ul>
</section>

<div class="trust-row">
  <div class="trust-item">
    <span class="feature-icon">🔒</span>
    <h3>এনক্রিপ্টেড স্বাস্থ্য তথ্য</h3>
    <p>মোবাইল নম্বর ও স্বাস্থ্য সংক্রান্ত তথ্য AES-256 দিয়ে Encrypt করে রাখা হয় — Plaintext-এ কোথাও না।</p>
  </div>
  <div class="trust-item">
    <span class="feature-icon">🚪</span>
    <h3>যেকোনো সময় Unsubscribe</h3>
    <p>Subscribe করার সাথে সাথেই Unsubscribe করার সুযোগ থাকে — কোনো লুকানো শর্ত নেই।</p>
  </div>
  <div class="trust-item">
    <span class="feature-icon">⚠️</span>
    <h3>এটা চিকিৎসা পরামর্শ নয়</h3>
    <p>প্রতিটি প্ল্যান সাধারণ নির্দেশনা — জটিল স্বাস্থ্য সমস্যায় ডাক্তার বা ডায়েটিশিয়ানের পরামর্শ নিন।</p>
  </div>
</div>

<section class="card">
  <h2>একজন Nutritionist?</h2>
  <p>Approved হওয়ার পর Patient দের Profile দেখে নিজে Plan বানাতে ও সংশোধন করতে পারবেন।</p>
  <a class="btn btn-outline" href="/nutri/register">Nutritionist হিসেবে Register করুন</a>
</section>

<?= \App\Core\View::partial('partials/subscribe-box', ['next' => '/app/dashboard']) ?>
