# Edtek Review — atualização v0.4

Pacote para cPanel com PHP 8.2+ e MySQL. Mantém o sistema administrativo existente e adiciona a geração de placas individuais com layout frontal de 10 × 10 cm.

## O que esta versão acrescenta

- Menu **Gerar placas** no painel.
- Lotes de 1, 5, 10, 50 ou quantidade personalizada (até 500 por lote).
- Identificador único por unidade (`ED00000001` etc.).
- Código de ativação individual por placa; o banco guarda somente o hash.
- Impressão em formato quadrado de 100 × 100 mm, uma placa por página.
- QR Code real gerado individualmente para a URL dinâmica da placa.
- Inventário CSV com identificador, URL e código de ativação.
- Utiliza a arte PNG original enviada como referência, sem redesenhar a placa; o sistema substitui a área do QR ilustrativo pelo QR individual e imprime os códigos discretamente.

## Atualização de uma instalação existente

1. Faça backup completo dos arquivos e do banco MySQL.
2. No phpMyAdmin, selecione o banco do Edtek Review e importe `upgrade-v0.4.sql`.
3. Envie os arquivos deste pacote para a pasta do sistema, substituindo os arquivos correspondentes.
4. **Não envie nem substitua `config.php`**. O pacote não inclui o arquivo com credenciais do banco; mantenha o `config.php` que já está no servidor.
5. Entre no painel e abra **Gerar placas**.

Se a instalação for nova, use `schema.sql` para criar as tabelas e configure `config.php` com base em `config.example.php`.

## Geração e impressão

1. Escolha 1, 5, 10, 50 ou uma quantidade personalizada.
2. Gere o lote. Os identificadores são gravados no banco e as placas permanecem em estoque.
3. Clique em **Imprimir placas / Salvar PDF**.
4. Na janela de impressão, mantenha escala em **100% / tamanho real**, margens em **nenhuma** e habilite gráficos de plano de fundo. O CSS define uma página de 100 × 100 mm por placa.
5. O QR Code codifica exatamente a URL dinâmica `/r/CODIGO` daquela placa.
6. Grave no NTAG213 o mesmo endereço mostrado para a respectiva unidade. QR e NFC usam a mesma URL.

A geração visual do QR utiliza a biblioteca QRCode.js carregada do CDN cdnjs; portanto, é necessário estar conectado à internet ao abrir a página de lote e gerar/imprimir as placas. O QR não é uma imagem ilustrativa: é criado a partir do endereço individual de cada unidade.

## Atenção ao código de ativação

O código é impresso na frente, como solicitado. Por ser visível, ele não deve ser considerado uma senha secreta. O banco armazena um hash e o código deve ser tratado no futuro como resgate individual de uso único, mediante login e limitação de tentativas. O fluxo de ativação de revendedores ainda não faz parte desta versão.

## NFC

O sistema não grava fisicamente o chip. Na fabricação, instale um NTAG213 (ou compatível) e grave nele a URL individual da placa, idêntica à codificada no QR. Não reutilize a mesma URL em placas de clínicas diferentes.

## Observações

- O link de avaliação final continua sendo administrado pelo sistema; o QR e o NFC não precisam ser reimpressos quando o destino da placa for alterado.
- A aparência é inspirada na linguagem visual do Google; não se trata de material oficial ou endossado pelo Google.
- Faça backup antes de aplicar a migração.
- O arquivo `config.php` é específico do servidor e não deve ser distribuído.


## Arte de impressão baseada no modelo enviado

A impressão usa `assets/plaque-template.png`, uma cópia da arte de referência enviada pelo usuário. O QR ilustrativo foi removido apenas da área de leitura; o QR real da unidade é sobreposto pelo sistema. A ilustração NFC, logotipo Google, textos, estrelas, moldura e assinatura Edtek permanecem na arte original. Os identificadores e códigos de ativação são impressos discretamente abaixo da área do QR.

