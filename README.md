# Convite App

Sistema de convites para eventos via WhatsApp, construído com Laravel, Inertia.js, React e PrimeReact, com integração à Z-API para envio e recebimento de mensagens.

## Visão geral

O objetivo da aplicação é permitir:

- cadastro e gestão de eventos
- cadastro manual e importação de convidados
- envio de convites em texto, imagem ou PDF
- uso de botões de RSVP no WhatsApp
- recebimento de respostas por webhook
- atualização automática do status do convidado
- envio de lembretes para convidados indecisos
- acompanhamento da fila de envio
- visualização de indicadores no dashboard

## Stack

- PHP 8.2
- Laravel 10
- MySQL
- Inertia.js
- React
- PrimeReact
- Vite
- Laravel Queue com `database`
- Scheduler nativo do Laravel
- Z-API como gateway de WhatsApp

## Funcionalidades implementadas

### Eventos

- criação e edição de eventos
- suporte a convite:
  - `text`
  - `image`
  - `pdf`
- upload de arquivo para imagem e PDF
- substituição do arquivo anterior ao trocar o convite
- remoção do arquivo anterior ao voltar o tipo para `Texto`

### Convidados

- cadastro manual por evento
- importação em lote no formato `Nome;Telefone`
- telefone único por evento
- edição e exclusão de convidados
- polling automático da lista de convidados a cada 10 segundos com o modal aberto

### RSVP

Fluxo atual de respostas:

- `Vou` -> status `confirmed`
- `Não vou` -> status `declined`
- `Ainda não sei` -> status `undecided`
- `Vou com crianças` -> status `confirmed`

Observações:

- a informação de ir com crianças fica registrada usando `companions_count > 0`
- na interface isso aparece na coluna `Com crianças` com `Sim` ou `Não`
- o sistema ainda mantém suporte legado para fluxos antigos com acompanhante, mas o fluxo ativo do produto hoje é `Vou com crianças`

### Lembretes

- quando o convidado responde `Ainda não sei`, o sistema agenda lembrete
- o scheduler verifica lembretes vencidos a cada minuto
- o lembrete gera novo dispatch e reutiliza o fluxo de envio do convite

### Fila

- fila de envios por evento
- processamento de convites e lembretes com jobs
- fila de prioridade `high` para webhook de entrada
- fila `default` para envios
- tela para acompanhamento da fila por evento

### Dashboard

- total de eventos
- total de convidados
- total de confirmados
- total de confirmados com crianças
- total de lembretes pendentes
- RSVP por evento

## Arquitetura

O projeto está organizado como um monólito modular em Laravel.

### Backend

- `app/Http/Controllers`
  - controllers administrativos
  - dashboard
  - webhooks
- `app/Jobs`
  - envio de convites
  - envio de lembretes
  - processamento de mensagens recebidas
- `app/Services`
  - parser de resposta
  - máquina de estados do convidado
  - normalização de telefone
  - agendamento de lembretes
  - configuração da Z-API
- `app/Integrations/ZApi`
  - implementação do gateway de WhatsApp
- `app/Models`
  - entidades persistidas
- `app/Enums`
  - enums de status e tipos de negócio

### Frontend

- `resources/js/Pages`
  - dashboard
  - eventos
  - fila
  - configurações da Z-API
- `resources/js/Components`
  - cards
  - tags de status
- `resources/js/Layouts`
  - layout autenticado
  - menu
  - topbar

## Estrutura principal

```text
app/
  Console/
  Enums/
  Http/
    Controllers/
    Middleware/
    Requests/
  Integrations/
    ZApi/
  Jobs/
  Models/
  Services/
  Support/
database/
  migrations/
resources/
  js/
    Components/
    Layouts/
    Pages/
routes/
  web.php
  api.php
```

## Banco de dados

### Tabelas principais

- `events`
- `guests`
- `invitation_dispatches`
- `guest_responses`
- `reminder_schedules`
- `webhook_receipts`
- `app_settings`
- `jobs`
- `failed_jobs`
- `users`

### Resumo das entidades

#### `events`

Armazena os dados do evento:

- nome
- anfitrião
- data
- hora
- local
- tipo do convite
- URL do asset
- template da mensagem
- dias de lembrete
- status

#### `guests`

Armazena os convidados por evento:

- nome
- telefone original
- telefone normalizado
- status atual
- `companions_count`
- timestamps de resposta e envio

#### `invitation_dispatches`

Histórico de cada envio realizado:

