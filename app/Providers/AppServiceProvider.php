<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\Brand;
use App\View\Composers\SidebarComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
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
        Paginator::defaultView('pagination.master');
        Paginator::defaultSimpleView('pagination.master');
        Carbon::setLocale('pt_BR');
        View::composer('layouts.app', SidebarComposer::class);
        View::composer(['layouts.app', 'layouts.guest', 'partials.sidebar'], function ($view) {
            try {
                $view->with('brand', Brand::toArray());
            } catch (\Throwable) {
                $view->with('brand', [
                    'name' => config('app.name', 'Exclusive Locker'),
                    'subtitle' => 'Locker Master',
                    'mark' => 'EL',
                    'primary' => '#E2A84A',
                    'logo' => null,
                    'favicon' => null,
                ]);
            }
        });

        $this->applyRuntimeSettings();
    }

    private function applyRuntimeSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                date_default_timezone_set(config('app.timezone'));

                return;
            }
        } catch (\Throwable) {
            date_default_timezone_set(config('app.timezone'));

            return;
        }

        $tz = (string) Setting::get('timezone', config('app.timezone'));
        if ($tz !== '') {
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);
        }

        $mailer = Setting::get('mail_mailer');
        if ($mailer) {
            config(['mail.default' => $mailer]);
        }

        $host = Setting::get('mail_host');
        if ($host !== null && $host !== '') {
            config(['mail.mailers.smtp.host' => $host]);
        }
        $port = Setting::get('mail_port');
        if ($port !== null && $port !== '') {
            config(['mail.mailers.smtp.port' => (int) $port]);
        }
        $user = Setting::get('mail_username');
        if ($user !== null && $user !== '') {
            config(['mail.mailers.smtp.username' => $user]);
        }
        $pass = Setting::get('mail_password');
        if ($pass !== null && $pass !== '') {
            config(['mail.mailers.smtp.password' => $pass]);
        }
        $enc = Setting::get('mail_encryption');
        if ($enc !== null) {
            config(['mail.mailers.smtp.encryption' => $enc !== '' ? $enc : null]);
        }
        $fromAddress = Setting::get('mail_from_address');
        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }
        $fromName = Setting::get('mail_from_name');
        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }

        $empresa = Setting::get('empresa');
        if ($empresa) {
            config(['app.name' => $empresa]);
        }
    }
}