## v0.8 — interface responsiva e experiência pública
- Layout administrativo redesenhado com navegação adaptável para celular.
- Dashboard com indicadores, atividade de 7 dias e origem QR/NFC (últimos 30 dias).
- Mensagens de retorno do painel apresentadas em modal.
- Rota pública de placa inativa ou inexistente com página mobile-first e modal explicativo.
- A leitura de placas ativas continua registrando a origem e redirecionando para o link de avaliação do Google.
- Não altera o banco de dados nem a arte/medidas de impressão das placas.

Esta versão moderniza os módulos que já existem no MVP. Cadastro/login de revendedores, portal da clínica, ativação por chave pelo próprio comprador, gestão de kits e e-mails de convite ainda são etapas funcionais futuras e não devem ser consideradas disponíveis nesta versão.

## Atualização v0.9 — navegação lateral e página pública
- Painel administrativo reorganizado com sidebar fixa no desktop e menu lateral deslizante no celular.
- Barra superior contextual com título da seção e identificação do painel.
- Links ativos destacados automaticamente conforme a página aberta.
- Página de leitura QR/NFC reformulada: estado de placa inativa ou inexistente aparece em um modal/card central responsivo, com identidade visual Edtek Review; não há mais conteúdo HTML simples por trás do modal.
- Estilos específicos para telas pequenas e preservação do fluxo de impressão 100 × 100 mm.
- Não exige alteração no banco de dados. Preserve `config.php` durante a atualização.

## v1.0 — contas, ativação pelo comprador e múltiplos produtos

### Atualização em cPanel

1. Faça backup dos arquivos e do banco.
2. Confirme que a versão v0.4 (colunas `activation_code_hash`, `activation_code_hint` e `batch_ref`) já está aplicada.
3. Importe `upgrade-v1.0.sql` uma única vez no phpMyAdmin.
4. Envie os arquivos desta branch para a instalação, preservando o `config.php` atual e os diretórios de uploads/arquivos próprios.
5. Configure o SMTP em **Configurar e-mail** no painel administrativo e use o botão de teste.
6. Teste cadastro, ativação, redirecionamento, recuperação de senha e impressão antes de disponibilizar aos clientes.

Para uma instalação nova: importe `schema.sql` e depois `upgrade-v1.0.sql`. A migração v1.0 pressupõe a estrutura base v0.4.

### Portal do comprador

- `customer-register.php`: cadastro do comprador e da empresa.
- `customer-login.php`: login do cliente.
- `activate-plaque.php`: ativação com código da placa e chave individual impressa.
- `customer-dashboard.php`: placas vinculadas e leituras.
- `customer-plaque.php`: configuração individual do produto e destino.
- A página pública de uma placa em estoque apresenta **Entrar** e **Criar minha conta**, mantendo o código da placa durante o fluxo.
- A ativação usa transação e bloqueio de linha (`SELECT ... FOR UPDATE`); ao concluir, o hash da chave é apagado. A mesma chave não pode ativar a placa novamente.
- O portal do revendedor confere a chave impressa para reivindicar placas, mas não a consome; o comprador ainda pode ativá-la posteriormente.

### Produtos e destinos

Cada placa tem seu próprio tipo e destino, sem compartilhar a configuração com outras placas da mesma empresa:
- `google_review`: URL HTTPS de avaliação Google.
- `whatsapp`: telefone com DDI/DDD e mensagem pré-preenchida opcional.
- `instagram`: URL HTTPS do perfil.

QR Code e NFC continuam apontando para a mesma URL dinâmica `/r/CODIGO`. Alterar o destino no portal não exige reimprimir o QR nem regravar o chip.

### Projetos e inventário

