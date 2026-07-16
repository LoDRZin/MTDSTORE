---
title: "MTD STORE — Arquitetura e Modelagem Técnica"
status: "ADR aprovado"
versão: "1.0"
última atualização: "2026-07-15"
---

# MTD STORE — Documento de Arquitetura

## Sumário

**Parte I — Arquitetura**
1. [Responsabilidade de cada tecnologia](#1-responsabilidade-de-cada-tecnologia)
2. [Fronteira de responsabilidade (resumo)](#2-fronteira-de-responsabilidade-resumo)
3. [Integrações de pagamento](#3-integrações-de-pagamento-mantendo-e-expandindo-as-atuais)
4. [Fluxo de invalidação de cache](#4-fluxo-de-invalidação-de-cache-nextjs-isr)
5. [Fluxo de checkout com resiliência](#5-fluxo-de-checkout-com-resiliência-a-falha-de-gateway)
6. [Estrutura de pastas (Laravel)](#6-estrutura-de-pastas-laravel)
7. [Deploy e portabilidade](#7-deploy-e-portabilidade)
8. [Checklist de segurança](#8-checklist-de-segurança-não-negociável)
9. [Testes e Observabilidade](#9-testes-e-observabilidade)
10. [Contrato de erro da API](#10-contrato-de-erro-da-api)

**Parte II — Modelagem de Dados (Catálogo Digital)**
11. [Premissas do catálogo](#11-premissas-do-catálogo)
12. [Modelo de dados](#12-modelo-de-dados)
13. [Fluxo de estoque (admin adiciona chaves em lote)](#13-fluxo-de-estoque-admin-adiciona-chaves-em-lote)
14. [Fluxo de alocação de chave no checkout](#14-fluxo-de-alocação-de-chave-no-checkout-evitando-venda-duplicada)
15. [Migrations (Laravel)](#15-migrations-laravel)
16. [Recurso Filament (bulk add)](#16-recurso-filament-bulk-add)
17. [Segurança do dado de estoque](#17-segurança-do-dado-de-estoque)

**Parte III — Entrega Automática**
18. [Visão geral do fluxo de entrega](#18-visão-geral-do-fluxo-de-entrega)
19. [Passo a passo da entrega](#19-passo-a-passo-da-entrega)
20. [Página de sucesso (signed URL)](#20-página-de-sucesso-signed-url)
21. [E-mail de entrega](#21-e-mail-de-entrega)
22. [Reentrega (cliente perdeu a chave)](#22-reentrega-cliente-perdeu-a-chave)
23. [Checklist de segurança da entrega](#23-checklist-de-segurança-da-entrega)

**Parte IV — Roadmap**
24. [Próximos passos](#24-próximos-passos)

---

## Visão Geral

Reconstrução completa do e-commerce da **MTD STORE**, substituindo a stack monolítica legada (Node/Express + SQLite + túnel local) por uma **arquitetura híbrida desacoplada**: rápida na vitrine, segura no núcleo, e portátil entre provedores de hospedagem (Square Cloud, VPS genérica, Railway, etc.), sem lock-in de infraestrutura. Catálogo **100% digital** — chaves, licenças e downloads — com estoque modelado como pool de itens únicos.

```text
┌─────────────────────┐        HTTPS/JSON         ┌──────────────────────────┐
│   Next.js (Vitrine)  │ ─────────────────────────▶│   Laravel API (Motor)    │
│   SSR / ISR / Edge   │ ◀───────────────────────── │   Sanctum + Services     │
└─────────────────────┘        Webhook revalidate   └──────────────────────────┘
                                                              │
                                                              │ Eloquent
                                                              ▼
                                                     ┌──────────────────────────┐
                                                     │   Filament (Admin)       │
                                                     │   Consome Services       │
                                                     │   diretamente            │
                                                     └──────────────────────────┘
                                                              │
                              ┌───────────────────────────────┼───────────────────────────────┐
                              ▼                                ▼                               ▼
                     PostgreSQL/MySQL                  Redis (cache/queue)          Storage (S3-compatible)
```

---

# Parte I — Arquitetura

## 1. Responsabilidade de cada tecnologia

### 1.1 Next.js (Vitrine)

**Faz:**
- Renderização de catálogo, PDP (página de produto), busca e landing pages via SSR/ISR/Static, priorizando Core Web Vitals.
- Gerencia estado de UI (carrinho lateral, modais, filtros) no client.
- Consome exclusivamente a API REST/GraphQL do Laravel via `fetch`, autenticado via token Sanctum quando necessário.
- Expõe um endpoint interno `/api/revalidate` para receber webhooks de invalidação de cache.

**Não faz (proibido por design):**
- Não acessa banco de dados diretamente.
- Não calcula frete, cupom, imposto ou qualquer regra de negócio.
- Não processa nem armazena dado de cartão — delega 100% ao Laravel/gateway.

### 1.2 Laravel (API / Motor)

**Faz:**
- É a fonte da verdade: valida toda requisição (Form Requests), aplica regras de negócio via camada de **Services** (`CheckoutService`, `CartService`, `InventoryService`, `CouponService`).
- Gerencia autenticação via [Sanctum](https://laravel.com/docs/sanctum) (tokens para SPA/mobile).
- Processa pagamento através de uma camada de abstração de gateway (ver Seção 3).
- Dispara e recebe webhooks (pagamento aprovado, estoque baixo, produto atualizado).
- Expõe API versionada (`/api/v1/...`) usando **API Resources** para padronizar payloads.
- Padroniza respostas de erro em formato estruturado (ver [Seção 10](#10-contrato-de-erro-da-api)), para o Next.js tratar exceções de forma previsível na UI.

**Não faz:**
- Não duplica regra de negócio para atender API e Filament separadamente — ambos consomem os mesmos Services.

### 1.3 Filament (Admin)

**Faz:**
- CRUD de produtos, estoque de chaves, pedidos, cupons, afiliados, clientes.
- Consome diretamente Models e Services do Laravel (sem passar pela camada REST), o que o torna rápido para o time interno.
- RBAC via `spatie/laravel-permission` + [Filament Shield](https://filamentphp.com/plugins/bezhansalleh-shield).
- Dashboard operacional (vendas, estoque, gráficos).

**Regra crítica:** toda validação de segurança (quem pode alterar preço, limite de desconto, permissão por papel) precisa estar nas **Policies/Services**, nunca só na Form Request da API — senão o Filament passa por trás da validação.

### 1.4 Banco de Dados — PostgreSQL ou MySQL

- Substitui o SQLite do sistema legado. Suporta concorrência real, replicação e backup gerenciado.
- Dados sensíveis (chaves de produto digital, tokens) armazenados **criptografados em repouso** (`Crypt::encrypted` cast do Laravel), nunca em texto puro.

### 1.5 Redis

- Cache de sessão e de query, incluindo o contador de estoque disponível (ver [Seção 13](#13-fluxo-de-estoque-admin-adiciona-chaves-em-lote)).
- Fila (Queue) para jobs assíncronos: envio de e-mail, entrega de produto digital, webhook de revalidação, processamento de webhook de gateway de pagamento.
- Rate limiting de login/checkout (`RateLimiter` do Laravel).

### 1.6 Storage (S3-compatível)

- Uploads de imagem de produto e arquivos digitais via `Storage` do Laravel, com driver S3-compatível (AWS S3, Cloudflare R2, Backblaze, ou storage local em dev) — evita lock-in de provedor.

---

## 2. Fronteira de responsabilidade (resumo)

| Camada | Fala com banco? | Calcula regra de negócio? | Processa pagamento? | Consome |
|---|---|---|---|---|
| Next.js | Não | Não | Não | API Laravel (HTTP) |
| Laravel API | Sim | Sim | Sim (via gateway abstrato) | Services internos |
| Filament | Sim (via Eloquent) | Não (delega a Services) | Não | Services internos |

---

## 3. Integrações de pagamento (mantendo e expandindo as atuais)

O sistema legado já usa **Stripe**, **Mercado Pago** e **Efi (Gerencianet)**. Para manter isso e abrir portas para novos gateways sem reescrever o Checkout, usar o padrão **Strategy/Interface**:

```php
interface PaymentGatewayInterface
{
    public function createCharge(Order $order): PaymentIntentDTO;
    public function verifyWebhookSignature(Request $request): bool;
    public function handleWebhook(Request $request): void;
    public function refund(Order $order): bool;
}
```

Cada gateway (`StripeGateway`, `MercadoPagoGateway`, `EfiGateway`) implementa essa interface. O `CheckoutService` nunca fala diretamente com SDK de gateway — sempre através da interface, resolvida via um `PaymentGatewayFactory` baseado no método escolhido pelo cliente.

**Para adicionar um novo gateway no futuro** (PayPal, PagSeguro, Adyen, etc.): basta implementar a interface e registrar no factory — zero mudança no fluxo de checkout ou no Next.js.

> [!WARNING]
> **Regras não-negociáveis para todos os gateways**
> - **Verificação de assinatura obrigatória** em todo webhook recebido (`verifyWebhookSignature`) — nunca confiar em payload cru.
> - Processamento de webhook de pagamento roda em **fila (Queue)**, nunca de forma síncrona no request do gateway (evita timeout e permite retry).
> - Idempotência: cada evento de webhook grava um `external_event_id` único — reprocessamento do mesmo evento é ignorado.

---

## 4. Fluxo de invalidação de cache (Next.js ISR)

1. Gerente altera preço/estoque no Filament → salva.
2. **Eloquent Observer** no Model `Product` detecta a mudança.
3. Observer despacha um **Job** para a fila Redis (não bloqueia o admin).
4. Worker processa o Job e faz `POST` assinado (HMAC + timestamp, não só token na URL) para `/api/revalidate` no Next.js.
5. Next.js valida a assinatura e executa `revalidatePath`/`revalidateTag`.
6. Página é regenerada em background; próximo visitante já vê o dado atualizado.

---

## 5. Fluxo de checkout com resiliência a falha de gateway

1. Cliente inicia checkout → Next.js chama `POST /api/v1/checkout` no Laravel.
2. Laravel cria o pedido em estado `pending` (transação de banco garante atomicidade do carrinho → pedido).
3. `CheckoutService` chama o gateway resolvido via `PaymentGatewayFactory`.
4. Se o gateway responder a tempo: pedido segue para `awaiting_payment` com o `external_reference` salvo.
5. Se o gateway **não responder / cair**: pedido permanece `pending`, cliente recebe erro amigável, um Job de retry com backoff é agendado (não perde o pedido).
6. Confirmação real de pagamento **só acontece via webhook assinado** do gateway, nunca via redirect do navegador do cliente (redirect pode ser manipulado ou falhar antes de confirmar).
7. Webhook aprovado → Job dispara: alocação da chave em estoque (ver [Seção 14](#14-fluxo-de-alocação-de-chave-no-checkout-evitando-venda-duplicada)), entrega automática (Parte III), e revalidação de cache se o estoque zerar.

---

## 6. Estrutura de pastas (Laravel)

```text
app/
  Models/
  Services/
    CheckoutService.php
    CartService.php
    InventoryService.php
    CouponService.php
  Gateways/
    PaymentGatewayInterface.php
    StripeGateway.php
    MercadoPagoGateway.php
    EfiGateway.php
    PaymentGatewayFactory.php
  Http/
    Controllers/Api/V1/
    Resources/
    Requests/
  Observers/
    ProductObserver.php
    ProductStockObserver.php
  Jobs/
    RevalidateStorefrontCache.php
    ProcessPaymentWebhook.php
    DeliverDigitalProduct.php
    SendDeliveryEmail.php
  Policies/
Filament/
  Resources/
  Widgets/
```

---

## 7. Deploy e portabilidade

O objetivo é rodar em **qualquer provedor** (Square Cloud, VPS, Railway, Render, etc.) sem reescrever infraestrutura:

- **Containerização via Docker**: um `Dockerfile` para o Laravel (PHP-FPM + Nginx ou Octane), um para o Next.js (`node` runtime), `docker-compose.yml` para orquestrar localmente (app, Redis, banco).
- **Variáveis de ambiente** (`.env`) para tudo que muda entre provedores: banco, Redis, chaves de gateway, bucket S3, domínio do Next.js — nenhuma credencial hardcoded (ponto de falha do sistema legado).
- **Storage S3-compatível** evita lock-in: troca de provedor de arquivo só muda variável de ambiente.
- **Filas** rodam via worker separado (`php artisan queue:work`), podendo escalar independente do processo web.
- **Migrations versionadas** (`php artisan migrate`) garantem que o schema seja reproduzível em qualquer ambiente novo.

---

## 8. Checklist de segurança (não-negociável)

- [ ] Todo webhook (pagamento e revalidação) valida assinatura antes de processar.
- [ ] Dados sensíveis (chaves digitais, tokens) criptografados em repouso.
- [ ] Rate limiting em login, checkout, busca e endpoints de reentrega/lookup.
- [ ] CSRF ativo em rotas web do Filament; Sanctum + CORS explícito na API pública.
- [ ] Nenhuma lógica de validação de negócio duplicada — tudo centralizado em Services/Policies.
- [ ] Backups automáticos do banco, fora da máquina de aplicação.
- [ ] Logs de auditoria no Filament (quem alterou o quê e quando).
- [ ] LGPD: política de retenção de dados pessoais e endpoint de exclusão/exportação de dados do cliente.

---

## 9. Testes e Observabilidade

### 9.1 Testes

- **Laravel**: [Pest](https://pestphp.com/) (ou PHPUnit) com foco em:
  - Testes unitários dos **Services** (`CheckoutService`, `InventoryService`, `CouponService`) — é onde a regra de negócio vive, então é o que mais precisa de cobertura.
  - Testes de contrato dos **Gateways** (`StripeGateway`, `MercadoPagoGateway`, `EfiGateway`) usando mocks/fakes de HTTP, validando especialmente `verifyWebhookSignature`.
  - Feature tests na API (`/api/v1/...`) cobrindo os principais fluxos: checkout feliz, checkout com gateway indisponível, webhook duplicado (idempotência), alocação concorrente de chave.
  - O pipeline de CI (GitHub Actions/GitLab CI) deve **bloquear o merge** de qualquer PR que reduza a cobertura de testes nos diretórios `app/Services` e `app/Gateways` — testes ali não são opcionais, são gate obrigatório.
- **Next.js**: [Vitest](https://vitest.dev/)/Jest para componentes e lógica de UI (carrinho, formulários), [Playwright](https://playwright.dev/) para E2E cobrindo o fluxo crítico completo (busca → PDP → carrinho → checkout).
- **Meta prática**: cobertura alta obrigatória em `Services/` e `Gateways/` (dinheiro passa por ali); cobertura mais leve em Controllers/Resources, que são mais finos.

### 9.2 Observabilidade

- **Logs estruturados** (JSON) em produção, nunca `Log::info` de texto livre — facilita busca e alerta.
- **Rastreamento de erro**: [Sentry](https://sentry.io/) (ou equivalente) tanto no Laravel quanto no Next.js, capturando exceções não tratadas e falhas de webhook/gateway.
- **[Laravel Telescope](https://laravel.com/docs/telescope)** habilitado apenas em ambiente de desenvolvimento/staging (nunca em produção, por exposição de dados sensíveis).
- **Métricas de fila**: [Laravel Horizon](https://laravel.com/docs/horizon) para monitorar filas Redis (jobs travados, falhas, tempo de processamento) — crítico porque pagamento, entrega e revalidação de cache dependem de fila.
- **Alertas**: fila com jobs falhando repetidamente (ex: `ProcessPaymentWebhook`, `DeliverDigitalProduct`) deve gerar alerta ativo (Slack/e-mail), não só ficar visível em um dashboard que ninguém olha.

---

## 10. Contrato de erro da API

Para o Next.js tratar erros de forma previsível (toast, redirecionamento, retry), a API Laravel responde erros sempre no mesmo formato:

```json
{
  "error": {
    "code": "INSUFFICIENT_STOCK",
    "message": "Produto sem estoque suficiente para a quantidade solicitada.",
    "trace_id": "req_8f7a9b2c",
    "details": {
      "product_id": 123,
      "requested": 5,
      "available": 2
    }
  }
}
```

- `code`: string estável em `UPPER_SNAKE_CASE`, usada pelo Next.js para decidir a ação de UI (não parsear `message`, que é só para exibição/log).
- `trace_id`: identificador único de correlação, gerado por um middleware do Laravel (`X-Correlation-ID`) em cada request. Permite ao suporte técnico ligar o erro visto pelo cliente no Next.js ao log exato no Sentry/Laravel, acelerando o troubleshooting. Esse mesmo ID é repassado nas chamadas HTTP que os `Gateways` fazem a Stripe/Mercado Pago/Efi, fechando o rastreamento de ponta a ponta.
- HTTP status code semanticamente correto (`422` para validação, `409` para conflito de estoque/cupom, `402 Payment Required` para falha de gateway — escolha consciente que o cliente HTTP do Next.js precisa tratar explicitamente, já que não é um status comum, `500` para erro inesperado).
- Erros de validação de campo (Form Request) seguem o mesmo envelope, com `details` listando os campos inválidos.

---

# Parte II — Modelagem de Dados (Catálogo Digital)

## 11. Premissas do catálogo

- Catálogo **100% digital**: sem variação física (cor/tamanho), sem estoque multi-depósito.
- Estoque funciona como **pool de itens únicos**: cada linha cadastrada pelo admin é uma chave/licença específica, consumida (marcada como vendida) na primeira venda.
- Admin adiciona estoque colando **texto multilinha** — cada linha vira uma linha de estoque (`1 linha = 1 key`).
- Cliente enxerga apenas a **contagem de disponíveis**, nunca as chaves em si.

---

## 12. Modelo de dados

```text
products
├── id
├── name
├── slug
├── description
├── price (decimal)
├── status (draft | active | archived)
├── created_at / updated_at

product_stock_items          ← o "pool" de chaves
├── id
├── product_id (FK → products)
├── value (texto criptografado — a chave/licença, ex: "XXXX-YYYY-ZZZZ", ou para produtos do tipo download, um identificador de arquivo/URL assinada, ex: "s3://bucket/arquivo.zip")
├── status (available | sold | revoked)
├── order_item_id (FK → order_items, nullable — preenchido quando vendida)
├── added_by (FK → users — qual admin cadastrou)
├── batch_id (agrupa linhas coladas na mesma operação, útil para auditoria/rollback)
├── created_at / updated_at

orders
├── id
├── customer_id (FK → users, nullable se guest checkout)
├── status (pending | awaiting_payment | paid | failed | refunded)
├── total (decimal)
├── external_reference (id da transação no gateway)
├── created_at / updated_at

order_items
├── id
├── order_id (FK → orders)
├── product_id (FK → products)
├── unit_price (decimal — snapshot do preço no momento da compra)
├── stock_item_id (FK → product_stock_items, nullable até a alocação)
├── created_at / updated_at
```

**Por que `unit_price` fica em `order_items` e não só em `products`:** preço muda com o tempo; o pedido precisa preservar o valor cobrado no momento da compra, não o preço atual do produto (senão histórico de vendas fica incorreto após qualquer reajuste).

**Por que `order_item_id` fica em `product_stock_items` (e não uma tabela pivot):** a relação é 1:1 — uma chave, uma vez vendida, pertence a exatamente um item de pedido. Uma FK direta é mais simples e evita join desnecessário.

**Política de reembolso (decisão de negócio):** uma chave vendida e depois reembolsada **nunca volta ao estoque disponível**. Ela transita para `revoked` permanentemente — o `status` não tem caminho de volta para `available`. Isso evita revender uma chave que o cliente original pode já ter ativado/usado antes do reembolso ser processado.

---

## 13. Fluxo de estoque (admin adiciona chaves em lote)

1. Admin abre o produto no Filament e cola um bloco de texto, uma chave por linha:
   ```text
   ABCD-1234-EFGH
   IJKL-5678-MNOP
   QRST-9012-UVWX
   ```
2. O backend faz `explode("\n", $texto)`, remove linhas vazias/duplicadas, e cria uma linha em `product_stock_items` por chave, todas com o mesmo `batch_id` (UUID gerado nessa operação).
3. O contador de "disponível" mostrado ao cliente **não é contado diretamente no banco a cada request** — em uma tabela que pode chegar a centenas de milhares de linhas, isso vira gargalo de performance mesmo com índice. O contador vive em **Redis**, mantido sincronizado por um `ProductStockObserver`:
   ```php
   // Ao criar ou mudar status de um item (available → sold/revoked)
   Redis::set(
       "product_stock_count:{$product->id}",
       ProductStockItem::where('product_id', $product->id)->where('status', 'available')->count()
   );
   ```
   A API do Next.js lê `available_count` do Redis (resposta em milissegundos). O `count()` direto no banco continua existindo, mas só como **fonte da verdade para reconciliação** — um job noturno recalcula e corrige o valor no Redis, cobrindo qualquer dessincronia (ex: falha de Observer, deploy no meio de uma operação).
4. Nunca um campo `stock_count` fixo em `products` sem mecanismo de invalidação — isso é exatamente o que o Redis + Observer + reconciliação evita.
5. `batch_id` permite ao admin auditar ou desfazer uma importação errada (ex: colou a lista errada por engano) sem precisar identificar linha por linha — a migration (Seção 15) inclui índice nessa coluna para que o `where('batch_id', $id)->delete()` de rollback seja rápido mesmo em tabelas grandes.

---

## 14. Fluxo de alocação de chave no checkout (evitando venda duplicada)

Esse é o ponto mais delicado do modelo: **dois clientes não podem receber a mesma chave** se comprarem ao mesmo tempo (condição de corrida). A solução é alocar a chave dentro de uma transação de banco com [lock pessimista](https://laravel.com/docs/queries#pessimistic-locking), no momento da confirmação de pagamento — não no momento em que o cliente clica "comprar".

```php
DB::transaction(function () use ($orderItem) {
    $stockItem = ProductStockItem::where('product_id', $orderItem->product_id)
        ->where('status', 'available')
        ->lockForUpdate()   // PostgreSQL/MySQL: trava a linha até o commit
        ->first();

    if (!$stockItem) {
        // Estoque esgotou entre a compra e a confirmação — cenário raro mas possível
        throw new OutOfStockException($orderItem->product_id);
    }

    $stockItem->update([
        'status' => 'sold',
        'order_item_id' => $orderItem->id,
    ]);

    $orderItem->update(['stock_item_id' => $stockItem->id]);
});
```

**Por que alocar só na confirmação de pagamento, não no clique de "comprar":** se reservasse a chave assim que o cliente inicia o checkout, um carrinho abandonado travaria estoque disponível indefinidamente. A alocação real acontece dentro do Job que processa o webhook de pagamento aprovado (Seção 5), garantindo que só pedido efetivamente pago consome uma chave.

**Se o gateway aprovar o pagamento mas o estoque tiver esgotado nesse meio-tempo** (cenário raro, mas o `lockForUpdate` sozinho não evita — ele só evita duas transações pegarem a *mesma* linha, não evita que o pool acabe): o `OutOfStockException` deve disparar automaticamente um processo de reembolso parcial/total via `PaymentGatewayInterface::refund()`, e notificar o admin. Isso precisa estar no `CheckoutService`, não deixado como "vai que não acontece".

**Fluxo de reembolso pós-venda:** quando um pedido já entregue é reembolsado (não o caso acima, que é falta de estoque, mas um reembolso solicitado depois da entrega), a `product_stock_item` associada transita de `sold` para `revoked` — nunca de volta para `available` (ver política de reembolso na Seção 12). O `revoked` sinaliza que a chave existiu, foi vendida, e está permanentemente fora de circulação, preservando o histórico para auditoria.

---

## 15. Migrations (Laravel)

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
    $table->timestamps();
});

Schema::create('product_stock_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->text('value'); // criptografado via cast, ver Seção 17
    $table->enum('status', ['available', 'sold', 'revoked'])->default('available');
    $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
    $table->uuid('batch_id');
    $table->timestamps();

    $table->index(['product_id', 'status']); // acelera reconciliação do contador
    $table->index('batch_id'); // acelera rollback de importação em lote
});

Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('status', ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded'])->default('pending');
    $table->decimal('total', 10, 2);
    $table->string('external_reference')->nullable()->index();
    $table->timestamps();
});

Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained();
    $table->decimal('unit_price', 10, 2);
    $table->foreignId('stock_item_id')->nullable()->constrained('product_stock_items')->nullOnDelete();
    $table->timestamps();
});
```

---

## 16. Recurso Filament (bulk add)

No `ProductResource` do Filament, a aba de estoque usa um campo `Textarea` dedicado em vez de formulário linha-a-linha:

```php
Textarea::make('bulk_keys')
    ->label('Colar chaves (uma por linha)')
    ->rows(10)
    ->dehydrated(false) // não é um campo do model, só input transitório
    ->helperText('Cada linha vira uma chave disponível em estoque.'),

Action::make('importKeys')
    ->label('Importar chaves')
    ->action(function (Product $record, array $data) {
        app(InventoryService::class)->bulkImportKeys(
            product: $record,
            rawText: $data['bulk_keys'],
            addedBy: auth()->id(),
        );
    }),
```

Toda a lógica de parse/dedupe/criação fica no `InventoryService::bulkImportKeys()` — o Filament só coleta o input e chama o Service, seguindo a regra da arquitetura de nunca duplicar lógica de negócio na camada de admin.

---

## 17. Segurança do dado de estoque

- `product_stock_items.value` usa o cast [`encrypted`](https://laravel.com/docs/eloquent-mutators#encrypted-casting) do Laravel (`protected $casts = ['value' => 'encrypted'];`) — a chave fica ilegível diretamente no banco, mesmo em caso de dump/vazamento de backup.
- Nenhuma rota da API pública retorna `value` — os `API Resources` de produto expõem apenas o contador `available_count` (servido via Redis, ver Seção 13), nunca a lista de chaves ou uma chave individual fora do fluxo de entrega pós-pagamento.
- A entrega ao cliente (Parte III) busca o `value` só no momento de gerar o e-mail/página de sucesso, nunca fica exposto em endpoint reutilizável sem expiração.
- Log de auditoria: toda mudança de `status` em `product_stock_items` (`available → sold`, `sold → revoked`) deve ser registrada (quem, quando, por qual pedido) — reaproveitando o mecanismo de log de auditoria já previsto no Filament (Seção 8).

---

# Parte III — Entrega Automática

## 18. Visão geral do fluxo de entrega

```text
Webhook do gateway (pagamento aprovado)
        │
        ▼
Job: ProcessPaymentWebhook (fila)
        │
        ├─▶ Aloca chave (lockForUpdate) — Seção 14
        │
        ├─▶ Job: DeliverDigitalProduct
        │         ├─▶ Gera signed URL de sucesso (expira em X horas)
        │         └─▶ Job: SendDeliveryEmail (Mail::queue)
        │
        └─▶ Job: RevalidateStorefrontCache (se estoque zerou)
```

Toda a entrega roda **em fila**, nunca no request síncrono do webhook — se o e-mail demorar ou o SMTP falhar temporariamente, o pagamento já está registrado e a fila reprocessa a entrega sem re-executar a cobrança.

---

## 19. Passo a passo da entrega

1. **Webhook aprovado** chega em `POST /webhooks/{gateway}`, assinatura validada (`verifyWebhookSignature`).
2. **`ProcessPaymentWebhook`** (Job) roda dentro de uma transação: marca `order.status = paid`, aloca a `product_stock_item` (Seção 14).
3. Ao final da transação (garantindo que a chave já está `sold` e commitada), dispara **`DeliverDigitalProduct`**:
   - Descriptografa o `value` da chave (o cast `encrypted` do Eloquent já faz isso ao acessar o atributo).
   - Gera uma **signed URL temporária** (`URL::temporarySignedRoute`) apontando para a página de sucesso — não embute a chave direto na URL.
   - Dispara `SendDeliveryEmail` com a mesma signed URL.
4. Cliente acessa a signed URL (via e-mail ou redirect pós-checkout) → a rota valida a assinatura e a expiração, busca a chave associada ao `order_item_id` autenticado por essa URL, e a exibe **uma única vez na tela** (ou permite copiar/baixar).

---

## 20. Página de sucesso (signed URL)

```php
// Geração (dentro do Job DeliverDigitalProduct)
$url = URL::temporarySignedRoute(
    'orders.success',
    now()->addHours(48),
    ['order' => $order->uuid] // usar UUID público, nunca o id incremental
);
```

```php
// Rota protegida
Route::get('/pedido/{order:uuid}/sucesso', SuccessController::class)
    ->name('orders.success')
    ->middleware('signed'); // Laravel valida assinatura + expiração automaticamente
```

**Por que UUID e não o `id` incremental do pedido:** evita enumeração — um `id=1042` sequencial permite adivinhar outras URLs; um UUID não.

**Por que a URL expira (48h, por exemplo):** reduz a janela de exposição se o link vazar (encaminhado sem querer, ficar em cache de e-mail compartilhado, etc.). Passado esse prazo, o cliente usa o fluxo de reentrega (Seção 22), que exige autenticação real.

O Next.js **não recebe a chave via API pública** — essa página de sucesso pode ser uma rota do próprio Next.js que faz um fetch autenticado (server-side, usando o token da signed URL) para um endpoint específico do Laravel que só responde uma vez por sessão válida, nunca um endpoint genérico de "buscar produto".

---

## 21. E-mail de entrega

```php
Mail::to($order->customer_email)->queue(new DigitalProductDelivered($order, $signedUrl));
```

- O e-mail contém a **signed URL**, não a chave em texto puro no corpo — assim, se a caixa de e-mail do cliente for comprometida depois, a chave ainda está atrás de uma URL com expiração (e pode ser invalidada/reemitida, ver Seção 22).
- Fallback: se o cliente preferir, o e-mail também pode incluir a chave diretamente como conveniência — **isso é uma decisão de produto, não técnica**; tecnicamente, expor só a signed URL é mais seguro.
- Assunto e corpo do e-mail nunca incluem o valor da chave no *subject* (subjects de e-mail costumam ficar em logs de servidores de SMTP intermediários com menos proteção que o corpo).

---

## 22. Reentrega (cliente perdeu a chave)

Fluxo de "lookup" mencionado no sistema legado (pedido + e-mail) é mantido, mas com controles adicionais:

1. Cliente informa `order_uuid` + e-mail cadastrado na página de rastreio.
2. Rate limiting agressivo nesse endpoint (`RateLimiter` — poucas tentativas por IP/e-mail), já que é um vetor natural de brute-force de pedidos.
3. Se validado, o backend **não reexpõe a mesma chave livremente** — gera uma nova signed URL de curta duração (ex: 1h) e reenvia por e-mail (nunca exibe direto na tela do lookup, para evitar que alguém com o `order_uuid` adivinhado e um e-mail testado em massa colete chaves).
4. Toda reentrega é registrada em log de auditoria (`delivery_logs`: quando, IP, user agent) — permite detectar tentativa de abuso.

---

## 23. Checklist de segurança da entrega

- [ ] Chave nunca trafega em querystring de URL não assinada.
- [ ] Signed URLs usam UUID público do pedido, nunca o `id` incremental.
- [ ] Signed URLs têm expiração curta e são de uso único idealmente (invalidar após primeiro acesso bem-sucedido, se o produto permitir apenas uma visualização).
- [ ] Endpoint de reentrega tem rate limiting e log de auditoria.
- [ ] E-mail não expõe a chave no assunto; corpo evita reprodução desnecessária do valor em texto puro quando uma URL assinada já cumpre o papel.
- [ ] Nenhum endpoint genérico de "detalhe do produto" retorna `product_stock_items.value` — isso só existe no fluxo de entrega autenticado.
- [ ] Chave reembolsada transita para `revoked` (nunca de volta para `available`), conforme a política de reembolso definida na Seção 12.

---

# Parte IV — Roadmap

## 24. Próximos passos

1. Implementação da interface `PaymentGatewayInterface` para Stripe, Mercado Pago e Efi, com testes de contrato (mocks) escritos antes da lógica real (TDD).
2. Estrutura de API Resources e versionamento (`/api/v1`).
3. Implementar migrations e models da Parte II (`products`, `product_stock_items`, `orders`, `order_items`).
4. Implementar `InventoryService::bulkImportKeys()` e `ProductStockObserver` (cache Redis do contador).
5. Implementar `CheckoutService` com alocação de chave via `lockForUpdate` e tratamento de `OutOfStockException`.
6. Implementar Jobs de entrega (`DeliverDigitalProduct`, `SendDeliveryEmail`) e a rota de página de sucesso com signed URL.
7. Setup de CI/CD com build de imagem Docker para deploy em Square Cloud ou provedor equivalente, com gate de cobertura de testes (Seção 9.1).
8. Configuração inicial de Sentry + Horizon antes do primeiro deploy em produção.
9. Implementar middleware de `X-Correlation-ID` no Laravel (Seção 10), propagando o `trace_id` até as chamadas de gateway.
