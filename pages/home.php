<?php
/** 首页 */
$profile = $profile ?? profile();
$parts = array_map('trim', explode('|', $profile['description']));
$stats = [
    ['icon' => 'fa-user-graduate', 'text' => $parts[0] ?? ''],
    ['icon' => 'fa-laptop-code',   'text' => $parts[1] ?? ''],
    ['icon' => 'fa-heart',         'text' => $parts[2] ?? ''],
];
?>
<div class="page home-page">
  <!-- 个人简介 -->
  <p class="profile-title"><?= e($profile['description']) ?></p>
  <p class="profile-mbti">CN: <?= e($profile['cn']) ?> | MBTI: <?= e($profile['mbti']) ?></p>
  <p class="profile-quote">"<?= e($profile['quote']) ?>"</p>
  <div class="quick-stats">
    <?php foreach ($stats as $s): ?>
      <div class="stat-item">
        <?= fa_icon($s['icon']) ?>
        <span><?= e($s['text']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- 关于我 -->
  <div class="about-content">
    <?php foreach ($profile['about'] as $p): ?>
      <p><?= e($p) ?></p>
    <?php endforeach; ?>
  </div>

  <div class="about-section">
    <h3><?= fa_icon('fa-gamepad') ?> 目前玩的游戏</h3>
    <ul class="tag-list">
      <?php foreach ($profile['games'] as $g): ?>
        <li class="tag"><?= e($g) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="about-section">
    <h3><?= fa_icon('fa-heart') ?> 二次元成分</h3>
    <ul class="tag-list">
      <?php foreach ($profile['anime'] as $a): ?>
        <li class="tag"><?= e($a) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="about-section">
    <h3><?= fa_icon('fa-cube') ?> Minecraft成分</h3>
    <p><?= e($profile['minecraft']) ?></p>
  </div>

  <div class="about-section">
    <h3><?= fa_icon('fa-heart') ?>主推（推し）</h3>
    <p>v圈单推 <strong><?= e($profile['vtuber']) ?></strong></p>
    <p><?= e($profile['genshin']) ?></p>
  </div>
</div>
