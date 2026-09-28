<?php

namespace App\View\Composers;

use App\Models\Alerta;
use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Movimento;
use App\Models\SupportTicket;
use App\Support\NavIcons;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $meta = Cache::remember('sidebar.meta', 20, function () {
            try {
                $lastSync = Condominio::query()->max('ultima_sincronizacao');

                return [
                    'pendencias' => Movimento::query()->where('status', 'pendente')->count(),
                    'alertas' => Alerta::query()->where('lido', false)->count(),
                    'lockersOffline' => Locker::query()->where('status', '!=', 'online')->count(),
                    'suporte' => SupportTicket::query()->whereIn('status', ['aberto', 'em_andamento'])->count(),
                    'lastSync' => $lastSync,
                ];
            } catch (\Throwable) {
                return [
                    'pendencias' => 0,
                    'alertas' => 0,
                    'lockersOffline' => 0,
                    'suporte' => 0,
                    'lastSync' => null,
                ];
            }
        });

        $view->with('sidebar', [
            'meta' => $meta,
            'icons' => NavIcons::class,
            'groups' => $this->groups($meta),
        ]);
    }

    private function groups(array $meta): array
    {
        return [
            [
                'id' => 'operacao',
                'label' => 'Operação',
                'items' => [
                    ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'hint' => 'Visão geral da operação', 'icon' => 'home'],
                    ['route' => 'estatisticas', 'match' => 'estatisticas', 'label' => 'Painel estatístico', 'hint' => 'Indicadores e séries', 'icon' => 'chart'],
                    ['route' => 'correspondencias.index', 'match' => 'correspondencias.*', 'label' => 'Correspondências', 'hint' => 'Fila e retiradas', 'icon' => 'mail', 'badge' => $meta['pendencias'], 'tone' => 'warn'],
                    ['route' => 'historico.index', 'match' => 'historico.*', 'label' => 'Histórico geral', 'hint' => 'Linha do tempo', 'icon' => 'history'],
                    ['route' => 'lockers.index', 'match' => 'lockers.*', 'label' => 'Monitoramento lockers', 'hint' => 'Ocupação em tempo real', 'icon' => 'locker', 'badge' => $meta['lockersOffline'], 'tone' => 'danger'],
                ],
            ],
            [
                'id' => 'cadastros',
                'label' => 'Cadastros',
                'items' => [
                    ['route' => 'condominios.index', 'match' => 'condominios.*', 'label' => 'Condomínios', 'hint' => 'Clientes e agregações', 'icon' => 'building'],
                    ['route' => 'moradores.index', 'match' => 'moradores.*', 'label' => 'Moradores', 'hint' => 'Unidades e telefones', 'icon' => 'people'],
                    ['route' => 'unidades.index', 'match' => 'unidades.*', 'label' => 'Unidades', 'hint' => 'Blocos e apartamentos', 'icon' => 'grid'],
                ],
            ],
            [
                'id' => 'plataforma',
                'label' => 'Plataforma',
                'items' => [
                    ['route' => 'integracao.index', 'match' => 'integracao.*', 'label' => 'Integração / Sync', 'hint' => 'APIs Exclusive Locker', 'icon' => 'sync'],
                    ['route' => 'notificacoes.index', 'match' => 'notificacoes.*', 'label' => 'Notificações push', 'hint' => 'Avisos aos moradores', 'icon' => 'bell'],
                    ['route' => 'suporte.index', 'match' => 'suporte.*', 'label' => 'Suporte do app', 'hint' => 'Tickets dos moradores', 'icon' => 'support', 'badge' => $meta['suporte'] ?? 0, 'tone' => 'warn'],
                    ['route' => 'alertas.index', 'match' => 'alertas.*', 'label' => 'Central de alertas', 'hint' => 'Incidentes da operação', 'icon' => 'alert', 'badge' => $meta['alertas'], 'tone' => 'warn'],
                    ['route' => 'relatorios.index', 'match' => 'relatorios.*', 'label' => 'Relatórios', 'hint' => 'CSV e impressão', 'icon' => 'report'],
                    ['route' => 'auditoria.index', 'match' => 'auditoria.*', 'label' => 'Auditoria', 'hint' => 'Trilha de acessos', 'icon' => 'audit'],
                    ['route' => 'logs.index', 'match' => 'logs.*', 'label' => 'Logs do sistema', 'hint' => 'Erros e integrações', 'icon' => 'logs'],
                    ['route' => 'configuracoes.index', 'match' => 'configuracoes.*', 'label' => 'Configurações', 'hint' => 'Parâmetros do Master', 'icon' => 'settings'],
                    ['route' => 'seguranca.index', 'match' => 'seguranca.*', 'label' => 'Segurança', 'hint' => 'Sessão e operadores', 'icon' => 'shield'],
                ],
            ],
        ];
    }
}
