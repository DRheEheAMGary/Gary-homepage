<?php
/**
 * Plugin Name: Mainpage API (Rewritten)
 * Plugin URI:  https://dreamgary.cn
 * Description: 为个人主页提供打卡数据、用户注册、SSO 单点登录等 REST API 端点。保持原 Mainpage API 全部功能，并修复跨子域 Cookie / CORS / JWT 兼容问题。
 * Version:     2.0.0
 * Author:      DRheEheAM_Gary
 *
 * 与原版（v1.2.0）的差异：
 *   1. 跨子域 Cookie：secure 跟随 is_ssl()（不再强制 true，避免 HTTP/反向代理下 Cookie 被浏览器丢弃）；
 *      并在 headers 已发送时安全跳过。
 *   2. 新增 POST /checkin/v1/set-cookie：由浏览器携带 token 直接建立 .dreamgary.cn 共享 Cookie，
 *      解决“主页服务端 cURL 登录导致 WordPress 的 Set-Cookie 到不了浏览器”的问题。
 *   3. 新增 POST /checkin/v1/clear-cookie。
 *   4. CORS：反射允许的来源并允许携带凭据（Access-Control-Allow-Credentials: true），处理 OPTIONS 预检。
 *   5. JWT 校验兼容 firebase/php-jwt v5（JWT::decode($t,$secret,['HS256'])）与 v6（new Key(...)）。
 *
 * 注意：本插件与旧版使用同一 REST 命名空间 checkin/v1，请先停用旧版再启用本插件。
 */

defined('ABSPATH') or die();

// ==================== 配置 ====================
define('CHECKIN_TURNSTILE_SECRET', '0x4AAAAAADCXU6Vku-xXuX5pmDeLDoC-Qng');

// 允许跨域携带凭据的来源（主页 / 博客）
function checkin_allowed_origins() {
    return array(
        'https://dreamgary.cn',
        'https://www.dreamgary.cn',
        'https://blog.dreamgary.cn',
    );
}

// ==================== JWT 库加载 ====================

function checkin_load_jwt_library() {
    if (class_exists('Firebase\\JWT\\JWT')) {
        return true;
    }

    $autoload_paths = array(
        WP_PLUGIN_DIR . '/jwt-authentication-for-wp-rest-api/includes/vendor/autoload.php',
        WPMU_PLUGIN_DIR . '/jwt-authentication-for-wp-rest-api/includes/vendor/autoload.php',
        WP_PLUGIN_DIR . '/jwt-authentication-for-wp-rest-api/vendor/autoload.php',
    );

    foreach ($autoload_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            if (class_exists('Firebase\\JWT\\JWT')) {
                return true;
            }
        }
    }
    return false;
}

// ==================== JWT 工具函数 ====================

/**
 * 生成 JWT token，返回 ['token' => string, 'expire' => int] 或 null
 */
function checkin_generate_jwt($user_id) {
    if (!checkin_load_jwt_library()) {
        return null;
    }

    $secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '';
    if (empty($secret)) {
        return null;
    }

    $issued_at = time();
    $expire    = $issued_at + (DAY_IN_SECONDS * 30);

    $payload = array(
        'iss'  => get_bloginfo('url'),
        'iat'  => $issued_at,
        'exp'  => $expire,
        'tv'   => checkin_get_jwt_version($user_id), // token version：退出时递增，旧 token 作废
        'data' => array(
            'user' => array(
                'id' => $user_id,
            ),
        ),
    );

    return array(
        'token'  => \Firebase\JWT\JWT::encode($payload, $secret, 'HS256'),
        'expire' => $expire,
    );
}

/**
 * 解析 JWT（不校验 tv），返回 payload 对象或 null
 */
