<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 40)->default('operador')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        Schema::create('condominios', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_agregacao')->unique();
            $table->string('nome');
            $table->string('cnpj', 20)->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('endereco')->nullable();
            $table->string('api_email')->nullable();
            $table->text('api_password')->nullable();
            $table->enum('status', ['ativo', 'bloqueado', 'removido'])->default('ativo');
            $table->timestamp('ultima_sincronizacao')->nullable();
            $table->timestamps();
        });

        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->string('bloco')->nullable();
            $table->string('apartamento');
            $table->string('identificador')->nullable();
            $table->enum('status', ['ativa', 'inativa'])->default('ativa');
            $table->timestamps();
            $table->unique(['condominio_id', 'bloco', 'apartamento']);
        });

        Schema::create('moradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->foreignId('unidade_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->uuid('uuid_externo')->nullable()->index();
            $table->string('nome');
            $table->string('telefone')->nullable()->index();
            $table->string('tel_ddi', 8)->nullable();
            $table->enum('status', ['ativo', 'bloqueado', 'excluido'])->default('ativo');
            $table->unsignedInteger('quantidade_encomendas')->default(0);
            $table->timestamp('data_cadastro_api')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('lockers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->string('tablet_id')->nullable();
            $table->string('nome');
            $table->unsignedInteger('ocupados')->default(0);
            $table->unsignedInteger('livres')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->string('taxa_ocupacao')->nullable();
            $table->enum('status', ['online', 'offline', 'indisponivel'])->default('online');
            $table->timestamp('atualizado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->foreignId('morador_id')->nullable()->constrained('moradores')->nullOnDelete();
            $table->foreignId('locker_id')->nullable()->constrained('lockers')->nullOnDelete();
            $table->unsignedBigInteger('id_externo')->nullable()->index();
            $table->uuid('guid')->nullable();
            $table->string('morador_nome')->nullable();
            $table->string('bloco')->nullable();
            $table->string('apartamento')->nullable();
            $table->string('porta')->nullable();
            $table->string('senha')->nullable();
            $table->string('codigo')->nullable()->index();
            $table->string('entregador')->nullable();
            $table->timestamp('data_entrada')->nullable()->index();
            $table->timestamp('data_retirada')->nullable();
            $table->enum('status', ['pendente', 'retirada', 'expirada', 'erro'])->default('pendente')->index();
            $table->string('tablet_nome')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condominio_id')->nullable()->constrained('condominios')->nullOnDelete();
            $table->foreignId('morador_id')->nullable()->constrained('moradores')->nullOnDelete();
            $table->string('canal', 40)->default('push');
            $table->string('titulo');
            $table->text('mensagem');
            $table->enum('status', ['pendente', 'enviada', 'falha'])->default('pendente');
            $table->timestamp('enviada_em')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();
        });

        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 60);
            $table->enum('severidade', ['info', 'warning', 'critical'])->default('warning');
            $table->string('titulo');
            $table->text('mensagem');
            $table->boolean('lido')->default(false);
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao');
            $table->string('modulo')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('info');
            $table->string('canal', 40)->default('app');
            $table->text('mensagem');
            $table->json('contexto')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->longText('valor')->nullable();
            $table->string('grupo', 40)->default('geral');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('notificacoes');
        Schema::dropIfExists('movimentos');
        Schema::dropIfExists('lockers');
        Schema::dropIfExists('moradores');
        Schema::dropIfExists('unidades');
        Schema::dropIfExists('condominios');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
