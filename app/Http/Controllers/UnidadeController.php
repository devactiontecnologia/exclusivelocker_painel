<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Models\Unidade;
use App\Support\Auditor;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{
    public function index(Request $request)
    {
        $q = Unidade::query()->with('condominio')->withCount('moradores');
        if ($c = $request->get('condominio_id')) {
            $q->where('condominio_id', $c);
        }
        if ($s = $request->get('q')) {
            $q->where(fn ($w) => $w->where('apartamento', 'like', "%$s%")->orWhere('bloco', 'like', "%$s%"));
        }

        return view('unidades.index', [
            'rows' => $q->orderBy('bloco')->orderBy('apartamento')->paginate(30)->withQueryString(),
            'condominios' => Condominio::query()->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'condominio_id' => ['required', 'exists:condominios,id'],
            'bloco' => ['nullable', 'string', 'max:40'],
            'apartamento' => ['required', 'string', 'max:40'],
        ]);
        $data['identificador'] = trim(($data['bloco'] ?? '').'-'.$data['apartamento'], '-');
        Unidade::query()->create($data);
        Auditor::log('Cadastrou unidade', 'unidades', $data);

        return back()->with('ok', 'Unidade cadastrada.');
    }
}