function checkin_decode_jwt($token) {
    if (!checkin_load_jwt_library()) {
        return null;
    }

    $secret = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '';
    if (empty($secret)) {
        return null;
    }

    try {
        if (class_exists('Firebase\\JWT\\Key')) {
            // firebase/php-jwt v6+
            return \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
        }
        // firebase/php-jwt v5
        return \Firebase\JWT\JWT::decode($token, $secret, array('HS256'));
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * 验证 JWT（签名 + 有效期 + tv），成功返回 user_id，失败返回 null
 */
function checkin_verify_jwt($token) {
    if (empty($token)) {
        return null;
    }

    $decoded = checkin_decode_jwt($token);
    if (!$decoded) {
        return null;
    }

    $user_id = isset($decoded->data->user->id) ? intval($decoded->data->user->id) : 0;
    if (!$user_id || !get_user_by('id', $user_id)) {
        return null;
    }

    // 检查 token 版本号：用户在别处退出后旧 token 作废
    $token_version   = isset($decoded->tv) ? intval($decoded->tv) : 0;
    $current_version = checkin_get_jwt_version($user_id);
    if ($token_version !== $current_version) {
        return null;
    }

    return $user_id;
}

/** 当前请求携带的 JWT：优先 Authorization: Bearer，其次跨子域 Cookie */
function checkin_current_token() {
    $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    if (empty($auth) && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp($k, 'Authorization') === 0) {
                $auth = $v;
                break;
            }
        }
    }
    if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
        return $m[1];
    }
    if (!empty($_COOKIE['wp_jwt_token'])) {
        return sanitize_text_field(wp_unslash($_COOKIE['wp_jwt_token']));
    }
    return '';
}

/** 解析当前请求用户：WP session → Bearer → Cookie */
function checkin_resolve_user_id() {
    $user_id = get_current_user_id();
    if ($user_id) {
        return $user_id;
    }
    return checkin_verify_jwt(checkin_current_token());
}

/** 获取用户的 JWT token 版本号 */
function checkin_get_jwt_version($user_id) {
    return intval(get_user_meta($user_id, 'jwt_token_version', true));
}

/** 递增 token 版本号，使旧 token 失效 */
function checkin_increment_jwt_version($user_id) {
    $version = checkin_get_jwt_version($user_id) + 1;
    update_user_meta($user_id, 'jwt_token_version', $version);
    return $version;
}

// ==================== 跨子域 Cookie ====================

/**
 * 写入共享 JWT Cookie（.dreamgary.cn 同时覆盖 dreamgary.cn / www / blog）
 * secure 跟随当前请求是否 HTTPS，避免 HTTP 或反代下被浏览器丢弃。
 */
function checkin_set_jwt_cookie($token, $expire) {
    if (headers_sent()) {
        return false;
    }

    $params = array(
        'expires'  => $expire,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => false, // 前端 JS 需要读取
        'samesite' => 'Lax',
    );

    // 共享到 .dreamgary.cn
    setcookie('wp_jwt_token', $token, array_merge($params, array('domain' => '.dreamgary.cn')));

    // 无 domain 的兜底（当当前主机的父域不是 dreamgary.cn 时仍然可用）
    setcookie('wp_jwt_token', $token, $params);

    $_COOKIE['wp_jwt_token'] = $token;
    return true;
}

/** 清除共享 JWT Cookie */
function checkin_clear_jwt_cookie() {
    if (headers_sent()) {
        return;
    }

    $expired = array(
        'expires'  => 1,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
    );

    setcookie('wp_jwt_token', '', array_merge($expired, array('domain' => '.dreamgary.cn')));
    setcookie('wp_jwt_token', '', $expired);

    unset($_COOKIE['wp_jwt_token']);
}

// ==================== CORS ====================

/** 反射允许来源并允许携带凭据 */
function checkin_send_cors_headers() {
    $origin = get_http_origin();
    if ($origin && in_array($origin, checkin_allowed_origins(), true)) {
        header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
        header('Access-Control-Allow-Credentials: true');
    }
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
}

// OPTIONS 预检：REST 请求在路由前直接返回
add_action('init', function () {
    if (
        isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS'
        && !empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])
    ) {
        checkin_send_cors_headers();
        status_header(204);
        exit;
    }
});

// 所有 REST 响应附加 CORS
add_filter('rest_pre_serve_request', function ($served) {
    checkin_send_cors_headers();
    return $served;
}, 15);

// ==================== 拦截未验证邮箱的用户登录 ====================