- tipo do envio
- tipo da mídia
- mensagem enviada
- URL do asset
- status de entrega
- IDs retornados pelo provider

#### `guest_responses`

Histórico de cada resposta recebida:

- `inbound_message_id`
- mensagem bruta
- intenção interpretada
- quantidade de acompanhantes/crianças quando aplicável
- payload original do webhook

#### `reminder_schedules`

Controle dos lembretes:

- data programada
- status
- motivo
- data de processamento

#### `webhook_receipts`

Auditoria dos webhooks recebidos:

- provider
- tipo do evento
- identificador externo
- headers
- payload bruto
- data de recebimento
- data de processamento

#### `app_settings`

Persistência de configurações de integração, como:

- `zapi.instance_id`
- `zapi.token`
- `zapi.client_token`
- status do último teste de conexão

## Estados de negócio

### Status do convidado

Arquivo: [app/Enums/GuestStatus.php](/private/var/www/convite-app/app/Enums/GuestStatus.php)

- `pending`
- `confirmed`
- `declined`
- `undecided`
- `confirmed_with_companion`
- `waiting_companion_count`

Observação:

- o sistema ainda preserva estados legados de acompanhante
- para o fluxo atual de crianças, o convidado fica como `confirmed` com `companions_count > 0`

### Tipos de intenção interpretada

Arquivo: [app/Enums/ParsedIntent.php](/private/var/www/convite-app/app/Enums/ParsedIntent.php)

- `confirmed`
- `declined`
- `undecided`
- `with_children`
- `waiting_companion_count`
- `companion_count`
- `unknown`
- `ignored`

## Regras de negócio do RSVP

Implementação principal:

- [app/Services/WhatsappResponseParser.php](/private/var/www/convite-app/app/Services/WhatsappResponseParser.php)
- [app/Services/GuestStateMachine.php](/private/var/www/convite-app/app/Services/GuestStateMachine.php)
- [app/Jobs/ProcessIncomingWhatsappMessageJob.php](/private/var/www/convite-app/app/Jobs/ProcessIncomingWhatsappMessageJob.php)

### Botões enviados hoje

- `Vou`
- `Não vou`
- `Ainda não sei`
- `Vou com crianças`

### Resultado de cada botão

- `Vou`
  - status `confirmed`
- `Não vou`
  - status `declined`
- `Ainda não sei`
  - status `undecided`
  - agenda lembrete
- `Vou com crianças`
  - status `confirmed`
  - `companions_count = 1`
  - coluna `Com crianças = Sim`

### Respostas automáticas

- `confirmed`
  - `Presença confirmada.`
- `declined`
  - `Tudo bem. Obrigado por responder.`
- `undecided`
  - `Vou te lembrar novamente 15 dias antes do evento.`
- `with_children`
  - `Presença confirmada com crianças.`

## Fluxo de envio

### 1. Criação do dispatch

Quando o usuário clica em `Fila de envio`, a rota:

- `POST /events/{event}/invitations/send`

executa [EventInvitationController.php](/private/var/www/convite-app/app/Http/Controllers/Admin/EventInvitationController.php).

O controller:

- seleciona convidados com status:
  - `pending`
  - `undecided`
- cria um `invitation_dispatch` por convidado
- despacha `SendInvitationJob`

### 2. Execução do job

Arquivo: [app/Jobs/SendInvitationJob.php](/private/var/www/convite-app/app/Jobs/SendInvitationJob.php)

O job:

- carrega evento e convidado
- monta a mensagem com `InvitationMessageBuilder`
- decide o tipo de envio:
  - texto com botões
  - imagem + botões
  - PDF + botões
- salva:
  - `provider_message_id`
  - `provider_zaap_id`
  - `delivery_status`
  - `sent_at`
  - payload do provider

### 3. Proteção contra envios antigos

O job possui proteção para:

- ignorar dispatch antigo quando já existe um dispatch mais novo do mesmo tipo para o mesmo convidado
- preferir sempre o asset atual do evento no momento do envio

Isso evita:

- reutilização de URL antiga
- envio de convite desatualizado após troca de imagem/PDF/texto

## Fluxo de recebimento

### Endpoint de webhook

- `POST /api/webhooks/zapi/messages`
- `POST /api/webhooks/zapi/message-status`

Arquivo: [app/Http/Controllers/Webhooks/ZApiWebhookController.php](/private/var/www/convite-app/app/Http/Controllers/Webhooks/ZApiWebhookController.php)

### Recebimento de mensagem

Ao receber webhook:

