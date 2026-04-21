<?php

return [
    'legacy_wboard_root' => env(
        'LEGACY_WBOARD_ROOT',
        base_path('../peoplecine/public_html/wboard')
    ),
    'legacy_wboard_roots' => array_values(array_filter(array_unique([
        env(
            'LEGACY_WBOARD_ROOT',
            base_path('../peoplecine/public_html/wboard')
        ),
        env('LEGACY_WBOARD_FALLBACK_ROOT'),
    ]))),
    'post_image_limit' => (int) env('PEOPLECINE_POST_IMAGE_LIMIT', 12),
    'post_image_max_kb' => (int) env('PEOPLECINE_POST_IMAGE_MAX_KB', 4096),
    'post_image_max_width' => (int) env('PEOPLECINE_POST_IMAGE_MAX_WIDTH', 1920),
    'post_image_max_height' => (int) env('PEOPLECINE_POST_IMAGE_MAX_HEIGHT', 1080),
    'post_image_base_directory' => env('PEOPLECINE_POST_IMAGE_BASE_DIRECTORY', 'picpost'),
    'post_image_directory_pattern' => env('PEOPLECINE_POST_IMAGE_DIRECTORY_PATTERN', 'Y/m'),
    'post_image_resize_quality' => (float) env('PEOPLECINE_POST_IMAGE_RESIZE_QUALITY', 0.9),
    'forum_search_cooldown_seconds' => (int) env('PEOPLECINE_FORUM_SEARCH_COOLDOWN_SECONDS', 6),
    'forum_search_burst_limit' => (int) env('PEOPLECINE_FORUM_SEARCH_BURST_LIMIT', 8),
    'watermark_site_name' => env('PEOPLECINE_WATERMARK_SITE_NAME', 'www.peoplecine.com'),
    'watermark_font_path' => env('PEOPLECINE_WATERMARK_FONT_PATH', 'C:/Windows/Fonts/tahoma.ttf'),
    'watermark_font_paths' => array_values(array_filter(array_map(
        static fn (string $path): string => trim($path),
        explode('|', (string) env('PEOPLECINE_WATERMARK_FONT_PATHS', ''))
    ))),
    'watermark_timestamp_format' => env('PEOPLECINE_WATERMARK_TIMESTAMP_FORMAT', 'Y-m-d H:i:s'),
    'watermark_margin' => (int) env('PEOPLECINE_WATERMARK_MARGIN', 14),
    'watermark_text_alpha' => (int) env('PEOPLECINE_WATERMARK_TEXT_ALPHA', 0),
    'watermark_shadow_alpha' => (int) env('PEOPLECINE_WATERMARK_SHADOW_ALPHA', 24),
    'watermark_background_alpha' => (int) env('PEOPLECINE_WATERMARK_BACKGROUND_ALPHA', 48),
    'watermark_jpeg_quality' => (int) env('PEOPLECINE_WATERMARK_JPEG_QUALITY', 90),
    'watermark_png_compression' => (int) env('PEOPLECINE_WATERMARK_PNG_COMPRESSION', 6),
    'watermark_webp_quality' => (int) env('PEOPLECINE_WATERMARK_WEBP_QUALITY', 90),
];
