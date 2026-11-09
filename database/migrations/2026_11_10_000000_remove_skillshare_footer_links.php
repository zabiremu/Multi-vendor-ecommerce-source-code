<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The demo data pointed several footer links at a skillshare.com class page.
 * Point footer menu links at this site's own pages, and social icons at "#",
 * so the external link disappears from every page.
 */
return new class extends Migration
{
    private const PATTERN = '%skillshare.com%';

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
