<?php
/**
 * Plugin Name: Gary Avatar API
 * Plugin URI:  https://dreamgary.cn
 * Description: 为个人主页（Gary-homepage）提供“当前登录用户实际生效头像”的 REST 接口，兼容 Simple Local Avatars / One User Avatar 等任意头像插件。
 * Version:     1.0.0
 * Author:      DRheEheAM_Gary
 * License:     GPL-2.0-or-later
 * Text Domain: gary-avatar-api
 */

defined('ABSPATH') or die();

/**
 * GET /wp-json/gary/v1/avatar[?size=96]
 * 需要 JWT 认证（与其它 checkin 接口同一套 token）。
 * 返回 get_avatar_url() 计算出的地址，即博客前台实际显示的头像。
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

            return [
                'id'     => $user_id,
                'size'   => $size,
                'avatar' => get_avatar_url($user_id, ['size' => $size]),
            ];
        },
    ]);
});
