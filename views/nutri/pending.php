<?php $this->layout('layouts/public', ['title' => 'Application পর্যালোচনাধীন']); ?>

<section class="card">
  <?php if ($status === 'rejected'): ?>
    <h1>😔 Application গ্রহণযোগ্য হয়নি</h1>
    <p>দুঃখিত, আপনার Nutritionist Application এই মুহূর্তে গ্রহণ করা যায়নি। প্রশ্ন থাকলে Contact Us পেজ দেখুন।</p>
  <?php else: ?>
    <h1>⏳ Application পর্যালোচনাধীন</h1>
    <p>আপনার Credential/License তথ্য Admin যাচাই করছেন। অনুমোদন পেলে এখানেই দেখতে পাবেন, এবং Subscription চালু করার ধাপে যেতে পারবেন।</p>
  <?php endif; ?>
  <a class="btn btn-outline" href="/">হোমে ফিরে যান</a>
</section>
