<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Locker;
use App\Models\Movimento;
use App\Models\Notificacao;
use App\Support\Auditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CorrespondenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filtered($request);
        $this->sort($query, $request);

        $countsQuery = $this->filtered($request, except: ['status', 'faixa']);
        $counts = [
            'todos' => (clone $countsQuery)->count(),
            'pendente' => (clone $countsQuery)->where('status', 'pendente')->count(),
            'retirada' => (clone $countsQuery)->where('status', 'retirada')->count(),
            'expirada' => (clone $countsQuery)->where('status', 'expirada')->count(),
            'atrasada' => (clone $countsQuery)->where('status', 'pendente')->where('data_entrada', '<', now()->subHours(24))->count(),
        ];

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        return view('correspondencias.index', [
            'rows' => $query->paginate($perPage)->withQueryString(),
            'condominios' => Condominio::query()->orderBy('nome')->get(['id', 'nome']),
            'lockers' => Locker::query()->orderBy('nome')->get(['id', 'nome', 'tablet_id']),
            'counts' => $counts,
            'titulo' => 'Correspondências',
            'modo' => 'operacao',
            'sort' => $request->get('sort', 'data_entrada'),
            'dir' => $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc',
        ]);
    }

    public function show(Movimento $movimento)
    {
        $movimento->load(['condominio', 'morador', 'locker']);

        $related = collect();
        if ($movimento->apartamento || $movimento->morador_id) {
            $related = Movimento::query()
                ->where('id', '!=', $movimento->id)
                ->where('condominio_id', $movimento->condominio_id)
                ->when($movimento->morador_id, fn ($q) => $q->where('morador_id', $movimento->morador_id), fn ($q) => $q->where('bloco', $movimento->bloco)->where('apartamento', $movimento->apartamento))
                ->latest('data_entrada')
                ->limit(6)
                ->get();
        }

        $phone = preg_replace('/\D+/', '', (string) ($movimento->morador?->telefone ?? ''));
        if ($phone && ! str_starts_with($phone, '55')) {
            $phone = '55'.$phone;
        }

        return view('correspondencias.show', [
            'movimento' => $movimento,
            'related' => $related,
            'duration' => $this->durationLabel($movimento),
            'late' => $movimento->status === 'pendente' && $movimento->data_entrada?->lt(now()->subHours(24)),
            'whatsapp' => $phone ? 'https://wa.me/'.$phone.'?text='.rawurlencode($this->avisoTexto($movimento)) : null,
        ]);
    }

    public function notify(Request $request, Movimento $movimento)
    {
        $movimento->load('morador');
        $texto = $this->avisoTexto($movimento);
        Notificacao::query()->create([
            'condominio_id' => $movimento->condominio_id,
            'morador_id' => $movimento->morador_id,
            'canal' => 'push',
            'titulo' => 'Correspondência no locker',
            'mensagem' => $texto,
            'status' => 'enviada',
            'enviada_em' => now(),
        ]);
        Auditor::log('Aviso de correspondência', 'correspondencias', ['id' => $movimento->id]);

        return back()->with('ok', 'Aviso registrado para o morador.');
    }

    private function avisoTexto(Movimento $movimento): string
    {
        $unidade = trim(($movimento->bloco ? 'Bloco '.$movimento->bloco.' ' : '').($movimento->apartamento ? 'Apt '.$movimento->apartamento : ''));

        return trim(sprintf(
            'Olá%s, sua correspondência está no Exclusive Locker%s. Porta %s. Senha %s.',
            $movimento->morador_nome ? ', '.$movimento->morador_nome : '',
            $unidade ? ' ('.$unidade.')' : '',
            $movimento->porta ?: '—',
            $movimento->senha ?: '—'
        ));
    }

    private function durationLabel(Movimento $movimento): string
    {
        if (! $movimento->data_entrada) {
            return '—';
        }
        $end = $movimento->data_retirada ?: now();
        $mins = (int) abs($movimento->data_entrada->diffInMinutes($end));
        $days = intdiv($mins, 1440);
        $hours = intdiv($mins % 1440, 60);
        $minutes = $mins % 60;
        $parts = [];
        if ($days) {
            $parts[] = $days.'d';
        }
        if ($hours || $days) {
            $parts[] = $hours.'h';
        }
        $parts[] = $minutes.'min';
        $suffix = $movimento->data_retirada ? ' até a retirada' : ' na fila';

        return implode(' ', $parts).$suffix;
    }

    public function export(Request $request)
    {
        $query = $this->filtered($request);
        $this->sort($query, $request);
        $rows = $query->with('condominio')->limit(8000)->get();
        Auditor::log('Exportou correspondências filtradas', 'correspondencias', ['total' => $rows->count()]);

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Entrada', 'Retirada', 'Condomínio', 'Morador', 'Unidade', 'Locker', 'Porta', 'Senha', 'Código', 'Entregador', 'Status'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    optional($r->data_entrada)->format('d/m/Y H:i'),
                    optional($r->data_retirada)->format('d/m/Y H:i'),
                    $r->condominio?->nome,
                    $r->morador_nome,
                    trim(($r->bloco ?? '').' '.($r->apartamento ?? '')),
                    $r->tablet_nome,
                    $r->porta,
                    $r->senha,
                    $r->codigo,
                    $r->entregador,
                    $r->status,
                ], ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'correspondencias-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filtered(Request $request, array $except = []): Builder
    {
        $q = Movimento::query()->with(['condominio', 'morador', 'locker']);

        if (! in_array('q', $except, true) && ($s = trim((string) $request->get('q'))) !== '') {
            $q->where(function (Builder $w) use ($s) {
                $w->where('morador_nome', 'like', "%{$s}%")
                    ->orWhere('codigo', 'like', "%{$s}%")
                    ->orWhere('porta', 'like', "%{$s}%")
                    ->orWhere('senha', 'like', "%{$s}%")
                    ->orWhere('bloco', 'like', "%{$s}%")
                    ->orWhere('apartamento', 'like', "%{$s}%")
                    ->orWhere('tablet_nome', 'like', "%{$s}%")
                    ->orWhere('entregador', 'like', "%{$s}%")
                    ->orWhere('guid', 'like', "%{$s}%");
            });
        }

        if (! in_array('status', $except, true) && ($st = $request->get('status'))) {
            $q->where('status', $st);
        }
        if ($c = $request->get('condominio_id')) {
            $q->where('condominio_id', $c);
        }
        if ($locker = $request->get('locker')) {
            $q->where('tablet_nome', $locker);
        }
        if ($request->filled('de')) {
            $q->whereDate('data_entrada', '>=', $request->get('de'));
        }
        if ($request->filled('ate')) {
            $q->whereDate('data_entrada', '<=', $request->get('ate'));
        }

        $faixa = in_array('faixa', $except, true) ? null : $request->get('faixa');
        if ($faixa === 'hoje') {
            $q->whereDate('data_entrada', today());
        } elseif ($faixa === 'atrasada') {
            $q->where('status', 'pendente')->where('data_entrada', '<', now()->subHours(24));
        }

        return $q;
    }

    private function sort(Builder $query, Request $request): void
    {
        $map = [
            'data_entrada' => 'data_entrada',
            'morador' => 'morador_nome',
            'unidade' => 'apartamento',
            'locker' => 'tablet_nome',
            'status' => 'status',
            'porta' => 'porta',
        ];
        $sort = $map[$request->get('sort')] ?? 'data_entrada';
        $dir = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir)->orderByDesc('id');
    }
}
