<?php

declare(strict_types=1);

/*
 * PHPFlasher (toastr) configuration.
 *
 * Replaces the abandoned yoeunes/toastr package. Assets live in public/flasher,
 * copied from vendor/php-flasher/{flasher,flasher-toastr}/Resources/public.
 * Don't run `php artisan flasher:install`: it creates public/vendor/, and that
 * folder hides the frontend /vendor route (the server answers 403).
 */
return [
    'default' => 'toastr',

    'main_script' => '/flasher/flasher.min.js',

    'styles' => [
        '/flasher/flasher.min.css',
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
                '/flasher/toastr.min.js',
                '/flasher/flasher-toastr.min.js',
            ],
            'styles' => [
                '/flasher/toastr.min.css',
            ],
            'options' => [
                'closeButton' => true,
                'progressBar' => true,
                'timeOut' => 5000,
            ],
        ],
    ],
];
