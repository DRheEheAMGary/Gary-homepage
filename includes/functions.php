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
    setcookie('gary_avatar', '', [
        'expires'  => time() - 3600,
        'domain'   => COOKIE_DOMAIN,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** 当前登录用户；未登录返回 null */
function current_user() {
    if (!empty($_SESSION['gary_user'])) {
        $stale = empty($_SESSION['gary_user_time']) || (time() - $_SESSION['gary_user_time'] > 3600);
        if (!$stale) {
            $cached = $_SESSION['gary_user'];
            $cached['avatar'] = normalize_avatar($cached['avatar'] ?? null);
            return $cached;
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
 * 规范化头像地址
 * WordPress 可能返回纯 URL、协议相对地址、相对路径，或整段 <img> 标签
 * @return string|null 可用的绝对 URL，失败返回 null
 */
function normalize_avatar($avatar) {
    if (empty($avatar)) {
        return null;
    }

    // 数组形式：优先 96，其次 48，再取第一个
    if (is_array($avatar)) {
        $avatar = $avatar['96'] ?? ($avatar['48'] ?? reset($avatar));
    }
    if (!is_string($avatar)) {
        return null;
    }

    $avatar = trim($avatar);
    if ($avatar === '') {
        return null;
    }

    // 若返回的是 <img ...> 标签，抽取 src
    if (stripos($avatar, '<img') !== false) {
        if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $avatar, $m)) {
            $avatar = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        } else {
            return null;
        }
    }

    // 协议相对地址补全
    if (strpos($avatar, '//') === 0) {
        $avatar = 'https:' . $avatar;
    // http 升级为 https，避免 HTTPS 页面混合内容被拦截
    } elseif (stripos($avatar, 'http://') === 0) {
        $avatar = 'https://' . substr($avatar, 7);
    // 相对路径补全为 WordPress 站点地址
    } elseif (strpos($avatar, '/') === 0) {
        $parts = parse_url(WP_BASE);
        $avatar = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $avatar;
    }

    return filter_var($avatar, FILTER_VALIDATE_URL) ? $avatar : null;
}

/** 是否为默认/无效的 Gravatar 头像 */
function is_default_avatar($url) {
    if (empty($url) || !is_string($url)) {
        return true;
    }
    // gravatar 地址缺少邮箱 hash（形如 /avatar/?...）即为默认头像
    if (preg_match('#^https?://[^/]*gravatar\.com/avatar/?(\?|$)#i', $url)) {
        return true;
    }
    return false;
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
    // users/me 会自行校验 token，无需再单独调用 validate（部分站点没有该端点）
    list($ok, $data) = wp_request('GET', WP_USERS_ME_PATH, null, $token);
    if (!$ok || empty($data['id'])) {
        return null;
    }
    // Simple Local Avatars 注册的 REST 字段 simple_local_avatar（含 full / 各尺寸 / media_id）
    $sla = $data['simple_local_avatar'] ?? null;
    $slaUrl = is_array($sla) ? ($sla['96'] ?? ($sla['full'] ?? null)) : null;
    $slaAvatar = normalize_avatar($slaUrl);

    // 只使用当前用户自己的数据，绝不回退到 Cookie / 其它账号
    $avatar = !empty($slaAvatar) ? $slaAvatar : normalize_avatar($data['avatar_urls'] ?? null);

    return [
        'id'     => $data['id'],
        'name'   => $data['name'] ?? ($data['slug'] ?? ''),
        'slug'   => $data['slug'] ?? '',
        'avatar' => $avatar,
    ];
}
