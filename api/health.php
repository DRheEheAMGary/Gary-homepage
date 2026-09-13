<?php
/**
 * 临时健康检查：诊断登录后页面是否因配置/网络问题报错
 * 请勿长期保留，排查完成后删除。
 */

require __DIR__ . '/../includes/functions.php';

$avatarPath = defined('GARY_AVATAR_PATH') ? GARY_AVATAR_PATH : '/gary/v1/avatar';
list($pluginOk, , $pluginStatus) = wp_request('GET', $avatarPath, null, '');

json_out([
    'php_version'            => PHP_VERSION,
    'curl'                   => function_exists('curl_init'),
    'wp_base'                => WP_BASE,
    'gary_avatar_path'       => $avatarPath,
    'gary_avatar_defined'    => defined('GARY_AVATAR_PATH'),
    'avatar_overrides_def'   => defined('AVATAR_OVERRIDES'),
    'normalize_avatar_exists'=> function_exists('normalize_avatar'),
    'fetch_user_exists'      => function_exists('fetch_current_user'),
    'plugin_status_no_token' => $pluginStatus,
    'plugin_ok'              => $pluginOk,
]);
