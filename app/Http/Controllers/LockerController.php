<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Movimento;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LockerController extends Controller
{
    public function index(Request $request)
    {
        $q = Locker::query()
            ->with('condominio')
            ->withCount([
                'movimentos',
                'movimentos as pendentes_count' => fn ($w) => $w->where('status', 'pendente'),
                'movimentos as hoje_count' => fn ($w) => $w->whereDate('data_entrada', today()),
            ]);

        if ($s = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('nome', 'like', "%{$s}%")
                    ->orWhere('tablet_id', 'like', "%{$s}%")
                    ->orWhereHas('condominio', fn ($c) => $c->where('nome', 'like', "%{$s}%"));
            });
        }
        if ($c = $request->get('condominio_id')) {
            $q->where('condominio_id', $c);
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        $rows = $q->orderBy('nome')->paginate(24)->withQueryString();

        return view('lockers.index', [
            'rows' => $rows,
            'condominios' => Condominio::query()->orderBy('nome')->get(['id', 'nome']),
            'summary' => [
                'total' => Locker::query()->count(),
                'online' => Locker::query()->where('status', 'online')->count(),
                'offline' => Locker::query()->where('status', '!=', 'online')->count(),
                'pendentes' => Movimento::query()->where('status', 'pendente')->count(),
                'ocupacao' => round((float) Locker::query()->avg('taxa_ocupacao'), 1),
            ],
        ]);
    }

    public function show(Request $request, Locker $locker)
    {
        $hist = Movimento::query()
            ->where(function ($w) use ($locker) {
                $w->where('locker_id', $locker->id);
                if ($locker->nome) {
                    $w->orWhere('tablet_nome', $locker->nome);
                }
            })
            ->when($request->get('status'), fn ($q, $st) => $q->where('status', $st))
            ->when(trim((string) $request->get('q')), function ($q, $s) {
                $q->where(fn ($w) => $w->where('morador_nome', 'like', "%{$s}%")->orWhere('porta', 'like', "%{$s}%"));
            })
            ->latest('data_entrada')
            ->paginate(20)
            ->withQueryString();

        $base = Movimento::query()->where(function ($w) use ($locker) {
            $w->where('locker_id', $locker->id);
            if ($locker->nome) {
                $w->orWhere('tablet_nome', $locker->nome);
            }
        });

        $portas = (clone $base)
            ->where('status', 'pendente')
            ->whereNotNull('porta')
            ->orderByRaw('CAST(porta AS UNSIGNED), porta')
            ->get(['id', 'porta', 'morador_nome', 'data_entrada', 'senha', 'status']);

        $from = now()->subDays(13)->startOfDay();
        $daily = (clone $base)
            ->selectRaw('DATE(data_entrada) as dia, COUNT(*) as total')
            ->where('data_entrada', '>=', $from)
            ->groupBy('dia')
            ->get()
            ->mapWithKeys(fn ($row) => [Carbon::parse($row->dia)->format('Y-m-d') => (int) $row->total]);
        $labels = [];
        $values = [];
        for ($i = 0; $i < 14; $i++) {
            $d = $from->copy()->addDays($i);
            $labels[] = $d->format('d/m');
            $values[] = (int) ($daily[$d->format('Y-m-d')] ?? 0);
        }

        return view('lockers.show', [
            'locker' => $locker->load('condominio'),
            'hist' => $hist,
            'portas' => $portas,
            'kpis' => [
                'pendentes' => (clone $base)->where('status', 'pendente')->count(),
                'retiradas' => (clone $base)->where('status', 'retirada')->count(),
                'hoje' => (clone $base)->whereDate('data_entrada', today())->count(),
                'total' => (clone $base)->count(),
            ],
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    public function export(Locker $locker)
    {
        $rows = Movimento::query()
            ->where(function ($w) use ($locker) {
                $w->where('locker_id', $locker->id)->orWhere('tablet_nome', $locker->nome);
            })
            ->latest('data_entrada')
            ->limit(5000)
            ->get();
        Auditor::log('Exportou histórico do locker', 'lockers', ['id' => $locker->id]);

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Entrada', 'Morador', 'Unidade', 'Porta', 'Status'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    optional($r->data_entrada)->format('d/m/Y H:i'),
                    $r->morador_nome,
                    trim(($r->bloco ?? '').' '.($r->apartamento ?? '')),
                    $r->porta,
                    $r->status,
                ], ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'locker-'.$locker->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
