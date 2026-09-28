<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Support\Facades\Cache;

class AlertaController extends Controller
{
    public function index()
    {
        return view('alertas.index', ['rows' => Alerta::query()->latest()->paginate(30)]);
    }

    public function read(Alerta $alerta)
    {
        $alerta->update(['lido' => true, 'resolvido_em' => now()]);
        Cache::forget('sidebar.meta');

        return back();
    }
}
