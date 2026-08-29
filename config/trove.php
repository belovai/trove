<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The locales the interface is translated into. The first entry is not
    | special; the fallback is config('app.locale'). SetLocale intersects the
    | Accept-Language header with this list.
    |
    */

    'locales' => ['en', 'hu'],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | disk:             the filesystem disk holding originals and thumbnails.
    |                   The default 'local' disk is storage/app/private, which
    |                   is outside the web root. Files are always served by a
    |                   controller, never linked directly.
    |
    | max_filesize:     kilobytes. PHP's upload_max_filesize/post_max_size and
    |                   nginx's client_max_body_size must be raised alongside
    |                   this, or the limit silently does not apply.
    |
    | allowed_mimes:    detected from file content, never from the client
    |                   header. SVG is absent on purpose: it can carry script.
    |
    | duplicate_policy: warn   - show the existing item, let the user proceed
    |                   reject - block the upload
    |
    */

    'media' => [
        'disk' => env('TROVE_MEDIA_DISK', 'local'),
        'max_filesize' => (int) env('TROVE_MEDIA_MAX_FILESIZE', 20 * 1024),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'],
        'duplicate_policy' => env('TROVE_MEDIA_DUPLICATE_POLICY', 'warn'),
        'image_driver' => env('TROVE_MEDIA_IMAGE_DRIVER', 'imagick'),
        'prune_after_days' => (int) env('TROVE_MEDIA_PRUNE_AFTER_DAYS', 30),
    ],

];
