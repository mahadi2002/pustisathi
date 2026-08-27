<?php $this->layout('layouts/public', ['title' => 'শরীরভেদে সঠিক ডায়েট প্ল্যান']); ?>

<section class="hero">
  <h1>আপনার শরীর, বাজেট আর এলাকা বুঝে প্রতিদিনের ডায়েট প্ল্যান</h1>
  <p>বয়স, ওজন, বাজেট আর আপনার এলাকায় যা পাওয়া যায় — সব মিলিয়ে পুষ্টিসাথী বানিয়ে দেয় বাস্তবসম্মত খাবারের তালিকা। ডায়াবেটিস, কিডনি সমস্যা, হৃদরোগ বা গর্ভাবস্থার মতো অবস্থার জন্য আলাদা নির্দেশনাও আছে।</p>
  <p class="hero-kicker">🚀 এখনই Start করুন — সম্পূর্ণ ফ্রি</p>
  <?= \App\Core\View::partial('partials/login-cta') ?>
</section>

<h2 class="section-title">কীভাবে কাজ করে</h2>
<p class="section-sub">তিনটি ধাপ — প্রোফাইল দিন, প্ল্যান পান, নিয়মিত অনুসরণ করুন।</p>

<div class="step-list">
  <div class="step-card reveal">
    <div class="step-badge">১</div>
    <h3>প্রোফাইল দিন</h3>
    <p>বয়স, ওজন, উচ্চতা, এলাকা, বাজেট আর কোনো স্বাস্থ্য সমস্যা থাকলে জানান — মাত্র একবার।</p>
  </div>
  <div class="step-card reveal">
    <div class="step-badge">২</div>
    <h3>প্ল্যান পান</h3>
    <p>আপনার এলাকায় যা পাওয়া যায় আর আপনার বাজেটের মধ্যে থেকে, দিনভর খাবারের তালিকা তৈরি হয় আপনার জন্য।</p>
  </div>
  <div class="step-card reveal">
    <div class="step-badge">৩</div>
    <h3>Dashboard-এ দেখুন</h3>
    <p>প্রতিদিনের Calorie ও Macro লক্ষ্য, বেলা-ভিত্তিক খাবারের তালিকা — সবকিছু একসাথে Dashboard-এ।</p>
  </div>
</div>

<h2 class="section-title">যা যা পাবেন</h2>
<p class="section-sub">এখনই ব্যবহারযোগ্য ফিচার, আর শীঘ্রই যা আসছে।</p>

<div class="feature-grid">
  <div class="feature-card reveal">
    <span class="feature-icon-badge">🍽️</span>
    <h3>ব্যক্তিগত Diet Plan Generator</h3>
    <p>BMR আর Activity Level হিসাব করে, আপনার দৈনিক ক্যালরি ও Macro লক্ষ্য অনুযায়ী খাবার সাজানো হয়।</p>
  </div>
  <div class="feature-card reveal">
    <span class="feature-icon-badge">💰</span>
    <h3>বাজেট-ভিত্তিক প্ল্যান</h3>
    <p>Low, Mid বা High বাজেট বেছে নিন — প্ল্যান সেই অনুযায়ী খাবার বাছবে, বাস্তবতার বাইরে যাবে না।</p>
  </div>
  <div class="feature-card reveal">
    <span class="feature-icon-badge">🩺</span>
    <h3>রোগ-ভিত্তিক নির্দেশনা</h3>
    <p>ডায়াবেটিস, কিডনি সমস্যা, হৃদরোগ, গর্ভাবস্থা — প্রতিটির জন্য আলাদা বিধিনিষেধ ও অগ্রাধিকার মেনে চলা প্ল্যান।</p>
  </div>
  <div class="feature-card reveal">
    <span class="feature-icon-badge">📍</span>
    <h3>এলাকা অনুযায়ী বিকল্প</h3>
    <p>আপনার জেলা/উপজেলায় যে খাবার সহজে পাওয়া যায়, প্ল্যান সেটাকেই অগ্রাধিকার দেয়।</p>
  </div>
  <div class="feature-card reveal">
    <span class="feature-icon-badge">🔍</span>
    <h3>খাবার খুঁজুন <span class="live-badge">Free</span></h3>
    <p>যেকোনো খাবারের ক্যালরি ও Macro এখনই খুঁজে দেখুন — Sign up ছাড়াই।</p>
    <a href="/foods">খুঁজে দেখুন →</a>
  </div>
  <div class="feature-card reveal">
    <span class="feature-icon-badge">🧮</span>
    <h3>BMI / BMR ক্যালকুলেটর <span class="live-badge">Free</span></h3>
    <p>উচ্চতা-ওজন দিয়ে সাথে সাথে BMI আর দৈনিক ক্যালরি চাহিদা দেখুন।</p>
    <a href="/calculator">হিসাব করুন →</a>
  </div>
  <div class="feature-card is-soon reveal">
    <span class="feature-icon-badge">📒</span>
    <h3>দৈনিক Food Log <span class="soon-badge">শীঘ্রই আসছে</span></h3>
    <p>যা খেলেন তা লিখে রাখুন, আর সেদিনের মোট Calorie ও Nutrient হিসাব সাথে সাথে দেখুন।</p>
  </div>
  <div class="feature-card is-soon reveal">
    <span class="feature-icon-badge">📄</span>
    <h3>Plan History + PDF Export <span class="soon-badge">শীঘ্রই আসছে</span></h3>
    <p>আগের Plan গুলো দেখুন, আর চাইলে বর্তমান Plan PDF করে সংরক্ষণ করুন।</p>
  </div>
  <div class="feature-card is-soon reveal">
    <span class="feature-icon-badge">🧑‍⚕️</span>
    <h3>Nutritionist Review <span class="soon-badge">শীঘ্রই আসছে</span></h3>
    <p>একজন Approved Nutritionist আপনার প্রোফাইল দেখে নিজে Plan সংশোধন করে দেবেন।</p>
  </div>
