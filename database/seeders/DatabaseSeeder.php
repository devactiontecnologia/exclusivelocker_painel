<?php

namespace Database\Seeders;

use App\Models\Condominio;
use App\Models\Setting;
use App\Models\User;
use App\Services\LockerSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Throwable;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'supera_adm@gmail.com'],
            [
                'name' => 'Master Exclusive Locker',
                'password' => Hash::make('!@SuperaAdm05!@'),
                'role' => 'master',
                'is_active' => true,
            ]
        );

        $condo = Condominio::query()->updateOrCreate(
            ['uuid_agregacao' => config('locker.id_agregacao')],
            [
                'nome' => 'Supera',
                'cidade' => 'Rio de Janeiro',
                'uf' => 'RJ',
                'api_email' => config('locker.email'),
                'api_password' => config('locker.password'),
                'status' => 'ativo',
            ]
        );

        Setting::put('empresa', 'Exclusive Locker');
        Setting::put('documento', 'Plataforma Master');
        Setting::put('cor_primaria', '#E2A84A');
        Setting::put('timezone', 'America/Sao_Paulo');
        Setting::put('sync_minutos', '5');
        Setting::put('sync_automatica', '1');
        Setting::put('push_ativo', '1');
        Setting::put('push_titulo', 'Nova correspondência no locker');
        Setting::put('push_corpo', 'Chegou uma encomenda na porta {porta}. Senha: {senha}.');
        Setting::put('mail_mailer', 'log');
        Setting::put('mail_from_name', 'Exclusive Locker');

        $this->call(AppContentSeeder::class);

        try {
            app(LockerSyncService::class)->syncCondominio($condo);
        } catch (Throwable $e) {
            $this->command?->warn('Sync inicial falhou: '.$e->getMessage());
        }
    }
}
