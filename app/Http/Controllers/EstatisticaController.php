<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Movimento;
use App\Support\Auditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstatisticaController extends Controller
{
    public function index(Request $request)
    {
        return view('estatisticas.index', $this->payload($request));
    }

    public function exportCsv(Request $request)
    {
        $data = $this->payload($request);
        Auditor::log('Exportou estatísticas', 'estatisticas', ['periodo' => $data['days']]);

        $callback = function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Indicador', 'Valor']);
            foreach ($data['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['raw'] ?? $kpi['value']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Dia', 'Entradas', 'Retiradas']);
            foreach ($data['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['entradas'][$i], $data['saidas'][$i]]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Unidade', 'Volume', 'Pendentes']);
            foreach ($data['topUnidades'] as $row) {
                fputcsv($out, [trim(($row->bloco ? 'Bloco '.$row->bloco.' ' : '').'Apt '.$row->apartamento), $row->total, $row->pendentes]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'estatisticas-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function payload(Request $request): array
    {
        $days = (int) $request->integer('periodo', 30);
        $days = in_array($days, [7, 14, 30, 90], true) ? $days : 30;
        $condoId = $request->integer('condominio_id') ?: null;
        $from = now()->subDays($days - 1)->startOfDay();
        $prevFrom = $from->copy()->subDays($days);
        $prevTo = $from->copy()->subSecond();

        $entradas = $this->movimentos($condoId)->where('data_entrada', '>=', $from)->count();
        $entradasPrev = $this->movimentos($condoId)->whereBetween('data_entrada', [$prevFrom, $prevTo])->count();
        $retiradasPeriodo = $this->movimentos($condoId)->whereNotNull('data_retirada')->where('data_retirada', '>=', $from)->count();
        $pendentesNow = $this->movimentos($condoId)->where('status', 'pendente')->count();
        $expiradas = $this->movimentos($condoId)->where('data_entrada', '>=', $from)->where('status', 'expirada')->count();

        $statusMap = $this->movimentos($condoId)
            ->where('data_entrada', '>=', $from)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sla = $this->slaMinutes($condoId, $from);
        $avgHours = $sla->isEmpty() ? 0.0 : round($sla->avg() / 60, 1);
        $p90Hours = round($this->percentile($sla, 0.9) / 60, 1);
        $p50Hours = round($this->percentile($sla, 0.5) / 60, 1);
        $conversao = $entradas > 0 ? round((($statusMap['retirada'] ?? 0) / $entradas) * 100, 1) : 0.0;

        $hourly = array_fill(0, 24, 0);
        $this->movimentos($condoId)
            ->where('data_entrada', '>=', $from)
            ->selectRaw('HOUR(data_entrada) as h, COUNT(*) as total')
            ->groupBy('h')
            ->pluck('total', 'h')
            ->each(function ($total, $hour) use (&$hourly) {
                $hourly[(int) $hour] = (int) $total;
            });
        $peakHour = array_search(max($hourly), $hourly, true);
        $peakHour = $hourly[$peakHour] > 0 ? sprintf('%02d:00', $peakHour) : '—';

        $weekdays = array_fill(0, 7, 0);
        $this->movimentos($condoId)
            ->where('data_entrada', '>=', $from)
            ->selectRaw('WEEKDAY(data_entrada) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->each(function ($total, $day) use (&$weekdays) {
                $weekdays[(int) $day] = (int) $total;
            });

        $heatRows = $this->movimentos($condoId)
            ->where('data_entrada', '>=', $from)
            ->selectRaw('WEEKDAY(data_entrada) as d, HOUR(data_entrada) as h, COUNT(*) as total')
            ->groupBy('d', 'h')
            ->get();
        $heat = [];
        $heatMax = 1;
        foreach ($heatRows as $row) {
            $heat[(int) $row->d][(int) $row->h] = (int) $row->total;
            $heatMax = max($heatMax, (int) $row->total);
        }

        $entradasMap = $this->movimentos($condoId)
            ->selectRaw('DATE(data_entrada) as dia, COUNT(*) as total')
            ->where('data_entrada', '>=', $from)
            ->groupBy('dia')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->dia)->format('Y-m-d') => (int) $row->total]);
        $saidasMap = $this->movimentos($condoId)
            ->selectRaw('DATE(data_retirada) as dia, COUNT(*) as total')
            ->whereNotNull('data_retirada')
            ->where('data_retirada', '>=', $from)
            ->groupBy('dia')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->dia)->format('Y-m-d') => (int) $row->total]);

        $labels = [];
        $serieIn = [];
        $serieOut = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d/m');
            $serieIn[] = (int) ($entradasMap[$key] ?? 0);
            $serieOut[] = (int) ($saidasMap[$key] ?? 0);
        }

        $buckets = ['0-4h' => 0, '4-12h' => 0, '12-24h' => 0, '24-48h' => 0, '48h+' => 0];
        foreach ($sla as $minutes) {
            $h = $minutes / 60;
            if ($h < 4) {
                $buckets['0-4h']++;
            } elseif ($h < 12) {
                $buckets['4-12h']++;
            } elseif ($h < 24) {
                $buckets['12-24h']++;
            } elseif ($h < 48) {
                $buckets['24-48h']++;
            } else {
                $buckets['48h+']++;
            }
        }

        return [
            'days' => $days,
            'condoId' => $condoId,
            'condominios' => Condominio::query()->orderBy('nome')->get(['id', 'nome']),
            'kpis' => [
                [
                    'label' => 'Taxa de retirada',
                    'value' => number_format($conversao, 1, ',', '.').'%',
                    'raw' => $conversao,
                    'hint' => ($statusMap['retirada'] ?? 0).' de '.$entradas.' entradas',
                    'tone' => $conversao >= 90 ? 'ok' : ($conversao >= 70 ? 'warn' : 'danger'),
                    'delta' => $this->deltaPct($entradas, $entradasPrev),
                ],
                [
                    'label' => 'SLA médio / P90',
                    'value' => $avgHours.'h',
                    'raw' => $avgHours,
                    'hint' => 'Mediana '.$p50Hours.'h · P90 '.$p90Hours.'h',
                    'tone' => $avgHours <= 12 ? 'ok' : ($avgHours <= 24 ? 'warn' : 'danger'),
                    'delta' => null,
                ],
                [
                    'label' => 'Horário de pico',
                    'value' => $peakHour,
                    'raw' => $peakHour,
                    'hint' => 'Maior volume de entradas',
                    'tone' => 'gold',
                    'delta' => null,
                ],
                [
                    'label' => 'Pendências atuais',
                    'value' => number_format($pendentesNow, 0, ',', '.'),
                    'raw' => $pendentesNow,
                    'hint' => $expiradas.' expiradas no período',
                    'tone' => $pendentesNow > 0 ? 'warn' : 'ok',
                    'delta' => null,
                ],
            ],
            'funnel' => [
                ['label' => 'Entradas', 'value' => $entradas, 'tone' => 'gold'],
                ['label' => 'Retiradas', 'value' => (int) ($statusMap['retirada'] ?? 0), 'tone' => 'ok'],
                ['label' => 'Pendentes', 'value' => (int) ($statusMap['pendente'] ?? 0), 'tone' => 'warn'],
                ['label' => 'Expiradas', 'value' => (int) ($statusMap['expirada'] ?? 0), 'tone' => 'danger'],
            ],
            'labels' => $labels,
            'entradas' => $serieIn,
            'saidas' => $serieOut,
            'hourly' => array_values($hourly),
            'weekdays' => array_values($weekdays),
            'weekdayLabels' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            'heat' => $heat,
            'heatMax' => $heatMax,
            'buckets' => $buckets,
            'statusPie' => [
                'Pendente' => (int) ($statusMap['pendente'] ?? 0),
                'Retirada' => (int) ($statusMap['retirada'] ?? 0),
                'Expirada' => (int) ($statusMap['expirada'] ?? 0),
                'Erro' => (int) ($statusMap['erro'] ?? 0),
            ],
            'topUnidades' => $this->movimentos($condoId)
                ->where('data_entrada', '>=', $from)
                ->select('bloco', 'apartamento', DB::raw('COUNT(*) as total'), DB::raw("SUM(status = 'pendente') as pendentes"))
                ->groupBy('bloco', 'apartamento')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'topLockers' => $this->movimentos($condoId)
                ->where('data_entrada', '>=', $from)
                ->whereNotNull('tablet_nome')
                ->select('tablet_nome', DB::raw('COUNT(*) as total'), DB::raw("SUM(status = 'pendente') as pendentes"))
                ->groupBy('tablet_nome')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'topEntregadores' => $this->movimentos($condoId)
                ->where('data_entrada', '>=', $from)
                ->whereNotNull('entregador')
                ->where('entregador', '!=', '')
                ->select('entregador', DB::raw('COUNT(*) as total'))
                ->groupBy('entregador')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'lockers' => Locker::query()->when($condoId, fn ($q) => $q->where('condominio_id', $condoId))->orderByDesc('ocupados')->get(),
            'retiradasPeriodo' => $retiradasPeriodo,
            'entradasTotal' => $entradas,
        ];
    }

    private function movimentos(?int $condoId): Builder
    {
        return Movimento::query()->when($condoId, fn (Builder $q) => $q->where('condominio_id', $condoId));
    }

    private function slaMinutes(?int $condoId, Carbon $from): Collection
    {
        return $this->movimentos($condoId)
            ->whereNotNull('data_entrada')
            ->whereNotNull('data_retirada')
            ->where('data_retirada', '>=', $from)
            ->selectRaw('TIMESTAMPDIFF(MINUTE, data_entrada, data_retirada) as m')
            ->pluck('m')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v >= 0)
            ->sort()
            ->values();
    }

    private function percentile(Collection $sorted, float $p): float
    {
        if ($sorted->isEmpty()) {
            return 0;
        }
        $index = (int) round(($sorted->count() - 1) * $p);

        return (float) $sorted[$index];
    }

    private function deltaPct(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
