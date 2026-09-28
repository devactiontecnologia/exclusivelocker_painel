<?php

namespace App\Console\Commands;

use App\Services\LockerSyncService;
use Illuminate\Console\Command;

class SyncLockerCommand extends Command
{
    protected $signature = 'locker:sync {--condo=}';

    protected $description = 'Sincroniza condomínios com a API Exclusive Locker';

    public function handle(LockerSyncService $sync): int
    {
        $this->info('Sincronizando Exclusive Locker...');
        $id = $this->option('condo');
        if ($id) {
            $condo = \App\Models\Condominio::query()->findOrFail($id);
            $stats = $sync->syncCondominio($condo);
            $this->table(array_keys($stats), [array_values($stats)]);
        } else {
            foreach ($sync->syncAll() as $condoId => $stats) {
                $this->line("Condomínio #{$condoId}");
                $this->table(array_keys($stats), [array_values($stats)]);
            }
        }
        $this->info('OK');

        return self::SUCCESS;
    }
}
