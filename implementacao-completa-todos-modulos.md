---
title: "Implementation Prompt — Painel Admin Completo (Todos os Módulos) — MTD STORE"
status: "Pronto para execução, por fases"
versão: "1.0"
base: "extracao-completa-painel-referencia.md + proposta-escopo-admin-para-cliente.md"
---

# IMPLEMENTAÇÃO COMPLETA — TODOS OS MÓDULOS NOVOS

## Princípios que se aplicam a TODO módulo abaixo (não repetir por seção)

> [!WARNING]
> Estes princípios já foram estabelecidos e validados ao longo do projeto — qualquer novo código precisa segui-los, sem exceção:
> 1. **Nunca texto livre onde existe conjunto finito de opções.** Sempre `Select`/`Toggle`/`Radio`.
> 2. **Nunca duplicar regra de negócio entre Filament e API.** Toda lógica fica em `Services`, tanto o painel quanto a API pública chamam o mesmo Service.
> 3. **Dado sensível nunca aparece em texto puro sem ação explícita + Gate + log de auditoria** (padrão já implementado em `ProductStockItemResource`/`OrderForm` — reaproveitar o mesmo componente, não duplicar).
> 4. **Toda Gate de permissão precisa de dupla validação**: `->visible()` para esconder o botão (UX) E `abort_unless()`/checagem dentro do `->action()` (segurança real) — nunca confiar só na visibilidade.
> 5. **Toda tabela nova precisa de eager loading** nos relacionamentos exibidos, e widgets/KPIs agregados precisam de cache curto (2-5 min), seguindo o padrão já aplicado no Dashboard.
> 6. **Toda ação que dispara e-mail, webhook ou processamento pesado vai para Job/fila**, nunca síncrono no request.

---

## FASE 1 — Modelo de dados: campos e tabelas novas

Antes de qualquer tela, os seguintes ajustes de schema são pré-requisito:

### 1.1 `orders.status` — adicionar `chargeback` e `partially_refunded`

```php
// Migration
$table->enum('status', [
    'pending', 'awaiting_payment', 'paid', 'failed',
    'refunded', 'partially_refunded', 'chargeback'
])->default('pending')->change();

$table->decimal('refunded_amount', 10, 2)->default(0)->after('total');
```
Isso resolve o Gap 2 identificado na análise lógica anterior (reembolso parcial) e incorpora `chargeback` como estado de primeira classe, confirmado como relevante pela referência (Estatísticas → Operações → % chargeback; Webhooks → evento "Pedido com chargeback").

### 1.2 Nova tabela: `posts` (Blog)

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('author');
    $table->longText('content'); // rich text
    $table->string('cover_image')->nullable();
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->timestamps();
});
```

### 1.3 Nova tabela: `reviews` (Avaliações)

```php
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('customer_id')->nullable()->constrained('users');
    $table->unsignedTinyInteger('rating'); // 1-5
    $table->text('comment')->nullable();
    $table->enum('status', ['private', 'published'])->default('private');
    $table->timestamps();
});

Schema::create('review_settings', function (Blueprint $table) {
    $table->id();
    $table->boolean('enabled')->default(true);
    $table->boolean('auto_publish')->default(false);
    $table->json('suggested_phrases')->default('[]');
    $table->timestamps();
});
```

### 1.4 Nova tabela: `store_settings` (Configurações — chave/valor, ou colunas fixas)

```php
Schema::create('store_settings', function (Blueprint $table) {
    $table->id();
    // Geral
    $table->string('store_name');
    $table->text('description')->nullable();
    $table->string('cnpj')->nullable();
    $table->boolean('maintenance_mode')->default(false);
    $table->boolean('require_login')->default(false);
    // Contatos
    $table->string('contact_email')->nullable();
    $table->json('social_links')->default('{}'); // {whatsapp, instagram, discord, ...}
    $table->json('business_hours')->nullable(); // por dia da semana
    $table->boolean('show_business_hours')->default(false);
    // Tema
    $table->string('logo_path')->nullable();
    $table->string('favicon_path')->nullable();
    $table->string('primary_color', 7)->default('#dc2626');
    $table->string('secondary_color', 7)->default('#991b1b');
    $table->timestamps();
});
```
Tabela única de configuração (linha única, tipo singleton) — mais simples de gerenciar que key-value genérico para este volume de campos.

### 1.5 Nova tabela: `webhooks` + `webhook_logs` (saída — notificar sistemas externos)

```php
Schema::create('webhooks', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('url'); // sempre HTTPS, validar no Form Request
    $table->json('events'); // ['order.created', 'order.chargeback', ...]
    $table->string('secret')->nullable(); // para assinar o payload de saída (HMAC)
    $table->boolean('active')->default(true);
    $table->timestamps();
});

Schema::create('webhook_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
    $table->string('event');
    $table->json('payload');
    $table->unsignedSmallInteger('response_status')->nullable();
    $table->text('response_body')->nullable();
    $table->timestamps();
});
```

### 1.6 Afiliados — saques como fluxo próprio

```php
Schema::create('affiliate_withdrawals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('affiliate_id')->constrained();
    $table->decimal('amount', 10, 2);
    $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
    $table->text('admin_notes')->nullable();
    $table->timestamps();
});

