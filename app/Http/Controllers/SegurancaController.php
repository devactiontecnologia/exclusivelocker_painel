<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;

class SegurancaController extends Controller
{
    public function index()
    {
        return view('seguranca.index', [
            'users' => User::query()->latest()->get(),
            'sessoes' => AuditLog::query()->where('modulo', 'segurança')->latest()->limit(20)->get(),
        ]);
    }
}