add_filter('wp_authenticate_user', function ($user, $password) {
    if ($user instanceof WP_User) {
        $verify_token   = get_user_meta($user->ID, 'email_verify_token', true);
        $email_verified = get_user_meta($user->ID, 'email_verified', true);
        if ($verify_token && !$email_verified) {
            return new WP_Error(
                'not_verified',
                '你的邮箱尚未验证，请检查邮箱中的验证链接完成验证后再登录。'
            );
        }
    }
    return $user;
}, 99, 2);

// ==================== 注册 REST API 端点 ====================

add_action('rest_api_init', function () {

    $public = '__return_true';

    register_rest_route('checkin/v1', '/register', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_register',
        'permission_callback' => $public,
    ));

    register_rest_route('checkin/v1', '/login', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_login',
        'permission_callback' => $public,
    ));

    register_rest_route('checkin/v1', '/logout', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_logout',
        'permission_callback' => $public,
    ));

    register_rest_route('checkin/v1', '/me', array(
        'methods'             => 'GET',
        'callback'            => 'checkin_me',
        'permission_callback' => $public,
    ));

    // 浏览器侧建立 / 清除共享 Cookie（修复服务端 cURL 登录拿不到 Set-Cookie 的问题）
    register_rest_route('checkin/v1', '/set-cookie', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_set_cookie_endpoint',
        'permission_callback' => $public,
    ));

    register_rest_route('checkin/v1', '/clear-cookie', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_clear_cookie_endpoint',
        'permission_callback' => $public,
    ));

    register_rest_route('checkin/v1', '/verify-email', array(
        'methods'             => 'GET',
        'callback'            => 'checkin_verify_email',
        'permission_callback' => $public,
    ));

    register_rest_route('checkin/v1', '/verify-turnstile', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_verify_turnstile',
        'permission_callback' => $public,
    ));

    $logged_in = function () {
        return is_user_logged_in();
    };

    register_rest_route('checkin/v1', '/dates', array(
        'methods'             => 'GET',
        'callback'            => 'checkin_get_dates',
        'permission_callback' => $logged_in,
    ));
    register_rest_route('checkin/v1', '/dates', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_add_date',
        'permission_callback' => $logged_in,
    ));
    register_rest_route('checkin/v1', '/fortune', array(
        'methods'             => 'GET',
        'callback'            => 'checkin_get_fortune',
        'permission_callback' => $logged_in,
    ));
    register_rest_route('checkin/v1', '/fortune', array(
        'methods'             => 'POST',
        'callback'            => 'checkin_save_fortune',
        'permission_callback' => $logged_in,
    ));
});

// ==================== Cookie 端点 ====================

/**
 * POST /checkin/v1/set-cookie { token }
 * 校验 token 后重新签发并写入 .dreamgary.cn 共享 Cookie（浏览器可直接调用，带 credentials）。
 */
function checkin_set_cookie_endpoint($request) {
    $token   = sanitize_text_field($request->get_param('token'));
    $user_id = checkin_verify_jwt($token);

    if (!$user_id) {
        return new WP_Error('invalid_token', '无效的登录令牌', array('status' => 401));
    }

    $jwt = checkin_generate_jwt($user_id);
    if (!$jwt) {
        return new WP_Error('jwt_unavailable', '无法生成登录令牌', array('status' => 500));
    }

    checkin_set_jwt_cookie($jwt['token'], $jwt['expire']);

    return array(
        'message' => 'ok',
        'token'   => $jwt['token'],
        'expire'  => $jwt['expire'],
        'user_id' => $user_id,
    );
}

/** POST /checkin/v1/clear-cookie */
function checkin_clear_cookie_endpoint() {
    checkin_clear_jwt_cookie();
    return array('message' => 'ok');
}

// ==================== 登录 / 退出 / 状态 ====================

/**
 * 组装用户信息
 */
