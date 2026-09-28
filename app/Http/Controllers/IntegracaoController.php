<?php

namespace App\Http\Controllers;

use App\Models\Condominio;
use App\Services\LockerSyncService;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IntegracaoController extends Controller
{
    public function index()
    {
        return view('integracao.index', [
            'condominios' => Condominio::query()->latest('ultima_sincronizacao')->get(),
            'config' => config('locker'),
        ]);
    }

    public function sync(Request $request, LockerSyncService $sync)
    {
        $id = $request->integer('condominio_id');
        try {
            if ($id) {
                $stats = $sync->syncCondominio(Condominio::query()->findOrFail($id));
            } else {
                $stats = $sync->syncAll();
            }
            Auditor::log('Sincronização manual', 'integracao', ['stats' => $stats]);
            Cache::forget('sidebar.meta');

            return back()->with('ok', 'Sincronização concluída.')->with('stats', $stats);
        } catch (\Throwable $e) {
            return back()->withErrors(['sync' => $e->getMessage()]);
        }
    }
}
