<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cleans up demo/development links stored in the database:
 *  - footer and slider URLs that point at the developer's machine (http://ecommerce.test/...)
 *    become site-relative paths;
 *  - slider "shop now" buttons that pointed at an admin page go to /products;
 *  - footer links and social icons that go nowhere ("#") are hidden (status 0, not deleted),
 *    so they can be re-enabled from the admin panel once they have a real URL;
 *  - duplicate footer links are hidden and typos in link names are fixed;
 *  - Blog, Track Order and Vendors links are added so both footer columns stay useful.
 */
return new class extends Migration
{
    /** Hosts that only exist on a developer machine. */
    private const DEV_HOST = '#^https?://(ecommerce\.test|localhost|127\.0\.0\.1)(:\d+)?#i';

    private const NAME_FIXES = [
        'terms andconditions' => 'Terms and Conditions',
        'contact' => 'Contact',
        'affilate' => 'Affiliate',
    ];

    private const GRIDS = ['footer_grid_twos', 'footer_grid_threes'];

    public function up(): void
    {
        $now = now();

        // 1. Development hosts -> relative paths (footer links and slider buttons).
        foreach ([['footer_grid_twos', 'url'], ['footer_grid_threes', 'url'], ['sliders', 'btn_url']] as [$table, $col]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (DB::table($table)->get(['id', $col]) as $row) {
                $url = (string) $row->{$col};
                $fixed = preg_replace(self::DEV_HOST, '', $url);
                if ($fixed !== $url) {
                    DB::table($table)->where('id', $row->id)->update([$col => $fixed === '' ? '/' : $fixed]);
                }
            }
        }

        // 2. Slider buttons must not send shoppers to the admin area (or nowhere).
        if (Schema::hasTable('sliders')) {
            DB::table('sliders')
                ->where(fn ($q) => $q->whereNull('btn_url')->orWhere('btn_url', '')->orWhere('btn_url', '#')
                    ->orWhere('btn_url', 'like', '/admin%'))
                ->update(['btn_url' => '/products']);
        }

        foreach (self::GRIDS as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            // 3. Fix link-name typos.
            foreach (DB::table($table)->get(['id', 'name']) as $row) {
                $fix = self::NAME_FIXES[strtolower(trim((string) $row->name))] ?? null;
                if ($fix && $fix !== $row->name) {
                    DB::table($table)->where('id', $row->id)->update(['name' => $fix]);
                }
            }
            // 4. Hide links that go nowhere.
            DB::table($table)->where(fn ($q) => $q->whereNull('url')->orWhereIn('url', ['', '#']))->update(['status' => 0]);
        }

        // 5. A link shown in "Help links" should not also be shown in "More links".
        if (Schema::hasTable('footer_grid_twos') && Schema::hasTable('footer_grid_threes')) {
            $helpUrls = DB::table('footer_grid_threes')->where('status', 1)->pluck('url')->all();
            DB::table('footer_grid_twos')->where('status', 1)->whereIn('url', $helpUrls)->update(['status' => 0]);
        }

        // 6. Make sure each column has real pages in it.
        $this->ensureLink('footer_grid_twos', 'Blog', '/blog', $now);
        $this->ensureLink('footer_grid_threes', 'Track Order', '/product-traking', $now);
        $this->ensureLink('footer_grid_threes', 'Vendors', '/vendor', $now);

        // 7. Hide social icons without a real profile URL.
        if (Schema::hasTable('footer_socials')) {
            DB::table('footer_socials')->where(fn ($q) => $q->whereNull('url')->orWhereIn('url', ['', '#']))->update(['status' => 0]);
        }
    }

    private function ensureLink(string $table, string $name, string $url, $now): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $existing = DB::table($table)->where('url', $url)->first();
        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update(['status' => 1]);
        } else {
            DB::table($table)->insert(['name' => $name, 'url' => $url, 'status' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // Data clean-up only; the previous values were demo/development data.
    }
};
