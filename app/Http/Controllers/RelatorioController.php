<?php

namespace App\Http\Controllers;

use App\Models\Locker;
use App\Models\Movimento;
use App\Support\Auditor;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    public function index()
    {
        $pendentes = Movimento::query()->where('status', 'pendente')->count();
        $retiradas = Movimento::query()->where('status', 'retirada')->count();
        $avg = Movimento::query()->whereNotNull('data_entrada')->whereNotNull('data_retirada')->get()
            ->avg(fn ($m) => $m->data_entrada && $m->data_retirada ? $m->data_entrada->diffInMinutes($m->data_retirada) / 60 : 0);

        return view('relatorios.index', [
            'pendentes' => $pendentes,
            'retiradas' => $retiradas,
            'avg' => round($avg ?: 0, 1),
            'lockers' => Locker::query()->get(),
        ]);
    }

    public function exportCsv()
    {
        $rows = Movimento::query()->with('condominio')->latest('data_entrada')->limit(5000)->get();
        Auditor::log('Exportou CSV de correspondências', 'relatorios');

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Condomínio', 'Morador', 'Unidade', 'Porta', 'Senha', 'Status', 'Entrada', 'Retirada', 'Código']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id_externo, $r->condominio?->nome, $r->morador_nome,
                    trim(($r->bloco ?? '').' '.($r->apartamento ?? '')), $r->porta, $r->senha, $r->status,
                    optional($r->data_entrada)->format('d/m/Y H:i'),
                    optional($r->data_retirada)->format('d/m/Y H:i'), $r->codigo,
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'correspondencias-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportPdf()
    {
        $rows = Movimento::query()->latest('data_entrada')->limit(200)->get();
        Auditor::log('Exportou PDF de correspondências', 'relatorios');

        return view('relatorios.print', compact('rows'));
    }
}
