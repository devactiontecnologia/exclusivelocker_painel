<?php

use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\CondominioController;
use App\Http\Controllers\CorrespondenciaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstatisticaController;
use App\Http\Controllers\HistoricoController;
use App\Http\Controllers\IntegracaoController;
use App\Http\Controllers\LockerController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MoradorController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SegurancaController;
use App\Http\Controllers\SuporteController;
use App\Http\Controllers\UnidadeController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/recuperar-senha', [LoginController::class, 'forgot'])->name('password.request');
    Route::post('/recuperar-senha', [LoginController::class, 'sendReset'])->name('password.email');
    Route::get('/redefinir-senha/{token}', [LoginController::class, 'resetForm'])->name('password.reset');
    Route::post('/redefinir-senha', [LoginController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/pulse', [DashboardController::class, 'pulse'])->name('dashboard.pulse');
    Route::get('/estatisticas', [EstatisticaController::class, 'index'])->name('estatisticas');
    Route::get('/estatisticas/csv', [EstatisticaController::class, 'exportCsv'])->name('estatisticas.csv');

    Route::get('/condominios', [CondominioController::class, 'index'])->name('condominios.index');
    Route::get('/condominios/novo', [CondominioController::class, 'create'])->name('condominios.create');
    Route::post('/condominios', [CondominioController::class, 'store'])->name('condominios.store');
    Route::get('/condominios/{condominio}/editar', [CondominioController::class, 'edit'])->name('condominios.edit');
    Route::post('/condominios/{condominio}/testar', [CondominioController::class, 'test'])->name('condominios.test');
    Route::put('/condominios/{condominio}', [CondominioController::class, 'update'])->name('condominios.update');
    Route::post('/condominios/{condominio}/bloquear', [CondominioController::class, 'block'])->name('condominios.block');
    Route::delete('/condominios/{condominio}', [CondominioController::class, 'destroy'])->name('condominios.destroy');
    Route::get('/condominios/{condominio}', [CondominioController::class, 'show'])->name('condominios.show');

    Route::get('/moradores', [MoradorController::class, 'index'])->name('moradores.index');
    Route::get('/moradores/exportar', [MoradorController::class, 'export'])->name('moradores.export');
    Route::get('/moradores/{morador}/editar', [MoradorController::class, 'edit'])->name('moradores.edit');
    Route::put('/moradores/{morador}', [MoradorController::class, 'update'])->name('moradores.update');
    Route::post('/moradores/{morador}/toggle', [MoradorController::class, 'toggle'])->name('moradores.toggle');
    Route::delete('/moradores/{morador}', [MoradorController::class, 'destroy'])->name('moradores.destroy');
    Route::get('/moradores/{morador}', [MoradorController::class, 'show'])->name('moradores.show');

    Route::get('/unidades', [UnidadeController::class, 'index'])->name('unidades.index');
    Route::post('/unidades', [UnidadeController::class, 'store'])->name('unidades.store');

    Route::get('/integracao', [IntegracaoController::class, 'index'])->name('integracao.index');
    Route::post('/integracao/sync', [IntegracaoController::class, 'sync'])->name('integracao.sync');

    Route::get('/lockers', [LockerController::class, 'index'])->name('lockers.index');
    Route::get('/lockers/{locker}/exportar', [LockerController::class, 'export'])->name('lockers.export');
    Route::get('/lockers/{locker}', [LockerController::class, 'show'])->name('lockers.show');

    Route::get('/correspondencias', [CorrespondenciaController::class, 'index'])->name('correspondencias.index');
    Route::get('/correspondencias/exportar', [CorrespondenciaController::class, 'export'])->name('correspondencias.export');
    Route::post('/correspondencias/{movimento}/avisar', [CorrespondenciaController::class, 'notify'])->name('correspondencias.notify');
    Route::get('/correspondencias/{movimento}', [CorrespondenciaController::class, 'show'])->name('correspondencias.show');
    Route::get('/historico', [HistoricoController::class, 'index'])->name('historico.index');
    Route::get('/historico/exportar', [HistoricoController::class, 'export'])->name('historico.export');

    Route::get('/notificacoes', [NotificacaoController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes', [NotificacaoController::class, 'store'])->name('notificacoes.store');

    Route::get('/suporte', [SuporteController::class, 'index'])->name('suporte.index');
    Route::get('/suporte/{ticket}', [SuporteController::class, 'show'])->name('suporte.show');
    Route::put('/suporte/{ticket}', [SuporteController::class, 'update'])->name('suporte.update');

    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::post('/alertas/{alerta}/lido', [AlertaController::class, 'read'])->name('alertas.read');

    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
    Route::get('/relatorios/csv', [RelatorioController::class, 'exportCsv'])->name('relatorios.csv');
    Route::get('/relatorios/pdf', [RelatorioController::class, 'exportPdf'])->name('relatorios.pdf');

    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::post('/configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
    Route::post('/configuracoes/migrate', [ConfiguracaoController::class, 'migrate'])->name('configuracoes.migrate');
    Route::post('/configuracoes/cache', [ConfiguracaoController::class, 'clearCaches'])->name('configuracoes.cache');
    Route::post('/configuracoes/storage', [ConfiguracaoController::class, 'storageLink'])->name('configuracoes.storage');
    Route::get('/seguranca', [SegurancaController::class, 'index'])->name('seguranca.index');
});
