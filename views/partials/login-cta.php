<?php
/**
 * The one CTA button every "not signed in yet" surface points at. Pass
 * $href/$label to point it at Login instead of the default Register (e.g.
 * `partials/login-cta`, ['href' => '/login', 'label' => 'Login করুন']).
 */
$btnClass = $class ?? 'btn btn-accent';
$ctaHref  = $href ?? '/register';
$ctaLabel = $label ?? 'এখনই শুরু করুন';
?>
<a class="<?= e($btnClass) ?>" href="<?= e($ctaHref) ?>"><?= e($ctaLabel) ?></a>
