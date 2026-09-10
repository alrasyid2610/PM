<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Nama brand yang jadi suffix judul tab browser.
     */
    private const BRAND = 'Pramatek';

    /**
     * Prefix route yang namanya beda dari slug menu — supaya judul tab tetap
     * ikut label menu induknya. Selaras dengan SLUG_MAP di CheckMenuPermission.
     */
    private const SLUG_MAP = [
        'business-relation-sites'    => 'business-relations',
        'business-relation-contacts' => 'business-relations',
        'brs-sampling-points'        => 'business-relations',
        'brs-mp'                     => 'business-relations',
        'br-products'                => 'business-relations',
        'testing-items'              => 'testing-points',
        'fwo-budgets'                => 'fieldworks',
        'fwo-budget-actuals'         => 'fieldworks',
        'lab-samples'                => 'fieldworks',
        'fieldwork-boq'              => 'fieldworks',
        'output-pekerjaan'           => 'work-orders',
        'boq'                        => 'work-orders',
        'wo-budgets'                 => 'work-orders',
        'wo-budget-actuals'          => 'work-orders',
        'wo-samples'                 => 'work-orders',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Judul tab browser dinamis mengikuti menu yang sedang dibuka.
        // Sumber label = config/menus.php (single source of truth yang sama
        // dipakai sidebar & permission matrix). Halaman bisa override dengan
        // @section('title', '...') di blade-nya masing-masing.
        View::composer('layouts.header', function ($view) {
            $view->with('pageTitle', $this->resolvePageTitle());
        });
    }

    private function resolvePageTitle(): string
    {
        $first = request()->segment(1);

        if (!$first || $first === 'dashboard') {
            return self::BRAND;
        }

        $slug = self::SLUG_MAP[$first] ?? $first;

        $label = null;
        foreach (config('menus', []) as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (($item['slug'] ?? null) === $slug) {
                    $label = $item['label'];
                    break 2;
                }
            }
        }

        $label = $label ?: Str::headline($slug);

        // Brand di belakang: saat banyak tab, browser memotong bagian belakang
        // judul lebih dulu — jadi nama modul yang harus tampil duluan.
        return $label . ' · ' . self::BRAND;
    }
}
