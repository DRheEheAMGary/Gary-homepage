<?php
/**
 * 全局右侧边栏：头像 + 名字 + 歌词 + 打卡
 * 依赖：$user、$profile
 */
$user = $user ?? current_user();
$profile = $profile ?? profile();
?>
<aside class="global-sidebar">
  <div class="sidebar-top">
    <h1 class="profile-name">
      <span class="profile-greeting">Hello! I'm</span>
      <span class="profile-name-main"><?= e($profile['name']) ?></span>
    </h1>
    <div class="avatar-wrapper">
      <img src="<?= e($profile['avatar']) ?>" alt="<?= e($profile['name']) ?>" class="avatar" />
      <div class="avatar-ring"></div>
    </div>
  </div>

  <div class="lyrics-section">
    <?php include __DIR__ . '/lyrics-typewriter.php'; ?>
  </div>

  <?php include __DIR__ . '/daily-checkin.php'; ?>
</aside>
