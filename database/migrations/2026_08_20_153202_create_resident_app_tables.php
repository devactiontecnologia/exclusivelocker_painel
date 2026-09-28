<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('morador_id')->constrained('moradores')->cascadeOnDelete();
            $table->foreignId('condominio_id')->constrained('condominios')->cascadeOnDelete();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('telefone')->index();
            $table->string('password');
            $table->boolean('notify_push')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->string('device_token')->nullable();
            $table->string('device_platform', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->unique('morador_id');
        });

        Schema::create('password_reset_tokens_residents', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_account_id')->constrained('resident_accounts')->cascadeOnDelete();
            $table->foreignId('movimento_id')->nullable()->constrained('movimentos')->nullOnDelete();
            $table->string('titulo');
            $table->text('mensagem');
            $table->string('tipo', 40)->default('correspondencia');
            $table->boolean('lida')->default(false);
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_account_id')->constrained('resident_accounts')->cascadeOnDelete();
            $table->foreignId('condominio_id')->nullable()->constrained('condominios')->nullOnDelete();
            $table->string('assunto');
            $table->text('mensagem');
            $table->enum('status', ['aberto', 'em_andamento', 'resolvido'])->default('aberto');
            $table->text('resposta')->nullable();
            $table->timestamps();
        });

        Schema::create('app_contents', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->string('titulo');
            $table->longText('corpo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_contents');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('password_reset_tokens_residents');
        Schema::dropIfExists('resident_accounts');
    }
};
