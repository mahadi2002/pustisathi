<?php $this->layout('layouts/public', ['title' => 'শরীরভেদে সঠিক ডায়েট প্ল্যান']); ?>

<section class="card">
  <h1>পুষ্টিসাথী — আপনার শরীর, বাজেট আর এলাকা বুঝে ডায়েট প্ল্যান</h1>
  <p>বয়স, ওজন, বাজেট আর আপনার এলাকায় যা পাওয়া যায় — সব মিলিয়ে প্রতিদিনের খাবারের তালিকা তৈরি করে দেয় পুষ্টিসাথী। ডায়াবেটিস, কিডনি সমস্যা, হৃদরোগ বা গর্ভাবস্থার মতো অবস্থার জন্য আলাদা নির্দেশনাও আছে।</p>
  <a class="btn" href="/subscribe">🚀 এখনই Start করুন — মাত্র ৳<?= e($dailyAmount) ?>/day</a>
</section>

<section class="card">
  <h2>🚀 এখনই Start করুন — মাত্র ৳<?= e($dailyAmount) ?>/day</h2>
  <p>Robi &amp; Airtel Users Only &nbsp;|&nbsp; যেকোনো সময় Unsubscribe করুন</p>
  <a class="btn btn-accent" href="/subscribe">Subscribe Now</a>
</section>

<section class="card">
  <h2>বিনামূল্যে যা পাবেন</h2>
  <ul>
    <li><a href="/calculator">BMI / BMR ক্যালকুলেটর</a></li>
    <li>খাবারের পুষ্টিমান খুঁজুন (দিনে সীমিত সংখ্যক বার)</li>
  </ul>
</section>

<?= \App\Core\View::partial('partials/subscribe-box', ['next' => '/app/dashboard']) ?>