</div>

<section class="card">
  <h2>যা পাবেন — সব ফ্রি, শুধু একটি Account লাগবে</h2>
  <ul>
    <li>বয়স, ওজন, উচ্চতা ও Activity Level অনুযায়ী সম্পূর্ণ ব্যক্তিগত Diet Plan</li>
    <li>ডায়াবেটিস, কিডনি সমস্যা, হৃদরোগ ও গর্ভাবস্থার জন্য আলাদা নির্দেশনা</li>
    <li>আপনার বাজেট (Low/Mid/High) মেনে খাবার নির্বাচন — বাস্তবতার বাইরে যাবে না</li>
    <li>আপনার জেলা/উপজেলায় সহজে পাওয়া যায় এমন খাবারকে অগ্রাধিকার</li>
    <li>খাবার খোঁজায় কোনো দৈনিক সীমা নেই — Unlimited</li>
    <li>প্রোফাইল বদলালে বা চাইলেই নতুন করে Plan তৈরি (Regenerate) করুন</li>
    <li>স্বাস্থ্য তথ্য AES-256 দিয়ে Encrypted — Plaintext-এ কোথাও সংরক্ষণ হয় না</li>
  </ul>
  <?= \App\Core\View::partial('partials/login-cta') ?>
</section>

<div class="trust-row">
  <div class="trust-item reveal">
    <span class="feature-icon-badge">🔒</span>
    <h3>এনক্রিপ্টেড স্বাস্থ্য তথ্য</h3>
    <p>স্বাস্থ্য সংক্রান্ত তথ্য AES-256 দিয়ে Encrypt করে রাখা হয় — Plaintext-এ কোথাও না।</p>
  </div>
  <div class="trust-item reveal">
    <span class="feature-icon-badge">🆓</span>
    <h3>সম্পূর্ণ ফ্রি</h3>
    <p>কোনো Billing বা Subscription নেই — একটি Account খুলে সরাসরি ব্যবহার শুরু করুন।</p>
  </div>
  <div class="trust-item reveal">
    <span class="feature-icon-badge">⚠️</span>
    <h3>এটা চিকিৎসা পরামর্শ নয়</h3>
    <p>প্রতিটি প্ল্যান সাধারণ নির্দেশনা — জটিল স্বাস্থ্য সমস্যায় ডাক্তার বা ডায়েটিশিয়ানের পরামর্শ নিন।</p>
  </div>
</div>

<section class="card">
  <h2>একজন Nutritionist?</h2>
  <p>Register করুন, Admin Approve করলে Patient দের Profile দেখে নিজে Plan বানাতে ও সংশোধন করতে পারবেন।</p>
  <a class="btn btn-outline" href="/nutri/register">Nutritionist হিসেবে Register করুন</a>
</section>