function checkin_user_payload($user) {
    return array(
        'id'       => $user->ID,
        'username' => $user->user_login,
        'slug'     => $user->user_nicename,
        'email'    => $user->user_email,
        'name'     => $user->display_name ?: $user->user_login,
        // 用 get_avatar() 更能兼容只挂 get_avatar 的头像插件；取不到再退回 get_avatar_url()
        'avatar'   => checkin_avatar_url($user->ID),
    );
}

/** 取实际生效头像（优先 get_avatar 的 <img src>） */
function checkin_avatar_url($user_id) {
    $html = get_avatar($user_id, 96);
    if (is_string($html) && preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    $url = get_avatar_url($user_id, array('size' => 96));
    return is_string($url) ? $url : '';
}

/**
 * POST /checkin/v1/login
 * 主页登录 — 验证用户名密码，生成 JWT，设置跨子域 Cookie，建立 WP session
 */
function checkin_login($request) {
    $username = sanitize_text_field($request->get_param('username'));
    $password = $request->get_param('password');

    if (empty($username) || empty($password)) {
        return new WP_Error('missing_fields', '请填写用户名和密码', array('status' => 400));
    }

    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
        return new WP_Error('login_failed', '用户名或密码错误', array('status' => 401));
    }

    // 邮箱验证检查：仅拦截“有待验证 token 但未验证”的新用户
    $verify_token   = get_user_meta($user->ID, 'email_verify_token', true);
    $email_verified = get_user_meta($user->ID, 'email_verified', true);
    if ($verify_token && !$email_verified) {
        return new WP_Error('not_verified', '请先验证邮箱后再登录，检查你的邮箱中的验证链接', array('status' => 403));
    }

    // 生成共享 JWT + 写入跨子域 Cookie
    $jwt = checkin_generate_jwt($user->ID);
    if ($jwt) {
        checkin_set_jwt_cookie($jwt['token'], $jwt['expire']);
    }

    // 建立 WordPress 端登录状态（blog.dreamgary.cn）
    wp_set_auth_cookie($user->ID, true);

    return array(
        'message' => '登录成功',
        'token'   => $jwt ? $jwt['token'] : null,
        'expire'  => $jwt ? $jwt['expire'] : null,
        'user'    => checkin_user_payload($user),
    );
}

/**
 * POST /checkin/v1/logout
 * 退出登录 — 递增 token 版本（旧 token 立即失效）+ 清除共享 Cookie + WordPress session
 */
function checkin_logout() {
    $user_id = checkin_resolve_user_id();

    if ($user_id) {
        checkin_increment_jwt_version($user_id);
    }

    checkin_clear_jwt_cookie();

    if (is_user_logged_in()) {
        wp_logout();
    }

    return array('message' => '已退出登录');
}

/**
 * GET /checkin/v1/me
 * 返回当前用户登录状态：WP session / Bearer token / 共享 Cookie 三种方式均支持
 */
function checkin_me() {
    $user_id = checkin_resolve_user_id();

    if (!$user_id) {
        return array(
            'logged_in' => false,
            'user'      => null,
        );
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return array(
            'logged_in' => false,
            'user'      => null,
        );
    }

    return array(
        'logged_in' => true,
        'user'      => checkin_user_payload($user),
    );
}

// ==================== Turnstile 验证 ====================

function checkin_verify_turnstile($request) {
    $token = sanitize_text_field($request->get_param('token'));

    if (empty($token)) {
        return new WP_Error('missing_token', '缺少验证令牌', array('status' => 400));
    }

    $result = checkin_turnstile_verify($token);
    if (is_wp_error($result)) {
        return $result;
    }

    return array('success' => true);
}

function checkin_turnstile_verify($token) {
    $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
        'body' => array(
            'secret'   => CHECKIN_TURNSTILE_SECRET,
            'response' => $token,
        ),
    ));

    if (is_wp_error($response)) {
        return new WP_Error('verify_failed', '验证服务异常', array('status' => 500));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['success'])) {
        return new WP_Error('turnstile_failed', '人机验证失败，请重试', array('status' => 400));
    }

    return true;
}

// ==================== 打卡数据 ====================

function checkin_get_dates() {
    $user_id = get_current_user_id();
    $dates   = get_user_meta($user_id, 'daily_checkin_dates', true);
    return is_array($dates) ? $dates : array();
}

