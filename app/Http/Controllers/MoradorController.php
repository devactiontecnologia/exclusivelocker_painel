<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Morador;
use App\Models\Movimento;
use App\Support\Auditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MoradorController extends Controller
{
    public function index(Request $request)
    {
        $q = Morador::query()->with(['condominio', 'unidade'])->withCount([
            'movimentos as pendentes_count' => fn ($w) => $w->where('status', 'pendente'),
        ]);

        if ($s = trim((string) $request->get('q'))) {
            $q->where(function (Builder $w) use ($s) {
                $w->where('nome', 'like', "%{$s}%")
                    ->orWhere('telefone', 'like', "%{$s}%")
                    ->orWhereHas('unidade', fn ($u) => $u->where('apartamento', 'like', "%{$s}%")->orWhere('bloco', 'like', "%{$s}%"));
            });
        }
        if ($c = $request->get('condominio_id')) {
            $q->where('condominio_id', $c);
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        return view('moradores.index', [
            'rows' => $q->orderBy('nome')->paginate(25)->withQueryString(),
            'condominios' => Condominio::query()->orderBy('nome')->get(['id', 'nome']),
            'summary' => [
                'total' => Morador::query()->count(),
                'ativos' => Morador::query()->where('status', 'ativo')->count(),
                'bloqueados' => Morador::query()->where('status', 'bloqueado')->count(),
                'excluidos' => Morador::query()->where('status', 'excluido')->count(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $q = Morador::query()->with(['condominio', 'unidade']);
        if ($s = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('nome', 'like', "%{$s}%")->orWhere('telefone', 'like', "%{$s}%"));
        }
        if ($c = $request->get('condominio_id')) {
            $q->where('condominio_id', $c);
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }
        $rows = $q->orderBy('nome')->limit(8000)->get();
        Auditor::log('Exportou moradores', 'moradores', ['total' => $rows->count()]);

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Nome', 'Telefone', 'Unidade', 'Condomínio', 'Status'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [$r->nome, $r->telefone, $r->unidade?->label(), $r->condominio?->nome, $r->status], ';');
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'moradores-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Morador $morador)
    {
        $morador->load(['condominio', 'unidade']);
        $hist = $this->movimentosDoMorador($morador)
            ->latest('data_entrada')
            ->paginate(20)
            ->withQueryString();

        $pendentes = (clone $this->movimentosDoMorador($morador))->where('status', 'pendente')->count();
        $total = (clone $this->movimentosDoMorador($morador))->count();
        $ultima = (clone $this->movimentosDoMorador($morador))->latest('data_entrada')->value('data_entrada');

        $phone = preg_replace('/\D+/', '', (string) $morador->telefone);
        if ($phone && ! str_starts_with($phone, '55')) {
            $phone = '55'.$phone;
        }

        return view('moradores.show', [
            'morador' => $morador,
            'hist' => $hist,
            'kpis' => [
                'total' => $total,
                'pendentes' => $pendentes,
                'ultima' => $ultima,
            ],
            'whatsapp' => $phone ? 'https://wa.me/'.$phone : null,
        ]);
    }

    public function edit(Morador $morador)
    {
        return view('moradores.form', [
            'item' => $morador->load(['condominio', 'unidade']),
        ]);
    }

    public function update(Request $request, Morador $morador)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:180'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'tel_ddi' => ['nullable', 'string', 'max:8'],
            'status' => ['required', 'in:ativo,bloqueado,excluido'],
        ]);
        $morador->update($data);
        Auditor::log('Atualizou morador '.$morador->nome, 'moradores', ['id' => $morador->id]);

        return redirect()->route('moradores.show', $morador)->with('ok', 'Morador atualizado.');
    }

    public function toggle(Morador $morador)
    {
        $next = $morador->status === 'ativo' ? 'bloqueado' : 'ativo';
        $morador->update(['status' => $next]);
        Auditor::log('Alterou status do morador', 'moradores', ['id' => $morador->id, 'status' => $next]);

        return back()->with('ok', 'Status atualizado para '.$next.'.');
    }

    public function destroy(Morador $morador)
    {
        $morador->update(['status' => 'excluido']);
        Auditor::log('Excluiu morador '.$morador->nome, 'moradores');

        return redirect()->route('moradores.index')->with('ok', 'Morador marcado como excluído.');
    }

    private function movimentosDoMorador(Morador $morador): Builder
    {
        return Movimento::query()->where(function (Builder $w) use ($morador) {
            $w->where('morador_id', $morador->id);
            if ($morador->nome) {
                $w->orWhere(function (Builder $q) use ($morador) {
                    $q->where('condominio_id', $morador->condominio_id)
                        ->where('morador_nome', $morador->nome);
                });
            }
        });
    }
}
