<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\AppNotification;
use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Morador;
use App\Models\Movimento;
use App\Models\ResidentAccount;
use App\Models\SystemLog;
use App\Models\Unidade;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class LockerSyncService
{
    public function __construct(private ExclusiveLockerApi $api) {}

    public function syncCondominio(Condominio $condo): array
    {
        $stats = ['moradores' => 0, 'unidades' => 0, 'movimentos' => 0, 'lockers' => 0];

        try {
            $this->api->authenticate($condo->api_email ?: null, $condo->api_password ?: null);
            $id = $condo->uuid_agregacao;

            $moradores = $this->api->moradores($id);
            $unidadesCache = [];

            foreach ($moradores as $row) {
                if (! empty($row['deleteado'])) {
                    continue;
                }
                $bloco = (string) ($row['bloco'] ?? '');
                $apto = (string) ($row['apartamento'] ?? '');
                $ukey = $bloco.'|'.$apto;

                if ($apto !== '' && ! isset($unidadesCache[$ukey])) {
                    $unidadesCache[$ukey] = Unidade::query()->updateOrCreate(
                        ['condominio_id' => $condo->id, 'bloco' => $bloco, 'apartamento' => $apto],
                        ['identificador' => trim($bloco.'-'.$apto, '-'), 'status' => 'ativa']
                    );
                    $stats['unidades']++;
                }

                $uuid = $row['id'] ?? null;
                Morador::query()->updateOrCreate(
                    ['condominio_id' => $condo->id, 'uuid_externo' => $uuid],
                    [
                        'unidade_id' => $unidadesCache[$ukey]->id ?? null,
                        'nome' => $row['moradorNome'] ?? 'Sem nome',
                        'telefone' => $row['telefoneCel'] ?? null,
                        'tel_ddi' => $row['telDDI'] ?? null,
                        'status' => 'ativo',
                        'quantidade_encomendas' => (int) ($row['quantidadeEncomendas'] ?? 0),
                        'data_cadastro_api' => $row['dataCadastro'] ?? null,
                        'payload' => $row,
                    ]
                );
                $stats['moradores']++;
            }

            $moradorMap = Morador::query()
                ->where('condominio_id', $condo->id)
                ->whereNotNull('uuid_externo')
                ->pluck('id', 'uuid_externo');

            $lockersByName = [];
            foreach ($this->api->ocupacao($id) as $taxa) {
                $locker = Locker::query()->updateOrCreate(
                    [
                        'condominio_id' => $condo->id,
                        'nome' => $taxa['tabletNome'] ?? 'Armário',
                    ],
                    [
                        'tablet_id' => $taxa['id'] ?? null,
                        'taxa_ocupacao' => $taxa['taxaOcupacao'] ?? null,
                        'ocupados' => (int) ($taxa['taxaOcupacao'] ?? 0),
                        'total' => (int) ($taxa['totalRegistros'] ?? 0),
                        'status' => 'online',
                        'atualizado_em' => now(),
                    ]
                );
                $lockersByName[$locker->nome] = $locker;
                $stats['lockers']++;
            }

            foreach ($this->api->movimentos($id) as $row) {
                $retirada = (string) ($row['MOV_UP_DATA_RETIRADA'] ?? '');
                $pendente = $retirada === '' || $retirada === '0';
                $entrada = $this->parseDate($row['MOV_UP_DATA_ENTRADA'] ?? null);
                $status = $pendente ? 'pendente' : 'retirada';
                if ($pendente && $entrada && $entrada->lt(now()->subDays(15))) {
                    $status = 'expirada';
                }

                $tablet = $row['MOV_UP_TABLET_NOME'] ?? 'Armário';
                if (! isset($lockersByName[$tablet])) {
                    $lockersByName[$tablet] = Locker::query()->updateOrCreate(
                        ['condominio_id' => $condo->id, 'nome' => $tablet],
                        ['status' => 'online', 'atualizado_em' => now()]
                    );
                }

                $movimento = Movimento::query()->updateOrCreate(
                    [
                        'condominio_id' => $condo->id,
                        'id_externo' => $row['id'] ?? null,
                    ],
                    [
                        'morador_id' => $moradorMap[$row['MOV_UP_IDMORADOR'] ?? ''] ?? null,
                        'locker_id' => $lockersByName[$tablet]->id ?? null,
                        'guid' => $row['MOV_UP_GUID'] ?? null,
                        'morador_nome' => $row['MOV_UP_MORADOR_NOME'] ?? null,
                        'bloco' => $row['MOV_UP_MORADOR_BLOCO'] ?? null,
                        'apartamento' => $row['MOV_UP_MORADOR_AP'] ?? null,
                        'porta' => $row['MOV_UP_PORTA_ARMARIO'] ?? null,
                        'senha' => $row['MOV_UP_RETIRADA_SENHA'] ?? null,
                        'codigo' => $row['MOV_UP_DESCRICAO_MERCADORIA'] ?? null,
                        'entregador' => $row['MOV_UP_ENTREGADOR_NOME'] ?? null,
                        'data_entrada' => $entrada,
                        'data_retirada' => $pendente ? null : $this->parseDate($retirada),
                        'status' => $status,
                        'tablet_nome' => $tablet,
                        'payload' => $row,
                    ]
                );
                if ($movimento->wasRecentlyCreated && $status === 'pendente' && $movimento->morador_id) {
                    $this->notifyResident($movimento);
                }
                $stats['movimentos']++;
            }

            $condo->update(['ultima_sincronizacao' => now(), 'status' => $condo->status === 'removido' ? $condo->status : 'ativo']);

            SystemLog::query()->create([
                'level' => 'info',
                'canal' => 'sync',
                'mensagem' => 'Sincronização concluída: '.$condo->nome,
                'contexto' => $stats,
            ]);
        } catch (Throwable $e) {
            Alerta::query()->create([
                'tipo' => 'integracao',
                'severidade' => 'critical',
                'titulo' => 'Falha de integração',
                'mensagem' => $condo->nome.': '.$e->getMessage(),
            ]);
            SystemLog::query()->create([
                'level' => 'error',
                'canal' => 'sync',
                'mensagem' => $e->getMessage(),
                'contexto' => ['condominio' => $condo->id],
            ]);
            Cache::forget('sidebar.meta');
            throw $e;
        }

        Cache::forget('sidebar.meta');

        return $stats;
    }

    public function syncAll(): array
    {
        $out = [];
        foreach (Condominio::query()->where('status', 'ativo')->get() as $condo) {
            $out[$condo->id] = $this->syncCondominio($condo);
        }

        return $out;
    }

    private function notifyResident(Movimento $movimento): void
    {
        if ((string) \App\Models\Setting::get('push_ativo', '1') === '0') {
            return;
        }

        $account = ResidentAccount::query()
            ->where('morador_id', $movimento->morador_id)
            ->where('notify_push', true)
            ->first();
        if (! $account) {
            return;
        }

        $exists = AppNotification::query()
            ->where('resident_account_id', $account->id)
            ->where('movimento_id', $movimento->id)
            ->exists();
        if ($exists) {
            return;
        }

        $titulo = (string) \App\Models\Setting::get(
            'push_titulo',
            'Nova correspondência no locker'
        );
        $corpoTpl = (string) \App\Models\Setting::get(
            'push_corpo',
            'Chegou uma encomenda na porta {porta}. Senha: {senha}.'
        );
        $mensagem = strtr($corpoTpl, [
            '{porta}' => $movimento->porta ?: '—',
            '{senha}' => $movimento->senha ?: '—',
            '{morador}' => $movimento->morador_nome ?: 'Morador',
        ]);

        AppNotification::query()->create([
            'resident_account_id' => $account->id,
            'movimento_id' => $movimento->id,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'tipo' => 'correspondencia',
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
