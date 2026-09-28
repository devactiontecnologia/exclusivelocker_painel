<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Auditor;
use App\Support\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ConfiguracaoController extends Controller
{
    private const TABS = ['aparencia', 'push', 'email', 'manutencao'];

    public function index(Request $request)
    {
        $tab = $this->resolveTab($request->query('tab'));

        $keys = [
            'empresa', 'documento', 'cor_primaria', 'logo_path', 'favicon_path',
            'push_ativo', 'push_titulo', 'push_corpo',
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password',
            'mail_encryption', 'mail_from_address', 'mail_from_name',
            'timezone', 'sync_minutos', 'sync_automatica',
        ];

        $settings = collect($keys)->mapWithKeys(
            fn ($k) => [$k => Setting::get($k, $this->defaults()[$k] ?? null)]
        );

        $pendingMigrations = $this->pendingMigrations();
        $lastSyncMinutes = (int) Setting::get('sync_minutos', 5);

        return view('configuracoes.index', [
            'tab' => $tab,
            'settings' => $settings,
            'brand' => Brand::toArray(),
            'pendingMigrations' => $pendingMigrations,
            'lastSyncMinutes' => $lastSyncMinutes,
            'storageLinked' => is_link(public_path('storage')) || File::exists(public_path('storage')),
        ]);
    }

    public function update(Request $request)
    {
        $tab = $this->resolveTab($request->input('tab', 'aparencia'));

        return match ($tab) {
            'aparencia' => $this->updateAparencia($request),
            'push' => $this->updatePush($request),
            'email' => $this->updateEmail($request),
            'manutencao' => $this->updateManutencao($request),
            default => back()->withErrors(['tab' => 'Aba inválida.']),
        };
    }

    public function migrate(Request $request)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            Auditor::log('Rodou migrations (sem perda de dados)', 'configuracoes.manutencao', [
                'output' => $output,
            ]);

            return redirect()
                ->route('configuracoes.index', ['tab' => 'manutencao'])
                ->with('ok', 'Migrations executadas com segurança (sem apagar dados).')
                ->with('migrate_output', $output !== '' ? $output : 'Nothing to migrate.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('configuracoes.index', ['tab' => 'manutencao'])
                ->withErrors(['migrate' => $e->getMessage()]);
        }
    }

    public function clearCaches(Request $request)
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Auditor::log('Limpou caches do painel', 'configuracoes.manutencao');

            return redirect()
                ->route('configuracoes.index', ['tab' => 'manutencao'])
                ->with('ok', 'Caches limpos (config, cache, views e rotas).');
        } catch (\Throwable $e) {
            return redirect()
                ->route('configuracoes.index', ['tab' => 'manutencao'])
                ->withErrors(['cache' => $e->getMessage()]);
        }
    }

    public function storageLink(Request $request)
    {
        try {
            if (! is_link(public_path('storage')) && ! File::exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }
            Auditor::log('Garantiu link público de storage', 'configuracoes.manutencao');

            return redirect()
                ->route('configuracoes.index', ['tab' => 'manutencao'])
                ->with('ok', 'Link public/storage disponível para logo e favicon.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('configuracoes.index', ['tab' => 'manutencao'])
                ->withErrors(['storage' => $e->getMessage()]);
        }
    }

    private function updateAparencia(Request $request)
    {
        $data = $request->validate([
            'empresa' => ['required', 'string', 'max:120'],
            'documento' => ['nullable', 'string', 'max:160'],
            'cor_primaria' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,gif,svg', 'max:4096'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,svg,webp', 'max:1024'],
            'remover_logo' => ['nullable', 'boolean'],
            'remover_favicon' => ['nullable', 'boolean'],
        ]);

        Setting::put('empresa', $data['empresa'], 'aparencia');
        Setting::put('documento', $data['documento'] ?? '', 'aparencia');
        Setting::put('cor_primaria', $data['cor_primaria'] ?: '#E2A84A', 'aparencia');

        if ($request->boolean('remover_logo')) {
            $this->deleteBrandFile('logo_path');
        }
        if ($request->boolean('remover_favicon')) {
            $this->deleteBrandFile('favicon_path');
        }

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $this->storeBrandFile($request->file('logo'), 'logo_path', 'logo');
        }
        if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
            $this->storeBrandFile($request->file('favicon'), 'favicon_path', 'favicon');
        }

        Auditor::log('Atualizou aparência do painel', 'configuracoes.aparencia');

        return redirect()
            ->route('configuracoes.index', ['tab' => 'aparencia'])
            ->with('ok', 'Aparência salva.');
    }

    private function updatePush(Request $request)
    {
        $data = $request->validate([
            'push_ativo' => ['nullable', 'in:0,1'],
            'push_titulo' => ['nullable', 'string', 'max:120'],
            'push_corpo' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::put('push_ativo', $request->input('push_ativo', '0'), 'push');
        Setting::put('push_titulo', $data['push_titulo'] ?? 'Nova correspondência no locker', 'push');
        Setting::put('push_corpo', $data['push_corpo'] ?? 'Chegou uma encomenda na porta {porta}. Senha: {senha}.', 'push');

        Auditor::log('Atualizou configurações de push', 'configuracoes.push');

        return redirect()
            ->route('configuracoes.index', ['tab' => 'push'])
            ->with('ok', 'Notificações push atualizadas.');
    }

    private function updateEmail(Request $request)
    {
        $data = $request->validate([
            'mail_mailer' => ['required', 'string', 'max:40'],
            'mail_host' => ['nullable', 'string', 'max:120'],
            'mail_port' => ['nullable', 'string', 'max:10'],
            'mail_username' => ['nullable', 'string', 'max:160'],
            'mail_password' => ['nullable', 'string', 'max:200'],
            'mail_encryption' => ['nullable', 'string', 'max:20'],
            'mail_from_address' => ['nullable', 'email', 'max:160'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
        ]);

        foreach ([
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_encryption', 'mail_from_address', 'mail_from_name',
        ] as $key) {
            Setting::put($key, $data[$key] ?? '', 'email');
        }

        // Só sobrescreve senha se o campo foi preenchido.
        if (($data['mail_password'] ?? '') !== '') {
            Setting::put('mail_password', $data['mail_password'], 'email');
        }

        Auditor::log('Atualizou configurações de e-mail', 'configuracoes.email');

        return redirect()
            ->route('configuracoes.index', ['tab' => 'email'])
            ->with('ok', 'Disparos de e-mail configurados.');
    }

    private function updateManutencao(Request $request)
    {
        $data = $request->validate([
            'timezone' => ['required', 'string', 'max:60'],
            'sync_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'sync_automatica' => ['nullable', 'in:0,1'],
        ]);

        Setting::put('timezone', $data['timezone'], 'manutencao');
        Setting::put('sync_minutos', (string) $data['sync_minutos'], 'manutencao');
        Setting::put('sync_automatica', $request->input('sync_automatica', '1'), 'manutencao');

        Auditor::log('Atualizou parâmetros de manutenção/sync', 'configuracoes.manutencao');

        return redirect()
            ->route('configuracoes.index', ['tab' => 'manutencao'])
            ->with('ok', 'Manutenção e sync automática atualizadas.');
    }

    private function storeBrandFile($file, string $settingKey, string $prefix): void
    {
        $this->deleteBrandFile($settingKey);

        $dir = public_path('branding');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'png'));
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'png';
        $filename = $prefix.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(3)).'.'.$ext;

        $file->move($dir, $filename);
        Setting::put($settingKey, 'branding/'.$filename, 'aparencia');
    }

    private function deleteBrandFile(string $settingKey): void
    {
        $old = ltrim(str_replace('\\', '/', (string) Setting::get($settingKey)), '/');
        if ($old !== '') {
            $publicFile = public_path($old);
            if (is_file($publicFile)) {
                @unlink($publicFile);
            }
            if (Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }
        Setting::put($settingKey, '', 'aparencia');
    }

    private function resolveTab(?string $tab): string
    {
        $tab = strtolower((string) $tab);

        return in_array($tab, self::TABS, true) ? $tab : 'aparencia';
    }

    private function defaults(): array
    {
        return [
            'empresa' => 'Exclusive Locker',
            'documento' => 'Locker Master',
            'cor_primaria' => '#E2A84A',
            'push_ativo' => '1',
            'push_titulo' => 'Nova correspondência no locker',
            'push_corpo' => 'Chegou uma encomenda na porta {porta}. Senha: {senha}.',
            'mail_mailer' => config('mail.default', 'log'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => (string) config('mail.mailers.smtp.port', '587'),
            'mail_username' => config('mail.mailers.smtp.username'),
            'mail_password' => '',
            'mail_encryption' => config('mail.mailers.smtp.encryption') ?: 'tls',
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
            'timezone' => config('app.timezone', 'America/Sao_Paulo'),
            'sync_minutos' => '5',
            'sync_automatica' => '1',
        ];
    }

    private function pendingMigrations(): array
    {
        try {
            $ran = Schema::hasTable('migrations')
                ? collect(DB::table('migrations')->pluck('migration'))->all()
                : [];
            $files = collect(File::files(database_path('migrations')))
                ->map(fn ($f) => pathinfo($f->getFilename(), PATHINFO_FILENAME))
                ->sort()
                ->values();

            return $files->reject(fn ($name) => in_array($name, $ran, true))->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
