<?php
/**
 * 登录 / 注册页面
 * 交互逻辑见 assets/js/auth.js
 * 依赖：$user、$view
 */
$view = $view ?? 'auth';
?>
<div class="page auth-page">
  <div class="auth-container">
    <!-- 左侧表单区 -->
    <div class="auth-form-side">
      <div class="auth-tabs" id="auth-tabs">
        <div class="auth-tab-indicator" id="auth-tab-indicator"></div>
        <button class="auth-tab active" data-auth-tab="login" type="button">登录</button>
        <button class="auth-tab" data-auth-tab="register" type="button">注册</button>
      </div>

      <!-- 登录表单 -->
      <div id="auth-pane-login">
        <h2 class="auth-title">欢迎回来</h2>
        <p class="auth-subtitle">登录后打卡数据将云端同步</p>
        <form class="auth-form" id="login-form">
          <div class="auth-field">
            <?= fa_icon('fa-user', 'auth-field-icon') ?>
            <input type="text" name="username" placeholder="用户名" autocomplete="username" autofocus />
          </div>
          <div class="auth-field">
            <?= fa_icon('fa-lock', 'auth-field-icon') ?>
            <input type="password" name="password" placeholder="密码" autocomplete="current-password" />
          </div>
          <p class="auth-error" data-auth-error hidden></p>
          <div class="turnstile-container" data-turnstile="login"></div>
          <button type="submit" class="auth-submit" data-auth-submit disabled>登 录</button>
        </form>
      </div>

      <!-- 注册表单 -->
      <div id="auth-pane-register" hidden>
        <h2 class="auth-title">创建账号</h2>
        <p class="auth-subtitle">注册后即可使用云端打卡功能</p>
        <form class="auth-form" id="register-form">
          <div class="auth-field">
            <?= fa_icon('fa-user', 'auth-field-icon') ?>
            <input type="text" name="username" placeholder="用户名" autocomplete="username" autofocus />
          </div>
          <div class="auth-field">
            <?= fa_icon('fa-envelope', 'auth-field-icon') ?>
            <input type="email" name="email" placeholder="邮箱" autocomplete="email" />
          </div>
          <div class="auth-field">
            <?= fa_icon('fa-lock', 'auth-field-icon') ?>
            <input type="password" name="password" placeholder="密码（至少6位）" autocomplete="new-password" />
          </div>
          <div class="auth-field">
            <?= fa_icon('fa-lock', 'auth-field-icon') ?>
            <input type="password" name="password2" placeholder="确认密码" autocomplete="new-password" />
          </div>
          <p class="auth-error" data-auth-error hidden></p>
          <p class="auth-success" data-auth-success hidden></p>
          <div class="turnstile-container" data-turnstile="register"></div>
          <button type="submit" class="auth-submit" data-auth-submit disabled>注 册</button>
        </form>
      </div>
    </div>

    <!-- 右侧信息区（CSS 默认隐藏） -->
    <div class="auth-info-side">
      <div class="auth-info-card">
        <?= fa_icon('fa-heart', 'auth-info-icon') ?>
        <h3 class="auth-info-title">云端打卡</h3>
        <ul class="auth-info-list">
          <li><?= fa_icon('fa-check-circle') ?> 数据云端存储，永不丢失</li>
          <li><?= fa_icon('fa-check-circle') ?> 多设备自动同步</li>
          <li><?= fa_icon('fa-check-circle') ?> 连续打卡天数统计</li>
          <li><?= fa_icon('fa-check-circle') ?> 每日运势永久保存</li>
        </ul>
      </div>
      <div class="auth-info-card">
        <?= fa_icon('fa-cloud', 'auth-info-icon') ?>
        <h3 class="auth-info-title">数据安全</h3>
        <p class="auth-info-text">
          基于 WordPress 账号体系，数据加密传输，
          仅存储打卡日期与运势信息。
        </p>
      </div>
    </div>
  </div>
</div>
