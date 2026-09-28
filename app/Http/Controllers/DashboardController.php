<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Morador;
use App\Models\Movimento;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.index', $this->payload($this->period($request)));
    }

    public function pulse(Request $request)
    {
        $data = $this->payload($this->period($request));
        unset($data['recentes'], $data['alertas'], $data['lockersList'], $data['topPendencias']);

        return response()->json($data);
    }

    private function period(Request $request): int
    {
        $days = (int) $request->integer('periodo', 14);

        return in_array($days, [7, 14, 30], true) ? $days : 14;
    }

    private function payload(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $prevFrom = $from->copy()->subDays($days);
        $prevTo = $from->copy()->subSecond();

        $pendentes = Movimento::query()->where('status', 'pendente')->count();
        $retiradas = Movimento::query()->where('status', 'retirada')->count();
        $expiradas = Movimento::query()->where('status', 'expirada')->count();
        $erros = Movimento::query()->where('status', 'erro')->count();
        $moradores = Morador::query()->where('status', 'ativo')->count();
        $volumeAtual = Movimento::query()->where('data_entrada', '>=', $from)->count();
        $volumeAnterior = Movimento::query()->whereBetween('data_entrada', [$prevFrom, $prevTo])->count();
        $pendentes24h = Movimento::query()->where('status', 'pendente')->where('data_entrada', '<', now()->subHours(24))->count();
        $pendentes48h = Movimento::query()->where('status', 'pendente')->where('data_entrada', '<', now()->subHours(48))->count();
        $retiradasPeriodo = Movimento::query()->whereNotNull('data_retirada')->where('data_retirada', '>=', $from)->count();
        $retiradasPrev = Movimento::query()->whereNotNull('data_retirada')->whereBetween('data_retirada', [$prevFrom, $prevTo])->count();
        $hoje = Movimento::query()->whereDate('data_entrada', today())->count();
        $retiradasHoje = Movimento::query()->whereDate('data_retirada', today())->count();

        $avgHours = (float) (Movimento::query()
            ->whereNotNull('data_entrada')
            ->whereNotNull('data_retirada')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, data_entrada, data_retirada) / 60')) ?: 0);

        $ocupados = (int) Locker::query()->sum('ocupados');
        $livres = (int) Locker::query()->sum('livres');
        $capacidade = (int) Locker::query()->sum('total');
        $ocupacao = $capacidade > 0 ? round($ocupados / $capacidade * 100, 1) : 0.0;
        $lockersOnline = Locker::query()->where('status', 'online')->count();
        $lockersTotal = Locker::query()->count();
        $lastSync = Condominio::query()->max('ultima_sincronizacao');
        $syncFresh = $lastSync && Carbon::parse($lastSync)->gt(now()->subMinutes(15));

        $entradasMap = Movimento::query()
            ->selectRaw('DATE(data_entrada) as dia, COUNT(*) as total')
            ->where('data_entrada', '>=', $from)
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->dia)->format('Y-m-d') => (int) $row->total]);

        $retiradasMap = Movimento::query()
            ->selectRaw('DATE(data_retirada) as dia, COUNT(*) as total')
            ->whereNotNull('data_retirada')
            ->where('data_retirada', '>=', $from)
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->dia)->format('Y-m-d') => (int) $row->total]);

        $labels = [];
        $entradas = [];
        $saidas = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d/m');
            $entradas[] = (int) ($entradasMap[$key] ?? 0);
            $saidas[] = (int) ($retiradasMap[$key] ?? 0);
        }

        return [
            'days' => $days,
            'hello' => now()->hour < 12 ? 'Bom dia' : (now()->hour < 18 ? 'Boa tarde' : 'Boa noite'),
            'kpis' => [
                [
                    'label' => 'Pendências',
                    'value' => $pendentes,
                    'hint' => $pendentes24h.' acima de 24h',
                    'tone' => $pendentes > 0 ? 'warn' : 'ok',
                    'delta' => null,
                    'href' => route('correspondencias.index', ['status' => 'pendente']),
                ],
                [
                    'label' => 'Entradas no período',
                    'value' => $volumeAtual,
                    'hint' => $hoje.' hoje',
                    'tone' => 'gold',
                    'delta' => $this->deltaPct($volumeAtual, $volumeAnterior),
                    'href' => route('correspondencias.index'),
                ],
                [
                    'label' => 'Retiradas no período',
                    'value' => $retiradasPeriodo,
                    'hint' => $retiradasHoje.' hoje',
                    'tone' => 'ok',
                    'delta' => $this->deltaPct($retiradasPeriodo, $retiradasPrev),
                    'href' => route('correspondencias.index', ['status' => 'retirada']),
                ],
                [
                    'label' => 'Ocupação dos lockers',
                    'value' => $ocupacao,
                    'suffix' => '%',
                    'hint' => $ocupados.' ocupados · '.$livres.' livres',
                    'tone' => $ocupacao >= 85 ? 'danger' : ($ocupacao >= 60 ? 'warn' : 'ok'),
                    'delta' => null,
                    'href' => route('lockers.index'),
                ],
            ],
            'health' => [
                ['label' => 'Moradores ativos', 'value' => number_format($moradores, 0, ',', '.'), 'href' => route('moradores.index')],
                ['label' => 'Condomínios', 'value' => Condominio::query()->where('status', 'ativo')->count(), 'href' => route('condominios.index')],
                ['label' => 'Lockers online', 'value' => $lockersOnline.'/'.$lockersTotal, 'href' => route('lockers.index')],
                ['label' => 'Tempo médio', 'value' => round($avgHours, 1).'h', 'href' => route('estatisticas')],
                ['label' => 'Expiradas', 'value' => $expiradas, 'href' => route('correspondencias.index', ['status' => 'expirada'])],
                ['label' => 'Falhas push', 'value' => Notificacao::query()->where('status', 'falha')->count(), 'href' => route('notificacoes.index')],
            ],
            'aging' => [
                'ate24' => max($pendentes - $pendentes24h, 0),
                'h24' => max($pendentes24h - $pendentes48h, 0),
                'h48' => $pendentes48h,
                'total' => $pendentes,
            ],
            'statusPie' => [
                'Pendente' => $pendentes,
                'Retirada' => $retiradas,
                'Expirada' => $expiradas,
                'Erro' => $erros,
            ],
            'labels' => $labels,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'ocupacao' => $ocupacao,
            'capacidade' => $capacidade,
            'ocupados' => $ocupados,
            'livres' => $livres,
            'lastSync' => $lastSync ? Carbon::parse($lastSync) : null,
            'syncFresh' => $syncFresh,
            'avgHours' => round($avgHours, 1),
            'recentes' => Movimento::query()->with('condominio')->latest('data_entrada')->limit(8)->get(),
            'alertas' => Alerta::query()->where('lido', false)->latest()->limit(5)->get(),
            'lockersList' => Locker::query()->with('condominio')->orderByDesc('ocupados')->limit(6)->get(),
            'topPendencias' => Movimento::query()
                ->select('bloco', 'apartamento', DB::raw('COUNT(*) as total'))
                ->where('status', 'pendente')
                ->groupBy('bloco', 'apartamento')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'generatedAt' => now()->format('H:i:s'),
        ];
    }

    private function deltaPct(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
