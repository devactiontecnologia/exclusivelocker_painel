# Exclusive Locker Master (Laravel)

Painel administrativo Laravel 12 + MySQL integrado às APIs Exclusive Locker.

## Acesso

- URL (XAMPP): `http://localhost/exclusivelocker_senha/exclusivelocker_painel/login`
- Homologação: `https://homologacao.devactions.com/exclusivelocker_senhas/login`
- E-mail: `supera_adm@gmail.com`
- Senha: `!@SuperaAdm05!@`

## Banco MySQL

Crie o banco e ajuste a senha em `.env`:

```sql
CREATE DATABASE exclusivelocker_painel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=exclusivelocker_painel
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

```bash
cd exclusivelocker_painel
php artisan migrate --seed
php artisan storage:link
php artisan locker:sync
php artisan schedule:work
```

O seeder já sincroniza o condomínio Supera (`e681f986-...`) via API.

## Sync automática

A sincronização roda pelo agendador Laravel (intervalo configurável em **Configurações → Manutenção**).

- Local: `php artisan schedule:work`
- Produção/Hostinger: cron a cada minuto  
  `* * * * * cd /caminho/do/painel && php artisan schedule:run >> /dev/null 2>&1`

## Configurações

Em **Configurações** há abas:

1. **Aparência** — nome, subtítulo, cor, logo e favicon  
2. **Notificações push** — liga/desliga e textos padrão  
3. **E-mail** — SMTP / remetente para disparos  
4. **Manutenção** — sync automática, migrations seguras (`migrate`, sem apagar dados), limpeza de cache e `storage:link`
