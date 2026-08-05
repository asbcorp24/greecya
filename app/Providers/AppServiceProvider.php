<?php

namespace App\Providers;

use App\Models\SeoPage;
use App\Support\SiteSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('*', function ($view) {
            $view->with('site', SiteSettings::all());
        });

        View::composer('layouts.app', function ($view) {
            $seo = null;
            $routeName = request()->route()?->getName();

            if ($routeName && Schema::hasTable('seo_pages')) {
                $seo = SeoPage::query()->active()->where('route_name', $routeName)->first();
            }

            $view->with('seoPage', $seo);
        });
    }
}