1. o payload é salvo em `webhook_receipts`
2. o sistema dispara `ProcessIncomingWhatsappMessageJob`
3. o job processa o conteúdo
4. a resposta é salva em `guest_responses`
5. o status do convidado é atualizado
6. se necessário, o sistema envia resposta automática

### Identificação do evento correto

O sistema usa `buttonId` contextualizado, no formato:

```text
evt:{event_id}:guest:{guest_id}:{action}
```

Exemplos:

- `evt:15:guest:88:confirmed`
- `evt:15:guest:88:declined`
- `evt:15:guest:88:undecided`
- `evt:15:guest:88:with_children`

Com isso, mesmo que o mesmo telefone exista em eventos diferentes, a resposta por botão é vinculada ao convidado e evento corretos.

## Lembretes

### Agendamento

Arquivo: [app/Services/ReminderScheduler.php](/private/var/www/convite-app/app/Services/ReminderScheduler.php)

Quando o convidado responde `Ainda não sei`:

- o sistema agenda um registro em `reminder_schedules`
- a data usa a regra `event_date - reminder_days_before`

### Disparo

Arquivos:

- [app/Console/Kernel.php](/private/var/www/convite-app/app/Console/Kernel.php)
- [app/Console/Commands/DispatchDueReminders.php](/private/var/www/convite-app/app/Console/Commands/DispatchDueReminders.php)
- [app/Jobs/SendReminderJob.php](/private/var/www/convite-app/app/Jobs/SendReminderJob.php)

O scheduler roda:

- `reminders:dispatch-due` a cada minuto

O comando:

- busca lembretes pendentes vencidos
- cria dispatch de lembrete
- reaproveita `SendInvitationJob`

## Integração com a Z-API

### Configuração

A configuração pode vir de:

- tela administrativa `/settings/zapi`
- `.env`

Implementação:

- [app/Services/ZApiSettingsService.php](/private/var/www/convite-app/app/Services/ZApiSettingsService.php)

### Campos usados

- `INSTANCE_ID`
- `INSTANCE_TOKEN`
- `CLIENT_TOKEN` opcional, dependendo da instância

### Gateway

Arquivo: [app/Integrations/ZApi/ZApiWhatsappGateway.php](/private/var/www/convite-app/app/Integrations/ZApi/ZApiWhatsappGateway.php)

Métodos implementados:

- `sendText`
- `sendButtonList`
- `sendImage`
- `sendDocument`

### Teste de conexão

A tela de configurações permite:

- salvar credenciais
- testar conexão com a instância

## Rotas principais

### Web

Arquivo: [routes/web.php](/private/var/www/convite-app/routes/web.php)

Rotas principais:

- `GET /dashboard`
- `GET /events`
- `POST /events`
- `PATCH|POST /events/{event}`
- `GET /events/{event}`
- `GET /events/{event}/queue`
- `POST /events/{event}/guests`
- `PATCH /events/{event}/guests/{guest}`
- `DELETE /events/{event}/guests/{guest}`
- `POST /events/{event}/guests/import`
- `POST /events/{event}/invitations/send`
- `POST /events/{event}/reminders/send`
- `GET /settings/zapi`
- `PUT /settings/zapi`
- `POST /settings/zapi/test`

### API

Arquivo: [routes/api.php](/private/var/www/convite-app/routes/api.php)

- `POST /api/webhooks/zapi/messages`
- `POST /api/webhooks/zapi/message-status`

## Execução local

### 1. Dependências

```bash
composer install
npm install
```

### 2. Ambiente

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Banco

