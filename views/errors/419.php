<?php $this->layout('layouts/public', ['title' => 'Session মেয়াদোত্তীর্ণ']); ?>
<section class="error-page">
    <h1>⏱️ Session-এর মেয়াদ শেষ</h1>
    <p><?= e($message ?: 'আবার চেষ্টা করুন।') ?></p>
    <button type="button" class="btn" id="go-back-btn">পেছনে যান</button>
    <noscript><a class="btn" href="/">হোমে ফিরে যান</a></noscript>
</section>
