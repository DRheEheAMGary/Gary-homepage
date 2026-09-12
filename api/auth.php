<?php
/**
 * 认证 API
 * GET  ?action=current                     当前登录用户
 * POST ?action=login      {username,password,turnstile_token}
 * POST ?action=register   {username,email,password,turnstile_token}
 * POST ?action=verify-turnstile {token}
 * POST ?action=logout
 */

require __DIR__ . '/../includes/functions.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($action) {

    case 'current':
        json_out(['user' => current_user()]);

    case 'login':
        if ($method !== 'POST') {
            json_out(['message' => '方法不允许'], 405);
        }
        $in = json_input();
        $username = trim((string) ($in['username'] ?? ''));
        $password = (string) ($in['password'] ?? '');
        if ($username === '' || $password === '') {
            json_out(['message' => '请输入用户名和密码'], 400);
        }

        $turnstile = (string) ($in['turnstile_token'] ?? '');
        if ($turnstile !== '') {
            $verified = turnstile_verify($turnstile);
            if (!$verified['ok']) {
                json_out(['message' => $verified['message']], 400);
            }
        }

        $result = wp_auth($username, $password);
        if (!$result['ok']) {
            json_out(['message' => $result['message']], 400);
        }

        store_token($result['token']);
        $user = fetch_current_user($result['token']);
        if (!$user) {
            $user = [
                'id'     => null,
                'name'   => $result['user']['name'],
                'slug'   => $result['user']['slug'],
                'avatar' => $result['user']['avatar'],
            ];
        }
        $_SESSION['gary_user'] = $user;
        $_SESSION['gary_user_time'] = time();
        json_out(['user' => $user]);

    case 'register':
        if ($method !== 'POST') {
            json_out(['message' => '方法不允许'], 405);
        }
        $in = json_input();
        $username = trim((string) ($in['username'] ?? ''));
        $email = trim((string) ($in['email'] ?? ''));
        $password = (string) ($in['password'] ?? '');
        $password2 = (string) ($in['password2'] ?? '');
        $turnstile = (string) ($in['turnstile_token'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            json_out(['message' => '请填写所有字段'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_out(['message' => '邮箱格式不正确'], 400);
        }
        if ($password2 !== '' && $password !== $password2) {
            json_out(['message' => '两次密码不一致'], 400);
        }
        if (strlen($password) < 6) {
            json_out(['message' => '密码至少6位'], 400);
        }
        if ($turnstile === '') {
            json_out(['message' => '请完成人机验证'], 400);
        }

        $result = wp_register($username, $email, $password, $turnstile);
        if (!$result['ok']) {
            json_out(['message' => $result['message']], 400);
        }
        json_out($result['data']);

    case 'verify-turnstile':
        if ($method !== 'POST') {
            json_out(['message' => '方法不允许'], 405);
        }
        $in = json_input();
        $verified = turnstile_verify((string) ($in['token'] ?? ''));
        if (!$verified['ok']) {
            json_out(['message' => $verified['message']], 400);
        }
        json_out(['success' => true]);

    case 'logout':
        clear_token();
        json_out(['ok' => true]);

    default:
        json_out(['message' => '未知操作'], 404);
}
