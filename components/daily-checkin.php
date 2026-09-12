<?php
/**
 * 每日打卡组件
 * 服务端渲染骨架，日历 / 运势逻辑见 assets/js/checkin.js
 * 依赖：$user
 */
$user = $user ?? current_user();
$weekdays = ['日', '一', '二', '三', '四', '五', '六'];
?>
<div class="checkin-row">
  <div class="checkin-wrapper" id="checkin-wrapper">
    <div class="checkin-popup" id="checkin-popup">
      <div class="checkin-header">
        <div class="checkin-title-row">
          <?= fa_icon('fa-calendar-check', 'checkin-title-icon') ?>
          <span class="checkin-title">每日打卡</span>
        </div>
        <div class="checkin-stats" id="checkin-stats"></div>
      </div>

      <div class="checkin-month-nav">
        <button class="checkin-nav-btn" id="checkin-prev" type="button"><?= fa_icon('fa-chevron-left') ?></button>
        <span class="checkin-month-label" id="checkin-month-label"></span>
        <button class="checkin-nav-btn" id="checkin-next" type="button"><?= fa_icon('fa-chevron-right') ?></button>
      </div>

      <div class="checkin-weekdays">
        <?php foreach ($weekdays as $w): ?>
          <span class="checkin-weekday"><?= e($w) ?></span>
        <?php endforeach; ?>
      </div>

      <div class="checkin-grid" id="checkin-grid"></div>
    </div>

    <button
      class="checkin-btn <?= $user ? '' : 'unauth' ?>"
      id="checkin-btn"
      type="button"
      title="<?= $user ? '点击打卡' : '未登录' ?>"
    >
      <?= fa_icon('fa-calendar-check') ?>
      <span class="checkin-btn-label" id="checkin-btn-label"><?= $user ? '打卡' : '未登录' ?></span>
    </button>
  </div>

  <div class="checkin-fortune" id="checkin-fortune"></div>
</div>
