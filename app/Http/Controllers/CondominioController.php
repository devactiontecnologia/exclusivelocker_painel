<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Movimento;
use App\Services\ExclusiveLockerApi;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CondominioController extends Controller
{
    public function index(Request $request)
    {
        $q = Condominio::query()->withCount(['moradores', 'movimentos', 'lockers', 'unidades']);
        if ($s = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('nome', 'like', "%{$s}%")
                ->orWhere('cidade', 'like', "%{$s}%")
                ->orWhere('uuid_agregacao', 'like', "%{$s}%")
                ->orWhere('cnpj', 'like', "%{$s}%"));
        }
        if ($st = $request->get('status')) {
            $q->where('status', $st);
        }

        $sorts = ['nome' => 'nome', 'cidade' => 'cidade', 'status' => 'status', 'sync' => 'ultima_sincronizacao'];
        $sort = $sorts[$request->get('sort')] ?? 'nome';
        $dir = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        if ($sort === 'nome') {
            $dir = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        }

        return view('condominios.index', [
            'rows' => $q->orderBy($sort, $dir)->paginate(20)->withQueryString(),
            'summary' => [
                'total' => Condominio::query()->count(),
                'ativos' => Condominio::query()->where('status', 'ativo')->count(),
                'bloqueados' => Condominio::query()->where('status', 'bloqueado')->count(),
                'sync' => Condominio::query()->where('ultima_sincronizacao', '>=', now()->subMinutes(15))->count(),
            ],
            'sort' => $request->get('sort', 'nome'),
            'dir' => $dir,
        ]);
    }

    public function create()
    {
        return view('condominios.form', ['item' => new Condominio]);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['uf'] = $data['uf'] ? strtoupper($data['uf']) : null;
        $item = Condominio::query()->create($data);
        Auditor::log('Cadastrou condomínio '.$item->nome, 'condominios', ['id' => $item->id]);

        return redirect()->route('condominios.show', $item)->with('ok', 'Condomínio cadastrado.');
    }

    public function show(Condominio $condominio)
    {
        $condominio->loadCount(['moradores', 'unidades', 'lockers', 'movimentos']);
        $condominio->load(['lockers' => fn ($q) => $q->orderBy('nome')]);

        return view('condominios.show', [
            'item' => $condominio,
            'pendentes' => Movimento::query()->where('condominio_id', $condominio->id)->where('status', 'pendente')->count(),
            'recentes' => Movimento::query()->where('condominio_id', $condominio->id)->latest('data_entrada')->limit(8)->get(),
        ]);
    }

    public function edit(Condominio $condominio)
    {
        return view('condominios.form', ['item' => $condominio]);
    }

    public function update(Request $request, Condominio $condominio)
    {
        $data = $this->rules($request, $condominio);
        $data['uf'] = ! empty($data['uf']) ? strtoupper($data['uf']) : null;
        if (blank($data['api_password'] ?? null)) {
            unset($data['api_password']);
        }
        $condominio->update($data);
        Auditor::log('Atualizou condomínio '.$condominio->nome, 'condominios', ['id' => $condominio->id]);

        return redirect()->route('condominios.show', $condominio)->with('ok', 'Condomínio atualizado.');
    }

    public function block(Condominio $condominio)
    {
        $next = match ($condominio->status) {
            'bloqueado', 'removido' => 'ativo',
            default => 'bloqueado',
        };
        $condominio->update(['status' => $next]);
        Auditor::log('Alterou status do condomínio', 'condominios', ['id' => $condominio->id, 'status' => $next]);

        return back()->with('ok', 'Status atualizado para '.$next.'.');
    }

    public function destroy(Condominio $condominio)
    {
        $condominio->update(['status' => 'removido']);
        Auditor::log('Removeu condomínio '.$condominio->nome, 'condominios');

        return redirect()->route('condominios.index')->with('ok', 'Condomínio marcado como removido.');
    }

    public function test(Condominio $condominio, ExclusiveLockerApi $api)
    {
        try {
            $api->authenticate($condominio->api_email, $condominio->api_password);
            Auditor::log('Testou API do condomínio', 'condominios', ['id' => $condominio->id]);

            return back()->with('ok', 'Autenticação na API Exclusive Locker concluída com sucesso.');
        } catch (\Throwable $e) {
            return back()->withErrors(['api' => $e->getMessage()]);
        }
    }

    private function rules(Request $request, ?Condominio $item = null): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:180'],
            'uuid_agregacao' => ['required', 'uuid', Rule::unique('condominios', 'uuid_agregacao')->ignore($item?->id)],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'uf' => ['nullable', 'string', 'max:2'],
            'endereco' => ['nullable', 'string', 'max:220'],
            'api_email' => ['nullable', 'email'],
            'api_password' => ['nullable', 'string'],
            'status' => ['required', 'in:ativo,bloqueado,removido'],
        ], [
            'uuid_agregacao.uuid' => 'Informe um UUID de agregação válido.',
            'uuid_agregacao.unique' => 'Este UUID já está cadastrado.',
        ]);
    }
}