function checkin_add_date($request) {
    $user_id = get_current_user_id();
    $dates   = get_user_meta($user_id, 'daily_checkin_dates', true);
    if (!is_array($dates)) {
        $dates = array();
    }

    $date = sanitize_text_field($request->get_param('date'));
    if (!in_array($date, $dates, true)) {
        $dates[] = $date;
        update_user_meta($user_id, 'daily_checkin_dates', $dates);
    }
    return $dates;
}

function checkin_get_fortune() {
    $user_id = get_current_user_id();
    $date    = date('Y-m-d');
    return get_user_meta($user_id, 'daily_fortune_' . $date, true) ?: null;
}

function checkin_save_fortune($request) {
    $user_id = get_current_user_id();
    $date    = date('Y-m-d');
    $params  = $request->get_params();

    $fortune = array(
        'value' => isset($params['value']) ? intval($params['value']) : 0,
        'luck'  => isset($params['luck']) ? sanitize_text_field($params['luck']) : '',
    );

    update_user_meta($user_id, 'daily_fortune_' . $date, $fortune);
    return $fortune;
}

// ==================== 用户注册（含 Turnstile + 邮箱验证） ====================

function checkin_register($request) {
    $username        = sanitize_text_field($request->get_param('username'));
    $email           = sanitize_email($request->get_param('email'));
    $password        = $request->get_param('password');
    $turnstile_token = sanitize_text_field($request->get_param('turnstile_token'));

    if (empty($username) || empty($email) || empty($password)) {
        return new WP_Error('missing_fields', '请填写所有字段', array('status' => 400));
    }
    if (strlen($password) < 6) {
        return new WP_Error('weak_password', '密码至少6位', array('status' => 400));
    }
    if (username_exists($username) || email_exists($email)) {
        return new WP_Error('register_invalid', '注册信息无效，请重试', array('status' => 400));
    }

    $reserved_usernames = array('none');
    if (in_array(strtolower($username), $reserved_usernames, true)) {
        return new WP_Error('reserved_username', '此用户名已被系统保留，请使用其他用户名。', array('status' => 400));
    }

    if (empty($turnstile_token)) {
        return new WP_Error('missing_turnstile', '请完成人机验证', array('status' => 400));
    }
    $verify = checkin_turnstile_verify($turnstile_token);
    if (is_wp_error($verify)) {
        return $verify;
    }

    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass'  => $password,
        'role'       => 'subscriber',
    ));

    if (is_wp_error($user_id)) {
        return new WP_Error('register_failed', $user_id->get_error_message(), array('status' => 400));
    }

    // 生成验证 token，有效期 24 小时
    $verify_token = wp_generate_password(32, false);
    update_user_meta($user_id, 'email_verify_token', $verify_token);
    update_user_meta($user_id, 'email_verify_expire', time() + DAY_IN_SECONDS);

    $verify_url = home_url('/wp-json/checkin/v1/verify-email?token=' . $verify_token . '&user_id=' . $user_id);
    $site_name  = get_bloginfo('name');

    $subject = '【' . $site_name . '】欢迎注册，请验证你的邮箱';
    $message  = '<div style="max-width:480px;margin:0 auto;padding:30px;font-family:Arial,sans-serif;">';
    $message .= '<h2 style="color:#333;">验证你的邮箱</h2>';
    $message .= '<p>你好 <strong>' . esc_html($username) . '</strong>，感谢注册！</p>';
    $message .= '<p>点击下方按钮验证你的邮箱地址：</p>';
    $message .= '<div style="text-align:center;margin:30px 0;">';
    $message .= '<a href="' . esc_url($verify_url) . '" style="display:inline-block;padding:14px 40px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;">验证邮箱</a>';
    $message .= '</div>';
    $message .= '<p style="color:#999;font-size:13px;">如果按钮不能点击，请复制以下链接到浏览器打开：</p>';
    $message .= '<p style="color:#666;font-size:12px;word-break:break-all;background:#f5f5f5;padding:10px;border-radius:4px;">' . esc_url($verify_url) . '</p>';
    $message .= '<p style="color:#999;font-size:13px;">链接 24 小时内有效。</p>';
    $message .= '</div>';

    wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));

    return array(
        'message'     => '验证邮件已发送，请查收邮件并点击链接完成验证',
        'user_id'     => $user_id,
        'username'    => $username,
        'need_verify' => true,
    );
}

