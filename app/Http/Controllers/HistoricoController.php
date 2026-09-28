<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Movimento;
use App\Support\Auditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HistoricoController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $days] = $this->range($request);
        $query = $this->filtered($request, $from, $to);

        $perPage = (int) $request->integer('per_page', 30);
        $perPage = in_array($perPage, [30, 50, 100], true) ? $perPage : 30;

        $rows = (clone $query)
            ->orderByRaw('COALESCE(data_retirada, data_entrada) DESC')
            ->paginate($perPage)
            ->withQueryString();

        $evento = $request->get('evento');
        $timeline = $this->timeline($rows->getCollection(), $evento);

        $base = $this->filtered($request, $from, $to);
        $kpis = [
            'entradas' => (clone $base)->whereBetween('data_entrada', [$from, $to])->count(),
            'retiradas' => (clone $base)->whereNotNull('data_retirada')->whereBetween('data_retirada', [$from, $to])->count(),
            'pendentes' => (clone $base)->where('status', 'pendente')->count(),
            'expiradas' => (clone $base)->where('status', 'expirada')->count(),
        ];

        $avgHours = (float) ((clone $base)
            ->whereNotNull('data_entrada')
            ->whereNotNull('data_retirada')
            ->whereBetween('data_retirada', [$from, $to])
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, data_entrada, data_retirada) / 60')) ?: 0);

        $daily = (clone $base)
            ->selectRaw('DATE(data_entrada) as dia, COUNT(*) as total')
            ->whereBetween('data_entrada', [$from, $to])
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->dia)->format('Y-m-d') => (int) $row->total]);

        $sparkLabels = [];
        $sparkValues = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $sparkLabels[] = $d->format('d/m');
            $sparkValues[] = (int) ($daily[$key] ?? 0);
        }

        return view('historico.index', [
            'rows' => $rows,
            'timeline' => $timeline,
            'kpis' => $kpis,
            'avgHours' => round($avgHours, 1),
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'viewMode' => $request->get('view', 'timeline') === 'tabela' ? 'tabela' : 'timeline',
            'evento' => $evento,
            'condominios' => Condominio::query()->orderBy('nome')->get(['id', 'nome']),
            'lockers' => Locker::query()->orderBy('nome')->get(['id', 'nome']),
            'sparkLabels' => $sparkLabels,
            'sparkValues' => $sparkValues,
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = $this->filtered($request, $from, $to)
            ->orderByRaw('COALESCE(data_retirada, data_entrada) DESC')
            ->with('condominio')
            ->limit(8000)
            ->get();
        Auditor::log('Exportou histórico', 'historico', ['total' => $rows->count()]);

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Entrada', 'Retirada', 'Evento', 'Condomínio', 'Morador', 'Unidade', 'Locker', 'Porta', 'Status'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    optional($r->data_entrada)->format('d/m/Y H:i'),
                    optional($r->data_retirada)->format('d/m/Y H:i'),
                    $r->data_retirada ? 'entrada+retirada' : 'entrada',
                    $r->condominio?->nome,
                    $r->morador_nome,
                    trim(($r->bloco ?? '').' '.$r->apartamento),
                    $r->tablet_nome,
                    $r->porta,
                    $r->status,
                ], ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'historico-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function range(Request $request): array
    {
        $days = (int) $request->integer('periodo', 14);
        $days = in_array($days, [1, 7, 14, 30, 90], true) ? $days : 14;
        $from = $request->filled('de') ? Carbon::parse($request->get('de'))->startOfDay() : now()->subDays($days - 1)->startOfDay();
        $to = $request->filled('ate') ? Carbon::parse($request->get('ate'))->endOfDay() : now()->endOfDay();
        $span = max(1, (int) $from->diffInDays($to) + 1);

        return [$from, $to, $span];
    }

    private function filtered(Request $request, Carbon $from, Carbon $to): Builder
    {
        $q = Movimento::query()->with(['condominio', 'morador', 'locker']);

        $q->where(function (Builder $w) use ($from, $to) {
            $w->whereBetween('data_entrada', [$from, $to])
                ->orWhereBetween('data_retirada', [$from, $to]);
        });

        if (($s = trim((string) $request->get('q'))) !== '') {
            $q->where(function (Builder $w) use ($s) {
                $w->where('morador_nome', 'like', "%{$s}%")
                    ->orWhere('codigo', 'like', "%{$s}%")
                    ->orWhere('porta', 'like', "%{$s}%")
                    ->orWhere('senha', 'like', "%{$s}%")
                    ->orWhere('bloco', 'like', "%{$s}%")
                    ->orWhere('apartamento', 'like', "%{$s}%")
                    ->orWhere('tablet_nome', 'like', "%{$s}%");
            });
        }
        if ($c = $request->get('condominio_id')) {
            $q->where('condominio_id', $c);
        }
        if ($locker = $request->get('locker')) {
            $q->where('tablet_nome', $locker);
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        return $q;
    }

    private function timeline($rows, ?string $evento)
    {
        $events = collect();
        foreach ($rows as $m) {
            if ($m->data_entrada && in_array($evento, [null, '', 'entrada'], true)) {
                $events->push(['at' => $m->data_entrada, 'tipo' => 'entrada', 'm' => $m]);
            }
            if ($m->data_retirada && in_array($evento, [null, '', 'retirada'], true)) {
                $events->push(['at' => $m->data_retirada, 'tipo' => 'retirada', 'm' => $m]);
            }
            if ($evento === 'expirada' && $m->status === 'expirada' && $m->data_entrada) {
                $events->push(['at' => $m->data_entrada, 'tipo' => 'expirada', 'm' => $m]);
            }
        }

        return $events->sortByDesc(fn ($e) => $e['at']->timestamp)->groupBy(fn ($e) => $e['at']->format('Y-m-d'));
    }
}