Exemplo atual:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_convite_app
DB_USERNAME=root
DB_PASSWORD=
```

Criar as tabelas:

```bash
php artisan migrate
```

Recriar tudo do zero:

```bash
php artisan migrate:fresh
```

### 4. Storage público

```bash
php artisan storage:link
```

### 5. Frontend e backend

```bash
php artisan serve
npm run dev
```

### 6. Fila

Como o webhook usa a fila `high`, o worker recomendado localmente é:

```bash
php artisan queue:work --queue=high,default
```

### 7. Scheduler

```bash
php artisan schedule:work
```

## Comandos úteis

### Banco

```bash
php artisan migrate
php artisan migrate:fresh
php artisan migrate:fresh --seed
```

### Queue

```bash
php artisan queue:work --queue=high,default
php artisan queue:restart
```

### Scheduler

```bash
php artisan schedule:work
php artisan reminders:dispatch-due
```

### Cache e config

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Assets

```bash
php artisan storage:link
```

## URL pública para arquivos

Convites com imagem e PDF dependem de URL pública acessível externamente pela Z-API.

Por isso:

- `APP_URL` deve apontar para um host público válido
- o link `public/storage` precisa existir
- o domínio precisa responder externamente

Exemplo:

```env
APP_URL=https://math-votes-ground-products.trycloudflare.com
```

O sistema já normaliza URLs antigas de `/storage/...` para o domínio atual do `APP_URL`, evitando que imagens antigas continuem aparecendo com um host antigo.

## Testes

Principais grupos de teste já existentes:

- `EventManagementTest`
- `EventQueueTest`
- `GuestManagementTest`
- `SendInvitationButtonsTest`
- `ProcessIncomingWebhookContextTest`
- `WhatsappResponseParserTest`
- `GuestStateMachineTest`
- `ZApiConnectionTest`
- `ZApiSettingsTest`

Executar tudo:

```bash
php artisan test
```

Executar grupos específicos:

```bash
php artisan test --filter=SendInvitationButtonsTest
php artisan test --filter='WhatsappResponseParserTest|GuestStateMachineTest'
```

## Regras e decisões importantes

- toda integração externa relevante roda em fila
- o webhook responde rápido e delega processamento
- a identificação correta da resposta por evento usa `buttonId`
- o sistema mantém histórico de envios e respostas
- convidados só entram na fila de convite se estiverem:
  - `pending`
  - `undecided`
- URLs de assets são normalizadas para o domínio atual
- dispatches antigos podem ser descartados quando um envio mais novo já existir

## Limitações atuais

- ainda existem estados legados de acompanhante no domínio
- respostas por texto livre são menos seguras que respostas por botão quando o mesmo telefone aparece em mais de um evento
- a Z-API pode exigir `Client-Token` em alguns cenários de status/teste
- a build ainda mostra avisos antigos de assets do tema Sakai em `resources/css/layout.css`

## Checklist de operação

Para o sistema funcionar em ambiente local ou servidor:

1. configurar `.env`
2. rodar `php artisan migrate`
3. rodar `php artisan storage:link`
4. rodar `php artisan serve`
5. rodar `npm run dev`
6. rodar `php artisan queue:work --queue=high,default`
7. rodar `php artisan schedule:work`
8. configurar a Z-API na tela `/settings/zapi`
9. expor uma URL pública válida se for usar imagem ou PDF
10. cadastrar o webhook:
    - `/api/webhooks/zapi/messages`
    - `/api/webhooks/zapi/message-status`

## Arquivos de referência

- [routes/web.php](/private/var/www/convite-app/routes/web.php)
- [routes/api.php](/private/var/www/convite-app/routes/api.php)
- [app/Http/Controllers/Admin/EventController.php](/private/var/www/convite-app/app/Http/Controllers/Admin/EventController.php)
- [app/Http/Controllers/Admin/EventInvitationController.php](/private/var/www/convite-app/app/Http/Controllers/Admin/EventInvitationController.php)
- [app/Http/Controllers/Webhooks/ZApiWebhookController.php](/private/var/www/convite-app/app/Http/Controllers/Webhooks/ZApiWebhookController.php)
- [app/Jobs/SendInvitationJob.php](/private/var/www/convite-app/app/Jobs/SendInvitationJob.php)
- [app/Jobs/ProcessIncomingWhatsappMessageJob.php](/private/var/www/convite-app/app/Jobs/ProcessIncomingWhatsappMessageJob.php)
- [app/Jobs/SendReminderJob.php](/private/var/www/convite-app/app/Jobs/SendReminderJob.php)
- [app/Services/WhatsappResponseParser.php](/private/var/www/convite-app/app/Services/WhatsappResponseParser.php)
- [app/Services/GuestStateMachine.php](/private/var/www/convite-app/app/Services/GuestStateMachine.php)
- [app/Integrations/ZApi/ZApiWhatsappGateway.php](/private/var/www/convite-app/app/Integrations/ZApi/ZApiWhatsappGateway.php)
- [resources/js/Pages/Events/Index.jsx](/private/var/www/convite-app/resources/js/Pages/Events/Index.jsx)
- [resources/js/Pages/Events/Show.jsx](/private/var/www/convite-app/resources/js/Pages/Events/Show.jsx)
- [resources/js/Pages/Events/Queue.jsx](/private/var/www/convite-app/resources/js/Pages/Events/Queue.jsx)
- [resources/js/Pages/Dashboard.jsx](/private/var/www/convite-app/resources/js/Pages/Dashboard.jsx)
