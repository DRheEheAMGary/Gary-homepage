<?php
/** 联系页 */
$profile = $profile ?? profile();
$iconMap = [
    'fa-envelope' => 'fa-envelope',
    'fa-qq'       => 'fa-qq',
    'fa-phone'    => 'fa-phone',
    'fa-bilibili' => 'fa-bilibili',
];
?>
<div class="page contact-page">
  <h2>联系方式</h2>
  <div class="contact-cards">
    <?php foreach ($profile['contacts'] as $c): ?>
      <?php $isEmail = ($c['type'] === 'email'); ?>
      <div class="contact-card" <?= $isEmail ? 'data-copy="' . e($c['value']) . '"' : '' ?>>
        <span class="contact-icon"><?= fa_icon($iconMap[$c['icon']] ?? 'fa-link') ?></span>
        <span class="contact-value"><?= e($c['display']) ?></span>
        <?php if ($isEmail): ?>
          <button class="copy-btn" type="button" title="复制" data-copy-btn>
            <span class="copy-icon-default"><?= fa_icon('fa-copy') ?></span>
            <span class="copy-icon-done" hidden><?= fa_icon('fa-check') ?></span>
          </button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
