<?php
/**
 * Plugin Name: Gary Avatar API
 * Plugin URI:  https://dreamgary.cn
 * Description: 为个人主页（Gary-homepage）提供“当前登录用户实际生效头像”的 REST 接口，兼容 Simple Local Avatars / One User Avatar 等任意头像插件。
 * Version:     1.1.0
 * Author:      DRheEheAM_Gary
 * License:     GPL-2.0-or-later
 * Text Domain: gary-avatar-api
 */

defined('ABSPATH') or die();

/**
 * GET /wp-json/gary/v1/avatar[?size=96]
 * 需要 JWT 认证（与其它 checkin 接口同一套 token）。
 *
 * 优先使用 get_avatar() 生成的 <img>（可捕获只挂 get_avatar、不挂 get_avatar_url 的头像插件），
 * 从中抽取真实 src；抽不到时再退回 get_avatar_url()。
 */
add_action('rest_api_init', function () {
    register_rest_route('gary/v1', '/avatar', [
        'methods'             => 'GET',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'args'                => [
            'size' => [
                'type'    => 'integer',
                'default' => 96,
            ],
        ],
        'callback'            => function ($request) {
            $user_id = get_current_user_id();
            $size    = (int) $request->get_param('size');
            if ($size <= 0 || $size > 2048) {
                $size = 96;
            }

            // 1) get_avatar() 的 <img> 标签里取 src
            $avatar = '';
            $html   = get_avatar($user_id, $size);
            if (is_string($html) && preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
                $avatar = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            }

            // 2) 退回 get_avatar_url()
            if (empty($avatar)) {
                $url = get_avatar_url($user_id, ['size' => $size]);
                if (is_string($url) && $url !== '') {
                    $avatar = $url;
                }
            }

            return [
                'id'     => $user_id,
                'size'   => $size,
                'avatar' => $avatar,
            ];
        },
    ]);
});
