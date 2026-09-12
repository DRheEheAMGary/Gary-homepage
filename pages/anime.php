<?php
/** 二次元页 */
$profile = $profile ?? profile();
$genshinIcons = ['温迪' => 'fa-wind', '妮露' => 'fa-water', '茜特菈莉' => 'fa-star'];
?>
<div class="page anime-page">
  <h2>二次元成分</h2>

  <div class="anime-section">
    <h3><?= fa_icon('fa-microphone') ?> 虚拟主播</h3>
    <div class="character-grid">
      <?php foreach ($profile['vtubers'] as $v): ?>
        <a href="<?= e($v['url']) ?>" target="_blank" rel="noopener noreferrer" class="char-card">
          <span class="char-icon"><?= fa_icon('fa-microphone') ?></span>
          <span class="char-name"><?= e($v['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="anime-section">
    <h3>原神 主推</h3>
    <div class="character-grid">
      <?php foreach ($profile['genshinChars'] as $c): ?>
        <div class="char-card">
          <span class="char-icon"><?= fa_icon($genshinIcons[$c['name']] ?? 'fa-star') ?></span>
          <span class="char-name"><?= e($c['name']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="anime-section">
    <h3>副推</h3>
    <div class="character-grid">
      <?php foreach ($profile['subPush'] as $s): ?>
        <?php if (!empty($s['url'])): ?>
          <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer" class="char-card">
            <span class="char-icon"><?= fa_icon('fa-microphone') ?></span>
            <span class="char-name"><?= e($s['name']) ?></span>
          </a>
        <?php else: ?>
          <div class="char-card">
            <span class="char-icon"><?= fa_icon($s['name'] === '银狼' ? 'fa-gamepad' : 'fa-wheat-alt') ?></span>
            <span class="char-name"><?= e($s['name']) ?></span>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
