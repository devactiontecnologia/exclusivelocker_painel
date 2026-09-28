<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class Auditor
{
    public static function log(string $acao, ?string $modulo = null, array $meta = []): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'acao' => $acao,
            'modulo' => $modulo,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'meta' => $meta ?: null,
        ]);
    }
}
