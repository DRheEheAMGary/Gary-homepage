<?php
/**
 * 通用函数库
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/wp-client.php';

// ==================== 会话 ====================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ==================== 基础工具 ====================

/** HTML 转义 */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 计算站点基础路径（支持子目录部署） */
function base_url($path = '') {
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if (basename($dir) === 'api') {
            $dir = dirname($dir);
        }
        $base = rtrim($dir, '/');
    }
    return $base . '/' . ltrim($path, '/');
}

/** 资源 URL */
function asset($path) {
    return base_url('assets/' . ltrim($path, '/'));
}

/** 图标：默认 Font Awesome Solid，知名品牌使用 Brands */
function fa_icon($name, $class = '') {
    static $brands = ['fa-qq', 'fa-bilibili', 'fa-github'];
    $style = in_array($name, $brands, true) ? 'fa-brands' : 'fa-solid';
    $html = '<i class="' . $style . ' ' . e($name);
    if ($class !== '') {
        $html .= ' ' . e($class);
    }
    return $html . '"></i>';
}

/** 读取个人信息数据 */
function profile() {
    static $profile = null;
    if ($profile === null) {
        $profile = require __DIR__ . '/../data/profile.php';
    }
    return $profile;
}

/** 输出 JSON 并结束 */
function json_out($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** 读取 JSON 请求体 */
function json_input() {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ==================== 认证状态 ====================

/** 当前登录用户的 JWT */
function current_token() {
    return $_SESSION['gary_token'] ?? null;
}

/** 写入 JWT（会话 + 跨子域 Cookie） */
function store_token($token) {
    $_SESSION['gary_token'] = $token;
    setcookie(TOKEN_COOKIE, $token, [
        'expires'  => time() + TOKEN_TTL,
        'domain'   => COOKIE_DOMAIN,
        'path'     => '/',
        'secure'   => true,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

/** 清除 JWT */
function clear_token() {
    unset($_SESSION['gary_token'], $_SESSION['gary_user'], $_SESSION['gary_user_time']);
    setcookie(TOKEN_COOKIE, '', [
        'expires'  => time() - 3600,
        'domain'   => COOKIE_DOMAIN,
        'path'     => '/',
        'secure'   => true,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

/** 当前登录用户；未登录返回 null */
function current_user() {
    if (!empty($_SESSION['gary_user'])) {
        $stale = empty($_SESSION['gary_user_time']) || (time() - $_SESSION['gary_user_time'] > 3600);
        if (!$stale) {
            return $_SESSION['gary_user'];
        }
        $user = fetch_current_user($_SESSION['gary_token'] ?? '');
        if ($user) {
            $_SESSION['gary_user'] = $user;
            $_SESSION['gary_user_time'] = time();
            return $user;
        }
        clear_token();
        return null;
    }

    // 会话丢失但存在跨子域 Cookie 时尝试恢复
    if (!empty($_COOKIE[TOKEN_COOKIE])) {
        $token = sanitize_token($_COOKIE[TOKEN_COOKIE]);
        if ($token !== '') {
            $user = fetch_current_user($token);
            if ($user) {
                $_SESSION['gary_token'] = $token;
                $_SESSION['gary_user'] = $user;
                $_SESSION['gary_user_time'] = time();
                return $user;
            }
        }
    }
    return null;
}

function is_logged_in() {
    return current_user() !== null;
}

/** 仅保留 JWT 允许的字符，防止头注入 */
function sanitize_token($token) {
    return preg_replace('/[^A-Za-z0-9\-_\.]/', '', (string) $token);
}

/**
 * 通过 token 拉取当前用户
 * @return array|null ['id','name','slug','avatar']
 */
function fetch_current_user($token) {
    $token = sanitize_token($token);
    if ($token === '') {
        return null;
    }
    list($ok) = wp_request('POST', JWT_VALIDATE_PATH, null, $token);
    if (!$ok) {
        return null;
    }
    list($ok, $data) = wp_request('GET', WP_USERS_ME_PATH, null, $token);
    if (!$ok || empty($data['id'])) {
        return null;
    }
    return [
        'id'     => $data['id'],
        'name'   => $data['name'] ?? ($data['slug'] ?? ''),
        'slug'   => $data['slug'] ?? '',
        'avatar' => $data['avatar_urls']['96'] ?? ($data['avatar_urls']['48'] ?? null),
    ];
}
