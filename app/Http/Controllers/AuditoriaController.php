<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::query()->with('user');
        if ($s = $request->get('q')) {
            $q->where('acao', 'like', "%$s%");
        }

        return view('auditoria.index', ['rows' => $q->latest()->paginate(40)->withQueryString()]);
    }
}