/**
 * GET /checkin/v1/verify-email?token=xxx&user_id=xxx
 */
function checkin_verify_email($request) {
    $token   = sanitize_text_field($request->get_param('token'));
    $user_id = intval($request->get_param('user_id'));

    if (empty($token) || !$user_id) {
        status_header(400);
        echo '<h2>无效的验证链接</h2><p>缺少验证参数。</p>';
        exit;
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        status_header(404);
        echo '<h2>用户不存在</h2><p>该账号可能已被删除。</p>';
        exit;
    }

    $stored_token = get_user_meta($user_id, 'email_verify_token', true);
    $expire       = get_user_meta($user_id, 'email_verify_expire', true);

    $card_css = '<style>body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;}'
        . '.card{background:#fff;border-radius:12px;padding:40px;text-align:center;box-shadow:0 2px 20px rgba(0,0,0,0.1);max-width:400px;}</style>';

    if (!$stored_token || !$expire || time() > intval($expire)) {
        delete_user_meta($user_id, 'email_verify_token');
        delete_user_meta($user_id, 'email_verify_expire');
        status_header(410);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>链接已过期</title>' . $card_css . '</head><body>';
        echo '<div class="card"><h2 style="color:#dc2626;">链接已过期</h2><p>验证链接已过期（24小时有效），请重新注册。</p></div></body></html>';
        exit;
    }

    if (!hash_equals($stored_token, $token)) {
        status_header(400);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>无效链接</title>' . $card_css . '</head><body>';
        echo '<div class="card"><h2 style="color:#dc2626;">无效链接</h2><p>验证链接不正确。</p></div></body></html>';
        exit;
    }

    // 验证成功
    delete_user_meta($user_id, 'email_verify_token');
    delete_user_meta($user_id, 'email_verify_expire');
    update_user_meta($user_id, 'email_verified', 1);

    // 自动登录
    $jwt = checkin_generate_jwt($user_id);
    if ($jwt) {
        checkin_set_jwt_cookie($jwt['token'], $jwt['expire']);
    }
    wp_set_auth_cookie($user_id, true);

    $redirect = 'https://dreamgary.cn?verified=1';
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="3;url=' . $redirect . '"><title>验证成功</title>' . $card_css . '</head><body>';
    echo '<div class="card"><h2 style="color:#16a34a;">✓ 邮箱验证成功</h2><p>正在跳转到主站...</p><p style="color:#999;font-size:13px;">如果没有自动跳转，<a href="' . $redirect . '">点击这里</a></p></div></body></html>';
    exit;
}

// ==================== 跨子域 SSO ====================

/**
 * 从共享 JWT Cookie 自动登录 WordPress
 */
add_action('init', function () {
    if (is_user_logged_in()) {
        return;
    }
    if (empty($_COOKIE['wp_jwt_token'])) {
        return;
    }

    $token   = sanitize_text_field(wp_unslash($_COOKIE['wp_jwt_token']));
    $user_id = checkin_verify_jwt($token);

    if ($user_id) {
        wp_set_auth_cookie($user_id, true);
        wp_set_current_user($user_id);
    } else {
        checkin_clear_jwt_cookie();
    }
});

/**
 * 博客登录 → 写入共享 JWT Cookie
 */
add_action('wp_login', function ($user_login, $user) {
    $jwt = checkin_generate_jwt($user->ID);
    if ($jwt) {
        checkin_set_jwt_cookie($jwt['token'], $jwt['expire']);
    }
}, 10, 2);

/**
 * 博客退出 → 递增 token 版本 + 清除共享 JWT Cookie
 */
add_action('wp_logout', function ($user_id) {
    if ($user_id) {
        checkin_increment_jwt_version($user_id);
    }
    checkin_clear_jwt_cookie();
});