- **Projetos**: agrupe placas por cliente, unidade ou campanha; projetos podem ser associados a uma empresa.
- **Placas geradas**: inventário com lote, produto, empresa, revendedor, projeto, leituras, status e atalho para reimprimir cada unidade.
- Para permitir reimpressão, a chave de ativação também é guardada criptografada (além do hash de validação) e apagada quando a placa é ativada. Placas geradas antes da v1.0 não têm como recuperar a chave original a partir do hash e podem aparecer com `N/D` na reimpressão.
- **Revendedores**: cadastro, login e portal para registrar placas adquiridas em marketplaces. Para reivindicar uma placa, o revendedor informa o código e a chave impressa no formato `ED00000001:CHAVE`. A chave é verificada, mas permanece válida para a ativação final pelo comprador.

### SMTP e DNS

A tela **Configurar e-mail** armazena host, porta, usuário, senha criptografada, modo SSL/STARTTLS, remetente e Reply-To. O teste verifica o envio a partir do servidor; o provedor de hospedagem ainda precisa permitir conexões SMTP de saída.

O sistema não consegue criar registros DNS automaticamente. No painel DNS do domínio, publique os valores exatos fornecidos pelo provedor de e-mail:
- SPF: um único registro TXT SPF consolidado com todos os serviços que enviam pelo domínio.
- DKIM: registro(s) TXT/CNAME e seletor(es) indicados pelo provedor.
- DMARC: TXT em `_dmarc.seudominio`; comece com política de monitoramento (`p=none`) e só aumente a política após analisar os relatórios.

SPF/DKIM/DMARC melhoram autenticação e entregabilidade, mas não garantem que todas as mensagens cheguem à caixa de entrada. Verifique também reputação do domínio/IP, alinhamento do remetente, conteúdo e limites do provedor. Não publique dois registros SPF separados.

### Segurança e limites

- `config.php` nunca deve ser substituído pelo pacote.
- As senhas das contas usam `password_hash()`; tokens de recuperação são aleatórios, armazenados como hash e expiram em uma hora.
- A senha SMTP e as chaves de ativação para reimpressão são criptografadas usando chave derivada da configuração local do banco e URL-base; preserve esses valores ao migrar o banco. Se trocar as credenciais do banco ou a URL-base, salve novamente a senha SMTP. As chaves de placas existentes antes da v1.0 não podem ser recuperadas do hash.
- Faça testes com uma placa de estoque antes de ativar lotes reais.
- A integração SMTP depende das extensões OpenSSL e sockets do PHP e de conexões de saída liberadas no cPanel.
- O portal de revendedor é um controle de inventário; a compra/pagamento dos kits continua ocorrendo no marketplace, fora do sistema.


## Correção de instalação v1.0 parcialmente aplicada (cPanel)

Use este procedimento quando o banco já recebeu parte da v1.0, quando a coluna `activation_code_encrypted` foi criada manualmente ou quando as páginas de clientes/projetos/revendedores não abrem.

1. Faça backup completo do banco e dos arquivos.
2. No phpMyAdmin, selecione explicitamente o banco `estarb84_review` (ou o banco configurado no seu `config.php`).
3. Importe **`upgrade-v1.0-recovery.sql`**. Esse arquivo verifica a existência de colunas e constraints antes de criá-las e usa `CREATE TABLE IF NOT EXISTS` para não recriar tabelas existentes.
4. Não execute novamente o `upgrade-v1.0.sql` original depois de usar a recuperação. Ele não foi escrito para reexecução e pode falhar por colunas/constraints duplicadas.
5. Preserve o `config.php` atual. Depois da migração, entre novamente no painel e teste primeiro com uma placa de teste.

A migração de recuperação cria as tabelas de clientes, projetos, revendedores, pedidos de kits, SMTP, redefinição de senha e tentativas de ativação; adiciona os campos de destino Google/WhatsApp/Instagram e os vínculos necessários nas placas; preserva as empresas e placas existentes. Ela não configura credenciais SMTP e não envia dados para serviços externos.

Após importar, confira se aparecem as tabelas `projects`, `customer_accounts`, `password_resets`, `email_settings`, `reseller_accounts`, `reseller_kit_orders`, `plaque_activation_attempts` e `edtek_schema_migrations`.
