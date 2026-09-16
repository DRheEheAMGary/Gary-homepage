<?php
/**
 * 顶栏：站点标题 + Tab + 用户/主题
 * 依赖：$view、$user、$activeTab
 */
$view = $view ?? 'main';
$user = $user ?? current_user();
$activeTab = $activeTab ?? 'home';
?>
<div class="top-bar">
  <div class="top-bar-left">
    <span class="site-title"><?= e(SITE_NAME) ?></span>
    <span class="site-subtitle"><?= e(SITE_SUBTITLE) ?></span>
  </div>

  <?php include __DIR__ . '/tab-bar.php'; ?>

  <div class="top-bar-right">
    <button class="nav-burger" id="nav-burger" type="button" aria-label="菜单" aria-expanded="false">
      <?= fa_icon('fa-bars') ?>
    </button>
    <?php if ($user): ?>
      <?php
        $avatar = $user['avatar'] ?? null;
        // 用 WordPress 的 user_nicename（slug），即个人主页 /user/{nicename}/ 的规范形式
        $profileSlug = !empty($user['slug']) ? $user['slug'] : ($user['name'] ?? '');
        $profileUrl = rtrim(BLOG_USER_URL, '/') . '/' . rawurlencode($profileSlug) . '/';
      ?>
      <div class="top-bar-user-wrap" id="user-wrap">
        <button
          class="top-bar-login-btn top-bar-user-pill"
          id="user-menu-btn"
          type="button"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <?php if (!empty($avatar)): ?>
            <img
              src="<?= e($avatar) ?>"
              alt=""
              class="top-bar-avatar"
              referrerpolicy="no-referrer"
              loading="lazy"
              onerror="this.onerror=null;this.hidden=true;var f=this.nextElementSibling;if(f)f.hidden=false;"
            />
            <i class="fa-solid fa-user-circle top-bar-avatar-fallback" hidden></i>
          <?php else: ?>
            <?= fa_icon('fa-user-circle') ?>
          <?php endif; ?>
          <?= e($user['name']) ?>
          <i class="fa-solid fa-chevron-down top-bar-user-caret"></i>
        </button>

        <div class="user-menu" id="user-menu" role="menu" hidden>
          <a class="user-menu-item" role="menuitem" href="<?= e($profileUrl) ?>" target="_blank" rel="noopener noreferrer">
            <?= fa_icon('fa-id-badge') ?><span>个人主页</span>
          </a>
          <a class="user-menu-item" role="menuitem" href="<?= e(BLOG_SETTINGS_URL) ?>" target="_blank" rel="noopener noreferrer">
            <?= fa_icon('fa-gear') ?><span>个人设置</span>
          </a>
          <button class="user-menu-item user-menu-logout" role="menuitem" id="user-menu-logout" type="button">
            <?= fa_icon('fa-sign-out-alt') ?><span>退出登录</span>
          </button>
        </div>
      </div>
    <?php else: ?>
      <a
        class="top-bar-login-btn"
        id="login-btn"
        href="<?= e(base_url('index.php')) ?>?view=auth"
        title="登录/注册"
      ><?= fa_icon('fa-user') ?> 登录/注册</a>
    <?php endif; ?>

    <?php include __DIR__ . '/theme-toggle.php'; ?>
  </div>

  <?php // 窄屏汉堡菜单：复用 tab-bar.php 定义的 $tabs ?>
  <nav class="nav-menu" id="nav-menu" hidden>
    <?php foreach ($tabs as $tab): ?>
      <a
        class="nav-menu-item"
        role="menuitem"
        data-section="<?= e($tab['id']) ?>"
        href="<?= e(base_url('')) ?>#<?= e($tab['id']) ?>"
      ><?= e($tab['label']) ?></a>
    <?php endforeach; ?>
  </nav>
</div>
