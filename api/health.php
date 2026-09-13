<?php
/**
 * 临时健康检查：诊断登录后头像取数链路
 * 请勿长期保留，排查完成后删除。
 */

require __DIR__ . '/../includes/functions.php';

$avatarPath = defined('GARY_AVATAR_PATH') ? GARY_AVATAR_PATH : '/gary/v1/avatar';
$out = [
    'php_version' => PHP_VERSION,
    'curl'        => function_exists('curl_init'),
    'wp_base'     => WP_BASE,
    'gary_avatar_defined' => defined('GARY_AVATAR_PATH'),
    'logged_in'   => is_logged_in(),
];

$token = current_token();
if (!empty($token)) {
    $out['token_present'] = true;

    list($meOk, $me) = wp_request('GET', WP_USERS_ME_PATH, null, $token);
    $out['me_ok']          = $meOk;
    $out['me_id']          = $me['id'] ?? null;
    $out['me_name']        = $me['name'] ?? null;
    $out['me_avatar_urls'] = $me['avatar_urls'] ?? null;
    $out['me_sla']         = $me['simple_local_avatar'] ?? null;

    list($pOk, $pData, $pStatus) = wp_request('GET', $avatarPath, null, $token);
    $out['plugin_ok']      = $pOk;
    $out['plugin_status']  = $pStatus;
    $out['plugin_body']    = $pData;
    $out['plugin_avatar']  = $pData['avatar'] ?? null;
    $out['plugin_avatar_normalized'] = normalize_avatar($pData['avatar'] ?? null);

    $out['session_avatar'] = $_SESSION['gary_user']['avatar'] ?? null;
    $out['chosen_avatar']  = fetch_current_user($token)['avatar'] ?? null;
}

json_out($out);
