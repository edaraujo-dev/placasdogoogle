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