// affiliates: adicionar campos que faltam
$table->decimal('min_withdrawal', 10, 2)->default(0);
$table->unsignedSmallInteger('cookie_duration_days')->default(30);
```

### 1.7 Clientes — ban

```php
// users (ou tabela de customers, conforme já modelado)
$table->timestamp('banned_at')->nullable();
$table->text('ban_reason')->nullable();
```
Middleware de autenticação precisa checar `banned_at` e recusar login/ação se preenchido.

### 1.8 UTM tracking (opcional, avaliar prioridade)

```php
Schema::create('visits', function (Blueprint $table) {
    $table->id();
    $table->string('utm_source')->nullable();
    $table->string('utm_medium')->nullable();
    $table->string('utm_campaign')->nullable();
    $table->string('session_id');
    $table->foreignId('customer_id')->nullable()->constrained('users');
    $table->timestamp('visited_at');
});
```
Isso é rastreamento de marketing — maior escopo que o resto, avaliar se entra nesta rodada ou fica para depois (não é bloqueante para nenhum outro módulo).

---

## FASE 2 — Produtos: variações, instruções, entrega por arquivo

### 2.1 Variações de produto

```php
Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('name'); // ex: "1 mês", "3 meses", "Vitalício"
    $table->decimal('price', 10, 2);
    $table->timestamps();
});
```
`product_stock_items` ganha `variant_id` nullable — o pool de chaves passa a ser por variante quando o produto tiver variações, por produto quando não tiver.

### 2.2 Aba "Instruções"

Campo rich text em `products` (`post_purchase_instructions`) — exibido na tela de sucesso/e-mail de entrega junto com a chave.

### 2.3 Aba "Arquivo" (entrega por download em vez de chave de texto)

`products.delivery_type`: enum `['unique_key', 'file_download']`. Se `file_download`, `product_stock_items.value` guarda o path do arquivo no storage S3-compatível (já previsto no ADR) em vez de uma chave de texto — o fluxo de entrega (signed URL) já desenhado continua o mesmo, só muda o que está por trás do `value`.

### 2.4 UI de criação de produto

- Dropdown "Novo pacote" → **Pacote padrão** (fluxo atual) vs. **Pacote com variações** (abre tela de variações após salvar).
- Formulário principal: nome, categoria (select), editor rich text, upload de imagem, `Select` de visibilidade.

---

## FASE 3 — Categorias: reordenação

Adicionar `order` (integer) em `categories`, e usar o componente de reordenação nativo do Filament (`->reorderable('order')`) na listagem em grid.

---

## FASE 4 — Postagens (Blog)

`PostResource` (Filament) simples: listagem (Título/Autor/Data), criação com editor rich text + upload de capa, `Select` de status (Rascunho/Publicado). CRUD direto, sem complexidade adicional.

---

## FASE 5 — Cupons: campos avançados

Adicionar aos cupons já existentes:

- `min_purchase_amount` (decimal, nullable).
- `usage_limit` (integer, nullable) + no formulário: `Toggle` "Ilimitado" que desabilita/limpa o campo numérico quando ativo.
- `expires_at` (date, nullable) + `Toggle` "Nunca expira", mesmo padrão do item anterior.
- `allowed_payment_methods` (json array, nullable) — `Select` múltiplo.
- Tabela pivot `coupon_product` e `coupon_category` para restringir por produto/categoria específico — árvore de checkbox com "Selecionar todos" (usar `CheckboxList` do Filament com estrutura agrupada, ou um componente customizado se o agrupamento visual exigir).
- Tabela pivot `coupon_allowed_user` — campo de identificador + botão adicionar (lista de usuários exclusivos).

**Lembrete do Gap 1 da análise lógica anterior**: o cupom só deve ser marcado como consumido de fato quando o pedido chegar a `paid`, nunca no momento da aplicação no carrinho — implementar isso junto, já que estamos mexendo na lógica de cupom de qualquer forma.

---

## FASE 6 — Avaliações

`ReviewResource`: listagem com filtro por status (Privada/Publicada), ação de aprovar/publicar manualmente, KPIs (nota média, total, publicadas, privadas) via widget cacheado.

`ReviewSettingsPage` (página de configuração única): toggle "Ativar avaliações", toggle "Publicação automática", `Repeater`/`TagsInput` para as frases sugeridas.

Fluxo de coleta: e-mail pós-entrega (reaproveitar o Job de e-mail de entrega já existente, ou um novo Job agendado X dias depois) com link para avaliar — vincula `order_id` + `product_id`, evita avaliação duplicada do mesmo pedido.

---

## FASE 7 — Estatísticas avançadas

Página `StatisticsPage` separada do Dashboard (o Dashboard fica enxuto/rápido, Estatísticas é a tela pesada de análise — separar evita que a lentidão de relatório complexo afete a tela mais acessada).

Widgets (todos com cache 2-5 min, dado o volume de agregação):

- `SalesNetChartWidget` — vendas líquidas no período.
- `PaymentGatewayDonutWidget` — % e R$ por gateway (query em `orders.gateway` ou equivalente, agrupado).
- `ApprovalChargebackWidget` — % aprovação vs. % chargeback (usa o novo status `chargeback` da Fase 1).
- `RevenueByYearChartWidget`.
- `TopCategoriesWidget`, `TopCustomersWidget`.
- `AverageTicketWidget`.
- `CustomerRecurrenceWidget` — novos vs. recorrentes (baseado em contagem de pedidos por cliente).
- `UtmSourceWidget` — só se a Fase 1.8 (tabela `visits`) for implementada nesta rodada.

Botão "Exportar pedidos" — gerar CSV/XLSX via Job em fila (não sincronamente, se o volume puder ser grande) e notificar quando pronto (ou disponibilizar link de download temporário, mesmo padrão de signed URL já usado na entrega de produto).

---

## FASE 8 — Afiliados: saques e configuração avançada

`AffiliateWithdrawalResource`: listagem de solicitações de saque com `Select` de status (Pendente/Aprovado/Rejeitado/Pago), campo de notas do admin, ação de aprovar/rejeitar.

Endpoint público (API) para o afiliado solicitar saque — valida contra `min_withdrawal` e saldo disponível via `AffiliateService` (nunca lógica duplicada entre a API pública e o Filament).

Formulário de criação de afiliado: adicionar `Saque mínimo`, `Duração do cookie (dias)`, botão "Gerar" código automático, seção colapsável "Configurações avançadas" (`Section::make()->collapsible()` do Filament).

---

## FASE 9 — Clientes: ban

Ação "Banir cliente" no `CustomerResource` (ou `UserResource` filtrado), com modal pedindo motivo (`ban_reason`), grava `banned_at`. Middleware de autenticação da API pública passa a checar `banned_at` e retornar 403 com código de erro específico (`ACCOUNT_BANNED`, seguindo o contrato de erro já padronizado no ADR) em vez de mensagem genérica.

---

## FASE 10 — Configurações da loja (módulo novo completo)

`SettingsPage` (Filament Page customizada, não Resource — é configuração singleton) com abas replicando a referência:

- **Geral**: nome, descrição (rich text), CNPJ, toggles de manutenção/login obrigatório.
- **Contatos**: e-mail, campos de rede social (um `TextInput` por rede, ícone via `prefixIcon`), horário de atendimento (`Repeater` por dia da semana com toggle de "fechado").
- **Pagamentos**: reaproveita a estrutura de gateways já prevista na `PaymentGatewayInterface` — cada card lê o status `active` de cada gateway configurado, toggle liga/desliga, "Configurar" abre modal com credenciais específicas daquele gateway (criptografadas em repouso, mesmo padrão de segurança já usado para chaves de estoque).
- **Tema**: upload de logo/favicon, color picker para cor primária/secundária, preview ao vivo (iframe ou mock estático atualizado via Livewire reativo).
- **Webhooks**: `WebhookResource` (CRUD) + aba de Logs (`webhook_logs`, somente leitura). Disparo reaproveita o mesmo padrão de Job assíncrono já usado para revalidação de cache — um `DispatchWebhookJob` genérico que roda toda vez que um Observer relevante (Order, Product) detectar mudança de estado, verifica quais webhooks cadastrados escutam aquele evento, e envia com assinatura HMAC no payload.
- **Integração via API**: página informativa/geração de API key para o lojista (Sanctum token com escopo restrito, se aplicável) — menor prioridade, pode ficar para depois.
- **Domínio**: já coberto conceitualmente pela documentação de deploy/portabilidade do ADR — aqui seria só a interface para configurar o domínio customizado apontando para o Render/Vercel.

---

## ORDEM DE EXECUÇÃO RECOMENDADA

1. **Fase 1** (schema) — bloqueante para tudo, fazer primeiro e por completo.
2. **Fase 5** (cupons avançados) — maior valor de negócio imediato, e já resolve o Gap 1 pendente da análise lógica.
3. **Fase 2** (produtos/variações/instruções/arquivo) — segunda maior prioridade, afeta catálogo real.
4. **Fase 10** (configurações) — não depende de dado de venda, pode rodar em paralelo às fases 2/5.
5. **Fase 7** (estatísticas avançadas) — depende do novo status `chargeback` da Fase 1, fazer depois dela.
6. **Fase 8 e 9** (afiliados/clientes) — fluxo próprio, sem dependência forte das anteriores.
7. **Fase 3, 4 e 6** (categorias/postagens/avaliações) — menor complexidade e menor urgência de negócio, ficam por último.

## FORA DE ESCOPO (confirmado pelo cliente)

Discord (automação de cargo, conectar bot), Música, Perguntas, Sorteio, Moderação — nenhum desses entra em nenhuma fase.
