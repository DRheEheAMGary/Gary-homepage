<?php
/**
 * Gary-homepage 入口
 * 由 DRheEheAM_Gary-Homepage (React + Vite) 重构为 PHP 服务端渲染
 */

require __DIR__ . '/includes/functions.php';

$profile = profile();
$user = current_user();
$view = (($_GET['view'] ?? '') === 'auth') ? 'auth' : 'main';
$activeTab = 'home';

$sections = ['home', 'links', 'contact', 'games', 'anime', 'projects'];

include __DIR__ . '/includes/header.php';
?>

<?php // 主视图与登录视图同时渲染，由 JS 切换（避免整页刷新，滑块可平滑滑动） ?>
<div class="snap-container" id="snap-container"<?= $view === 'auth' ? ' hidden' : '' ?>>
  <?php foreach ($sections as $id): ?>
    <section id="<?= e($id) ?>" class="snap-section">
      <?php include __DIR__ . '/pages/' . $id . '.php'; ?>
    </section>
  <?php endforeach; ?>

  <footer class="app-footer">
    <p>© <?= date('Y') ?> <?= e(SITE_NAME) ?></p>
    <a href="https://icp.gov.moe/?keyword=20260513" target="_blank" rel="noopener noreferrer">
      萌ICP备20260513号
    </a>
    <p class="footer-quote">"<?= e($profile['quote']) ?>"</p>
  </footer>
</div>

<div class="auth-snap-wrapper" id="auth-wrapper"<?= $view === 'main' ? ' hidden' : '' ?>>
  <section id="auth" class="snap-section">
    <?php include __DIR__ . '/components/auth-page.php'; ?>
  </section>
</div>

<?php
include __DIR__ . '/components/sidebar.php';
include __DIR__ . '/includes/footer.php';
