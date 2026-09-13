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
    <?php if ($user): ?>
      <div class="top-bar-user-wrap" id="user-wrap">
        <div class="top-bar-login-btn top-bar-user-pill">
          <?php $avatar = $user['avatar'] ?? null; ?>
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
          <button class="top-bar-logout-btn" id="logout-btn" title="退出登录"><?= fa_icon('fa-sign-out-alt') ?></button>
        </div>
        <div class="logout-confirm-bar" id="logout-confirm" hidden>
          <span>确定退出？</span>
          <div class="logout-confirm-actions">
            <button class="logout-confirm-cancel" id="logout-cancel">取消</button>
            <button class="logout-confirm-ok" id="logout-ok">确定</button>
          </div>
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
</div>
