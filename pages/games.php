<?php
/** 游戏页 */
$profile = $profile ?? profile();
?>
<div class="page games-page">
  <h2>游戏账号</h2>
  <div class="game-cards">
    <?php foreach ($profile['gameAccounts'] as $g): ?>
      <div class="game-card">
        <div class="game-icon">
          <?= fa_icon(strpos($g['game'], 'MC') !== false ? 'fa-cube' : 'fa-gamepad') ?>
        </div>
        <div class="game-info">
          <h3><?= e($g['game']) ?></h3>
          <p>UID: <?= e($g['uid']) ?></p>
          <?php if ($g['level'] !== ''): ?>
            <span class="game-level"><?= e($g['level']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
