<?php $this->layout('layouts/public', ['title' => 'Session মেয়াদোত্তীর্ণ']); ?>
<section class="error-page">
    <h1>⏱️ Session-এর মেয়াদ শেষ</h1>
    <p><?= e($message ?: 'আবার চেষ্টা করুন।') ?></p>
    <a class="btn" href="javascript:history.back()">পেছনে যান</a>
</section>
