<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The demo data pointed several footer links (and the LinkedIn/Instagram icons)
 * at https://www.linkedin.com/. Point footer menu links at this site's own pages
 * where one exists, otherwise "#", and social icons at "#".
 * The blog "share on LinkedIn" button is a real feature and is not touched.
 */
return new class extends Migration
{
    private const PATTERN = '%linkedin.com%';

    /** Footer link names that have a matching page on this site. */
    private const PAGES = [
        'contact' => '/contact',
        'about' => '/about',
        'blog' => '/blog',
        'vendor' => '/vendor',
        'vendors' => '/vendor',
        'flash sale' => '/flash-sale',
        'terms and conditions' => '/terms-and-conditions',
        'terms andconditions' => '/terms-and-conditions',
        'track order' => '/product-traking',
        'order history' => '/user/orders',
    ];

    public function up(): void
    {
        foreach (['footer_grid_twos', 'footer_grid_threes'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->where('url', 'like', self::PATTERN)->get()->each(function ($row) use ($table) {
                $url = self::PAGES[strtolower(trim($row->name))] ?? '#';
                DB::table($table)->where('id', $row->id)->update(['url' => $url]);
            });
        }

        if (Schema::hasTable('footer_socials')) {
            DB::table('footer_socials')->where('url', 'like', self::PATTERN)->update(['url' => '#']);
        }
    }

    public function down(): void
    {
        // The removed URLs were demo data; nothing to restore.
    }
};
