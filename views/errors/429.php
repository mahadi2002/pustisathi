<?php $this->layout('layouts/public', ['title' => 'অনেকবার চেষ্টা হয়েছে']); ?>
<section class="error-page">
    <h1>⏳ অনেকবার চেষ্টা হয়েছে</h1>
    <p><?= e($message ?: 'একটু পরে আবার চেষ্টা করুন।') ?></p>
    <a class="btn" href="/">হোমে ফিরে যান</a>
</section>
