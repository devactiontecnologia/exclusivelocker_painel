<?php

namespace Database\Seeders;

use App\Models\AppContent;
use Illuminate\Database\Seeder;

class AppContentSeeder extends Seeder
{
    public function run(): void
    {
        AppContent::query()->updateOrCreate(['chave' => 'termos'], [
            'titulo' => 'Termos de Uso',
            'corpo' => <<<'TXT'
1. Aceite dos termos
Ao utilizar o aplicativo Exclusive Locker, o morador concorda com estes Termos de Uso.

2. Serviço
O aplicativo permite consultar senhas de retirada, acompanhar correspondências e receber avisos relacionados aos armários inteligentes do condomínio.

3. Cadastro
O cadastro vincula o usuário à unidade já existente na base Exclusive Locker, mediante confirmação de telefone e dados pessoais.

4. Responsabilidades
O morador é responsável por manter a confidencialidade da senha de acesso ao app e das senhas de retirada exibidas.

5. Disponibilidade
O serviço depende da sincronização com a API Exclusive Locker e da operação do condomínio. Interrupções temporárias podem ocorrer.

6. Contato
Dúvidas devem ser encaminhadas à administração do condomínio pelo canal de suporte do aplicativo.
TXT
        ]);

        AppContent::query()->updateOrCreate(['chave' => 'privacidade'], [
            'titulo' => 'Política de Privacidade',
            'corpo' => <<<'TXT'
1. Dados coletados
Coletamos nome, e-mail, telefone e preferências de notificação para viabilizar o acesso ao locker e o suporte operacional.

2. Uso dos dados
Os dados são utilizados para autenticação, exibição de correspondências, envio de notificações e atendimento de solicitações de suporte.

3. Integração
Informações de encomendas e unidades são sincronizadas com a plataforma Exclusive Locker do condomínio.

4. Compartilhamento
Não vendemos dados pessoais. O compartilhamento ocorre apenas com a operação do condomínio e provedores necessários ao serviço.

5. Segurança
Adotamos autenticação por token, criptografia de senha e controles de acesso no painel administrativo.

6. Direitos do titular
O morador pode solicitar atualização de dados pelo perfil do aplicativo ou junto à administração do condomínio.
TXT
        ]);
    }
}
