<?php

declare(strict_types=1);

/*
 * PHPFlasher (toastr) configuration.
 *
 * Replaces the abandoned yoeunes/toastr package. Assets live in
 * public/vendor/flasher (regenerate with `php artisan flasher:install`).
 */
return [
    'default' => 'toastr',

    'main_script' => '/vendor/flasher/flasher.min.js',

    'styles' => [
        '/vendor/flasher/flasher.min.css',
    ],

    'inject_assets' => true,

    'translate' => true,

    'excluded_paths' => [],

    // Don't turn session flash keys (success/error/...) into toasts automatically;
    // the app calls toastr() explicitly, as it did with yoeunes/toastr.
    'flash_bag' => false,

    'plugins' => [
        'toastr' => [
            // Every layout already loads jQuery. The plugin's default list adds its own
            // jquery.min.js, which would replace window.jQuery and drop plugins like
            // DataTables and Select2, so it is left out here.
            'scripts' => [
                '/vendor/flasher/toastr.min.js',
                '/vendor/flasher/flasher-toastr.min.js',
            ],
            'styles' => [
                '/vendor/flasher/toastr.min.css',
            ],
            'options' => [
                'closeButton' => true,
                'progressBar' => true,
                'timeOut' => 5000,
            ],
        ],
    ],
];
