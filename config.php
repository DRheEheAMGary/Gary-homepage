<?php
/**
 * 全局配置
 * Gary-homepage（由 DRheEheAM_Gary-Homepage React 项目重构为 PHP）
 */

// ==================== WordPress 后端 ====================
define('WP_BASE', 'https://blog.dreamgary.cn/wp-json');
define('JWT_VALIDATE_PATH', '/jwt-auth/v1/token/validate');
define('JWT_LOGIN_PATH', '/checkin/v1/login');
define('CHECKIN_REGISTER_PATH', '/checkin/v1/register');
define('CHECKIN_VERIFY_PATH', '/checkin/v1/verify-turnstile');
define('CHECKIN_DATES_PATH', '/checkin/v1/dates');
define('CHECKIN_FORTUNE_PATH', '/checkin/v1/fortune');
define('WP_USERS_ME_PATH', '/wp/v2/users/me');

// ==================== 跨子域共享 Cookie ====================
define('COOKIE_DOMAIN', '.dreamgary.cn');
define('TOKEN_COOKIE', 'wp_jwt_token');
define('TOKEN_TTL', 60 * 60 * 24 * 30); // 30 天

// ==================== Cloudflare Turnstile ====================
define('TURNSTILE_SITE_KEY', '0x4AAAAAADCXUzgWHNGnWoQN');
define('TURNSTILE_SECRET', '0x4AAAAAADCXU6Vku-xXuX5pmDeLDoC-Qng');
define('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');

// ==================== 站点信息 ====================
define('SITE_NAME', 'DRheEheAM_Gary');
define('SITE_SUBTITLE', '个人主页 | OIer & 二次元');
define('SITE_URL', 'https://dreamgary.cn');

// ==================== 头像覆盖（可选） ====================
// 当 WordPress 头像插件返回的 avatar 不正确时，可按用户 ID 指定头像 URL。
// 键为 WordPress 用户 ID，值为头像图片地址。
define('AVATAR_OVERRIDES', [
    1 => 'https://blog.dreamgary.cn/wp-content/uploads/avatars/avatar-1-1784859119.jpeg',
]);

// ==================== 资源 / 缓存 ====================
define('CACHE_DIR', __DIR__ . '/cache');
define('GITHUB_CACHE_TTL', 60 * 30); // GitHub 贡献图缓存 30 分钟
