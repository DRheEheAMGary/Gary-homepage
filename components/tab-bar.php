<?php
/**
 * Tab 导航栏
 * 依赖：$view、$activeTab（默认 home）
 */
$view = $view ?? 'main';
$activeTab = $activeTab ?? 'home';
$tabs = [
    ['id' => 'home', 'label' => '首页'],
    ['id' => 'links', 'label' => '链接'],
    ['id' => 'contact', 'label' => '联系'],
    ['id' => 'games', 'label' => '游戏'],
    ['id' => 'anime', 'label' => '二次元'],
    ['id' => 'projects', 'label' => '项目'],
];
$isAuth = ($view === 'auth');
?>
<nav class="tab-bar" id="tab-bar">
  <div class="tab-indicator" id="tab-indicator"></div>
  <?php foreach ($tabs as $tab): ?>
    <a
      class="tab-item <?= (!$isAuth && $activeTab === $tab['id']) ? 'active' : '' ?> <?= $isAuth ? 'dimmed' : '' ?>"
      href="<?= e(base_url('')) ?>#<?= e($tab['id']) ?>"
      data-section="<?= e($tab['id']) ?>"
    ><?= e($tab['label']) ?></a>
  <?php endforeach; ?>
</nav>
