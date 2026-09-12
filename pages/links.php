<?php
/** 链接页 */
$profile = $profile ?? profile();
$linkIcons = [
    'fa-blog'        => 'fa-blog',
    'fa-folder'      => 'fa-folder',
    'fa-chess-rook'  => 'fa-chess-knight',
    'fa-bilibili'    => 'fa-bilibili',
    'fa-headphones'  => 'fa-headphones',
    'fa-cube'        => 'fa-cube',
    'fa-gamepad'     => 'fa-gamepad',
];
?>
<div class="page links-page">
  <h2>网站链接</h2>
  <div class="links-grid">
    <?php foreach ($profile['links'] as $l): ?>
      <?php $icon = $linkIcons[$l['icon']] ?? 'fa-link'; ?>
      <a href="<?= e($l['url']) ?>" target="_blank" rel="noopener noreferrer" class="link-card">
        <span class="link-icon"><?= fa_icon($icon) ?></span>
        <div class="link-info">
          <span class="link-name"><?= e($l['name']) ?></span>
          <span class="link-desc"><?= e($l['desc']) ?></span>
        </div>
        <?= fa_icon('fa-external-link-alt', 'link-arrow') ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
