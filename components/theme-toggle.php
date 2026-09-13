<?php
/**
 * 主题切换：悬浮菜单（自动 / 浅色 / 深色）
 * 图标与状态由 assets/js/theme.js 初始化
 */
?>
<div class="theme-picker" id="theme-picker">
  <button class="theme-toggle" id="theme-toggle" title="切换主题" aria-haspopup="true" aria-expanded="false">
    <?= fa_icon('fa-adjust') ?>
  </button>
  <div class="theme-menu" id="theme-menu" role="menu" hidden>
    <button type="button" class="theme-option" role="menuitem" data-theme-value="auto">
      <?= fa_icon('fa-adjust') ?><span>自动（跟随系统）</span>
    </button>
    <button type="button" class="theme-option" role="menuitem" data-theme-value="light">
      <?= fa_icon('fa-sun') ?><span>浅色模式</span>
    </button>
    <button type="button" class="theme-option" role="menuitem" data-theme-value="dark">
      <?= fa_icon('fa-moon') ?><span>深色模式</span>
    </button>
  </div>
</div>
