<?php
/**
 * WordPress REST API 客户端
 * 统一封装 cURL 请求、认证与 Turnstile 验证
 */

/**
 * 发起 WordPress REST 请求
 *
 * @return array [bool $ok, array $data, int $status]
 */
function wp_request($method, $path, $body = null, $token = null) {
    $url = WP_BASE . $path;
    $headers = ['Content-Type: application/json'];
    if (!empty($token)) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [false, ['message' => '网络请求失败：' . $error], 0];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = ['message' => $raw];
    }

    return [$status >= 200 && $status < 300, $data, $status];
}

/**
 * 登录：用户名 + 密码 → JWT
 * @return array ['ok'=>bool, 'token'=>?string, 'user'=>?array, 'message'=>?string]
 */
function wp_auth($username, $password) {
    list($ok, $data, $status) = wp_request('POST', JWT_LOGIN_PATH, [
        'username' => $username,
        'password' => $password,
    ]);

    if (!$ok || empty($data['token'])) {
        return ['ok' => false, 'message' => $data['message'] ?? '用户名或密码错误'];
    }

    $u = $data['user'] ?? [];
    // 优先 Simple Local Avatars（simple_local_avatar.full）
    $sla = $u['simple_local_avatar'] ?? ($data['simple_local_avatar'] ?? null);
    $avatarRaw = null;
    if (is_array($sla)) {
        $avatarRaw = $sla['96'] ?? ($sla['full'] ?? null);
    }
    if (empty($avatarRaw)) {
        $avatarRaw = $u['avatar']
            ?? ($u['avatar_url'] ?? null)
            ?? ($u['avatar_urls'] ?? null)
            ?? ($data['avatar'] ?? null);
    }

    return [
        'ok'    => true,
        'token' => $data['token'],
        'user'  => [
            'email'  => $u['email'] ?? null,
            'name'   => $u['name'] ?? ($u['username'] ?? $username),
            'slug'   => $u['username'] ?? $username,
            'avatar' => normalize_avatar($avatarRaw),
        ],
    ];
}

/**
 * 注册：用户名 + 邮箱 + 密码 + Turnstile token → 新用户
 * Turnstile 由 WordPress 端校验（保持与原后端一致）
 * @return array ['ok'=>bool, 'data'=>array, 'message'=>?string]
 */
function wp_register($username, $email, $password, $turnstileToken = '') {
    list($ok, $data) = wp_request('POST', CHECKIN_REGISTER_PATH, [
        'username'        => $username,
        'email'           => $email,
        'password'        => $password,
        'turnstile_token' => $turnstileToken,
    ]);

    if (!$ok) {
        return ['ok' => false, 'message' => $data['message'] ?? '注册失败，请重试'];
    }
    return ['ok' => true, 'data' => $data];
}

/**
 * 验证 Cloudflare Turnstile
 * @return array ['ok'=>bool, 'message'=>?string]
 */
function turnstile_verify($token) {
    $token = trim((string) $token);
    if ($token === '') {
        return ['ok' => false, 'message' => '请完成人机验证'];
    }

    $ch = curl_init(TURNSTILE_VERIFY_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => TURNSTILE_SECRET,
            'response' => $token,
        ]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'message' => '验证服务异常'];
    }

    $body = json_decode($raw, true);
    if (empty($body['success'])) {
        return ['ok' => false, 'message' => '人机验证失败，请重试'];
    }
    return ['ok' => true];
}

/**
 * 拉取 GitHub 贡献数据（带文件缓存）
 * @return array|null ['total'=>int, 'contributions'=>array]
 */
function github_contributions($username) {
    $cacheFile = CACHE_DIR . '/github_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $username) . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < GITHUB_CACHE_TTL)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $url = 'https://github-contributions-api.jogruber.de/v4/' . rawurlencode($username) . '?y=last';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: Gary-homepage',
        ],
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    if ($raw === false) {
        // 请求失败时退回旧缓存
        if (is_file($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }
        return null;
    }

    $data = json_decode($raw, true);
    if (empty($data['contributions']) || !is_array($data['contributions'])) {
        return null;
    }

    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0775, true);
    }
    @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $data;
}
