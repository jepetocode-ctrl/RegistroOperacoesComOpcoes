# Mapa técnico do sistema

> **Fonte de verdade:** código da branch `main`. Este documento foi montado a partir dos arquivos efetivamente consultados no repositório. Ele não substitui a inspeção do código. Onde não foi possível inspecionar o inventário completo, isso está indicado explicitamente em vez de presumir que algo não existe.
>
> **Status documental:** inventário inicial criado em 2026-10-23. A Etapa 5 e a Etapa 6 estão implementadas parcialmente, mas **não validadas**: o próprio projeto registra que a suíte e as migrações não foram executadas nesta sessão.

## 1. Como usar este mapa

1. Leia esta página e [Arquitetura](ARCHITECTURE.md), [Status do projeto](PROJECT_STATUS.md), [Próximos passos](NEXT_STEPS.md) e [Decisões técnicas](DECISIONS.md).
2. Confirme o conteúdo atual dos arquivos referenciados antes de alterar o código.
3. Siga a cadeia rota → controller → serviço → persistência/motor financeiro → resposta.
4. Execute testes e migrações no checkout real; não interprete a existência de testes como evidência de execução.
5. Atualize este mapa na mesma alteração que modificar código, rotas, migrações, contratos ou testes.

### Estados usados

- **Implementado:** código encontrado no repositório.
- **Testes existentes — não executados:** há teste referenciado, mas não há evidência de execução bem-sucedida.
- **Pendente de validação:** requer execução de testes, migrações ou revisão de regra.
- **Não confirmado:** não foi possível verificar o inventário ou a implementação no escopo desta leitura.
- **Planejado:** descrito como próximo passo, sem evidência suficiente de implementação.
- **Descontinuado:** usar somente quando houver evidência explícita de remoção/substituição.

## 2. Visão arquitetural

Aplicação PHP 8.3+ com Composer/PSR-4, roteamento por arrays e PDO. PostgreSQL é o banco principal; SQLite é previsto para desenvolvimento/testes. A arquitetura separa apresentação, controladores HTTP, serviços, persistência, migrações e um motor financeiro TypeScript independente.

```text
Navegador / cliente HTTP
  ├── routes/web.php ──> Router ──> Controllers ──> Services / Support ──> PDO
  └── routes/api.php ──> Router ──> Controllers ──> Services / Support ──> PDO
                                                    └── database/migrations
Motor financeiro TypeScript (src/) ── integração com interface/backend: confirmar no código
```

Fluxo implementado para rolagens:

```text
POST /api/rollovers
  -> RolloverController::handle()
  -> autenticação de sessão + validação CSRF
  -> RolloverService::register()
  -> validação de entrada e escopo do usuário
  -> transação PDO
  -> operação de encerramento + alocação de encerramento
  -> operação de abertura
  -> registro UUID da rolagem + histórico de ambas as pernas
  -> COMMIT; em exceção, ROLLBACK
```

**Regra financeira central:** fluxo de caixa líquido não é sinônimo de lucro realizado. O resultado realizado é armazenado separadamente na alocação de encerramento.

## 3. Inventário de diretórios

A árvore abaixo reúne os diretórios relevantes declarados no README e os caminhos confirmados por leitura. Não deve ser interpretada como listagem exaustiva de cada arquivo do repositório.

| Caminho | Responsabilidade / evidência | Estado |
|---|---|---|
| `app/` | Código PHP com namespace Composer `App\\`; controllers, middleware, services e suporte | Implementado; inventário completo pendente |
| `config/` | Configurações de aplicação e conexão de banco | Implementado; consultar arquivos individuais |
| `database/migrations/` | Migrações versionadas de schema | Implementado |
| `docs/` | Arquitetura, decisões, status, próximos passos e este mapa | Implementado |
| `public/` | Front controller e recursos públicos | Implementado |
| `resources/` | Recursos de apresentação; inventário de arquivos não confirmado | Não confirmado |
| `routes/` | Definição declarativa de rotas web e API | Implementado |
| `scripts/` | Ferramentas de execução; inclui runner de migrações | Implementado |
| `src/` | Motor financeiro TypeScript separado, segundo documentação do projeto | Implementação e arquivos exatos não confirmados nesta leitura |
| `storage/` | Armazenamento local; uso e subpastas não confirmados | Não confirmado |
| `tests/` | Testes PHPUnit; há teste de feature para rolagens | Implementado; suíte não executada |
| `.env.example` | Modelo de configuração por ambiente sem segredos reais | Referenciado no README |
| `composer.json` | Requisitos PHP, PHPUnit e autoload PSR-4 | Inspecionado |
| `package.json` | Atalhos `dev`, `test`, `migrate`; não declara dependências npm | Inspecionado |
| `phpunit.xml` | Configuração PHPUnit | Referenciado no README; conteúdo não inspecionado |
| `Dockerfile`, `docker-compose.yml` | Empacotamento/ambiente local | Referenciados; conteúdo não inspecionado |
| `README.md` | Entrada do projeto e comandos operacionais | Inspecionado |

### Arquivos de entrada e configuração confirmados

- `public/index.php`: carrega autoload, importa variáveis de `.env`, lê configurações e inicializa `App\\Support\\Router` com `routes/web.php` e `routes/api.php`, então chama `dispatch()`.
- `composer.json`: PHP `>=8.3`; PHPUnit `^11.0`; PSR-4 `App\\ => app/` e `Tests\\ => tests/`; script `test` chama `./vendor/bin/phpunit --colors=never`.
- `package.json`: `dev` executa servidor PHP embutido; `test` delega a `composer test`; `migrate` chama `php scripts/run-migrations.php`. Não há dependências JavaScript declaradas nesse arquivo.
- `scripts/run-migrations.php`: seleciona driver conforme `config/database.php`, ativa exceções PDO, ativa foreign keys no SQLite, ordena `database/migrations/*.php`, carrega cada classe e executa `up(PDO)`. **Atenção:** a inspeção não encontrou mecanismo visível de controle de migrações já aplicadas; validar idempotência e comportamento de repetição antes de uso operacional.

## 4. Rotas web

Fonte: `routes/web.php`. Os handlers apontam para métodos de controllers.

| Método | Rota | Handler | Responsabilidade |
|---|---|---|---|
| GET | `/` | `HomeController::index` | Página inicial |
| GET | `/health` | `HomeController::health` | Health check web |
| GET | `/login` | `AuthController::loginPage` | Página de login |
| POST | `/login` | `AuthController::login` | Autenticação web |
| POST | `/logout` | `AuthController::logout` | Encerramento de sessão |
| GET | `/change-password` | `AuthController::changePasswordPage` | Página de alteração de senha |
| POST | `/change-password` | `AuthController::changePassword` | Alteração de senha |
| GET | `/dashboard` | `DashboardController::index` | Dashboard |

## 5. API HTTP

Fonte: `routes/api.php`. A lista representa as rotas declaradas; a lógica de despacho interno de controllers genéricos deve ser confirmada no respectivo código.

| Método | Endpoint | Handler | Observações |
|---|---|---|---|
| GET | `/api/status` | `HomeController::apiStatus` | Estado da API |
| POST | `/api/auth/login` | `AuthController::apiLogin` | Login API |
| GET | `/api/auth/me` | `AuthController::apiMe` | Usuário da sessão |
| POST | `/api/auth/logout` | `AuthController::apiLogout` | Logout API |
| GET, POST | `/api/assets` | `FinancialRegistrationController::handle` | Cadastro/listagem de ativos |
| GET, PUT, PATCH | `/api/assets/{id}` | `FinancialRegistrationController::handle` | Consulta/alteração de ativo |
| GET, POST | `/api/option-contracts` | `FinancialRegistrationController::handle` | Cadastro/listagem de contratos |
| GET, PUT, PATCH | `/api/option-contracts/{id}` | `FinancialRegistrationController::handle` | Consulta/alteração de contrato |
| GET, POST | `/api/strategies` | `FinancialRegistrationController::handle` | Cadastro/listagem de estratégias |
| GET, PUT, PATCH | `/api/strategies/{id}` | `FinancialRegistrationController::handle` | Consulta/alteração de estratégia |
| GET, POST | `/api/operations` | `OperationController::handle` | Operações |
| GET | `/api/operations/{id}/history` | `OperationController::handle` | Histórico da operação |
| POST | `/api/quotes` | `OperationController::handle` | Cotação; provedor e contrato de entrada/saída a confirmar |
| GET | `/api/positions` | `OperationController::handle` | Lista de posições |
| GET | `/api/positions/{id}` | `OperationController::handle` | Detalhe de posição |
| GET | `/api/rollovers` | `RolloverController::handle` | Histórico de rolagens do usuário |
| GET | `/api/rollovers/{uuid}` | `RolloverController::handle` | Consulta por UUID |
| POST | `/api/rollovers` | `RolloverController::handle` | Registro de rolagem transacional |

### Contratos HTTP conhecidos

- O projeto documenta 422 para falhas de validação, 401 para não autenticado e 500 para erro interno padronizado.
- `RolloverController` retorna 401 quando a sessão não está autenticada, 419 para CSRF inválido, 404 para UUID não encontrado e 405 para método diferente de GET/POST; a criação propaga o status retornado pelo serviço (normalmente 201).
- A validação CSRF é verificada no controller de rolagem para métodos de mutação. Confirmar comportamento global para os demais controllers.
- Respostas e autorização específicas das rotas de cadastro/operações dependem da implementação de seus handlers; não inferir equivalência apenas pelo padrão de rota.

## 6. Módulo detalhado: rolagens

### `app/Services/RolloverService.php` — `App\\Services\\RolloverService`

Dependência injetada: `PDO`. Usa `Throwable`, prepared statements, transações PDO e JSON para histórico. Não foi observada dependência direta de outro serviço de domínio no arquivo inspecionado.

#### `__construct(PDO $pdo): void`

- **Responsabilidade:** guardar a conexão PDO usada pela operação.
- **Entrada:** conexão PDO.
- **Retorno:** nenhum.
- **Chamadores:** `RolloverController::__construct()`; teste `RolloverServiceTest::setUp()`.
- **Erros:** conforme construção/configuração de PDO.
- **Status:** implementado; teste indireto existente, execução pendente.

#### `register(int $userId, array $input): array`

- **Responsabilidade:** validar e registrar atomicamente uma rolagem, incluindo encerramento, alocação de quantidade encerrada, abertura, registro UUID e histórico.
- **Entradas obrigatórias:** `source_operation_id`, `closing_contract_id`, `opening_contract_id`, `quantity`, `closing_unit_price`, `opening_unit_price`.
- **Entradas opcionais observadas:** `strategy_id`, `opening_side`, `closing_costs`, `opening_costs`, `executed_at`.
- **Retorno de validação/regra:** array com `success=false`, `status=422`, `errors`.
- **Retorno de sucesso:** array com `success=true`, `status=201`, `data` incluindo UUID, IDs de origem/pernas, quantidade, fluxos, custos, resultado realizado e nota sobre fluxo de caixa.
- **Exceções:** erros PDO/JSON/random_bytes e demais `Throwable` propagam após rollback, se a transação ainda estiver aberta.
- **Dependências internas:** `validate()`, `reject()`, `uuid()`, `insertOperation()`, `insertHistory()`; tabelas `operations`, `option_contracts`, `strategies`, `operation_closures`, `rollovers`, `operation_history`.
- **Chamadores:** `RolloverController::handle()`; testes em `tests/Feature/RolloverServiceTest.php`.
- **Regras:** operação fonte deve pertencer ao usuário; contrato de encerramento deve corresponder à fonte; contrato de abertura deve ser diferente; contratos devem estar ativos e pertencer ao usuário; estratégia informada deve pertencer ao usuário; quantidade deve caber no remanescente; operações, alocação, rollover e histórico são gravados na mesma transação.
- **Fórmulas observadas:** fluxo bruto por perna = preço unitário × quantidade × multiplicador do contrato, com sinal dependente de BUY/SELL; fluxo líquido = fluxo de encerramento + fluxo de abertura − custos totais; resultado realizado do encerramento é calculado separadamente usando diferença entre preço de encerramento e preço unitário de origem, multiplicador, direção da posição e alocação proporcional dos custos da origem.
- **Riscos registrados pelo próprio projeto:** política de alocação de custos em encerramento parcial precisa revisão; controle de concorrência para rolagens simultâneas ainda precisa ser definido/testado. A consulta de quantidade remanescente e a gravação não exibem bloqueio explícito de linha; há risco de corrida concorrente.
- **Status:** implementação encontrada; teste de feature existente, mas não executado; não validado para uso financeiro real.

#### `history(int $userId, ?string $uuid = null): array`

- **Responsabilidade:** consultar registros de rolagem pertencentes ao usuário; filtrar opcionalmente por UUID e ordenar por ID descendente.
- **Entradas:** ID do usuário; UUID opcional.
- **Retorno:** linhas de `rollovers` como array associativo.
- **Erros:** exceções PDO podem propagar ao controller.
- **Dependências:** PDO e tabela `rollovers`.
- **Chamadores:** `RolloverController::handle()`.
- **Testes:** `RolloverServiceTest` consulta histórico por UUID no teste principal.
- **Status:** implementado; execução dos testes pendente.

#### Métodos privados de apoio

| Método | Entradas → retorno | Responsabilidade / dependências |
|---|---|---|
| `validate(array $input): array` | dados de entrada → mapa de erros | Verifica presença de campos obrigatórios, inteiros positivos, valores numéricos não negativos, BUY/SELL e data/hora parseável. Chamado por `register`. |
| `reject(array $errors): array` | erros → resposta de falha 422 | Faz rollback se houver transação aberta. Chamado em falhas de regra dentro de `register`. |
| `insertOperation(...): int` | dados da perna → ID inserido | Insere em `operations` com prepared statement e retorna `lastInsertId()`. Chamado duas vezes por `register`. |
| `insertHistory(int $userId, int $operationId, array $history): void` | usuário, operação, payload → void | Insere evento `ROLLOVER_REGISTERED` em `operation_history`; chamado para cada perna. Pode lançar erro PDO/JSON. |
| `uuid(): string` | nenhum → UUID v4 textual | Gera 16 bytes aleatórios e define bits de versão/variante; usa `random_bytes`. Chamado por `register`. |

### `app/Controllers/RolloverController.php` — `App\\Controllers\\RolloverController`

#### `__construct(): void`

Cria `RolloverService` usando `Database::defaultConnection()`. Chamado pelo roteador quando a rota é despachada. Erros de conexão/configuração dependem da camada de banco.

#### `handle(): void`

- **Responsabilidade:** validar sessão e CSRF, despachar listagem/consulta/criação e converter o resultado em JSON HTTP.
- **Entrada:** contexto HTTP global; usa `Request::method()`, `Request::all()`, `$_SERVER['REQUEST_URI']` e `SessionManager::user()`.
- **Retorno:** nenhum; escreve resposta via `Response::json()`.
- **Dependências:** `AuthMiddleware`, `CsrfMiddleware`, `RolloverService`, `Database`, `Request`, `Response`, `SessionManager`.
- **Erros/status:** 401 sem sessão; 419 CSRF inválido; 404 UUID não encontrado; 405 método não permitido; 500 em Throwable durante a operação.
- **Chamador:** rotas GET/POST `/api/rollovers` em `routes/api.php`.
- **Testes:** o teste disponível cobre o serviço diretamente; teste HTTP/controller não confirmado.
- **Status:** implementado; cobertura HTTP e execução de suíte pendentes.

## 7. Banco de dados e persistência

### Migração de rolagens

Arquivo: `database/migrations/20261023_000004_create_rollovers.php`; classe `Database\\Migrations\\CreateRollovers`.

- `up(PDO $pdo): void`: cria tabela `rollovers` e índices para usuário/data e estratégia/data. Usa tipos condicionais SQLite/PostgreSQL (ID, timestamp, UUID, JSON).
- `down(PDO $pdo): void`: remove `rollovers`.
- Restrições: UUID único; quantidade positiva; custos não negativos; chaves estrangeiras para usuário, estratégia, operação de origem, pernas de encerramento/abertura e contratos.
- Estado: migração implementada; não executada/validada nesta sessão nos bancos alvo.

### Tabelas confirmadas pela leitura do serviço/migração/teste

| Tabela | Papel e relações observadas | Estado |
|---|---|---|
| `users` | Usuários; referenciada por dados financeiros e sessão/autenticação | Confirmada por uso; schema completo não inventariado |
| `assets` | Ativos; contrato de opção referencia ativo | Confirmada |
| `option_contracts` | Contratos; usuário, ativo, tipo, strike, vencimento, multiplicador e status | Confirmada |
| `strategies` | Estratégias por usuário | Confirmada |
| `operations` | Ledger de operações; contrato, estratégia, lado, quantidade, preço, fluxo, custos, data, referência e tipo | Confirmada |
| `operation_closures` | Alocação de encerramento entre operação de abertura e operação de fechamento, quantidade e resultado realizado | Confirmada |
| `operation_history` | Histórico/auditoria de ações por operação | Confirmada |
| `rollovers` | Registro agregado da rolagem com UUID, usuário, estratégia, operação fonte, duas pernas, contratos, fluxos, custos e JSON histórico | Confirmada pela migração |

**Relações da rolagem:** `rollovers.source_operation_id`, `closing_operation_id` e `opening_operation_id` referenciam `operations.id`; os IDs de contrato referenciam `option_contracts.id`; `user_id` referencia `users.id`; `strategy_id` referencia `strategies.id` e pode ser nulo.

**Inventário pendente:** localizar e descrever todas as migrações anteriores, constraints e índices; documentar o esquema completo de cada tabela e as relações de posições; comparar o DDL de SQLite e PostgreSQL. Não tratar a tabela acima como catálogo completo do banco.

## 8. Motor financeiro TypeScript e fórmulas

A documentação arquitetural afirma que existe um motor TypeScript isolado de sessão, banco e apresentação. Nesta leitura não foi possível confirmar o inventário de arquivos do motor nem obter seu conteúdo por caminhos candidatos. Portanto, não registrar funções específicas de Black-Scholes-Merton, gregas, probabilidades ou strike reverso como implementadas sem localizar os arquivos reais.

| Área exigida | Estado documental | Próxima verificação |
|---|---|---|
| Black-Scholes-Merton | Não confirmado por leitura de fonte | Inventariar `src/`, exports e testes; registrar fórmulas, convenções de taxa/dividendos e unidades |
| Gregas e probabilidades | Não confirmado | Localizar funções, tipos de entrada/saída e testes numéricos |
| Calculadora reversa de strike | Não confirmado | Confirmar algoritmo, critérios de convergência, tolerância e casos sem solução |
| Breakeven operacional | Não confirmado | Identificar custos, multiplicador, quantidade e regras de payoff |
| Rolagens/posições | Lógica PHP inicial identificada; validação pendente | Rever semântica financeira e consistência com motor TypeScript |
| Precisão numérica | Pendente | Definir tolerâncias, arredondamento, limites e casos extremos |

Para cada função financeira encontrada futuramente, documentar a fórmula matemática, as convenções de sinal/unidades, parâmetros e tipos, retorno, validação, erros, referências/call sites e testes. Separar claramente resultado analítico, aproximação numérica e preço de mercado.

## 9. Segurança, autenticação e erros

- Autenticação descrita como sessão PHP; `RolloverController` verifica `AuthMiddleware::isAuthenticated()` e obtém usuário via `SessionManager::user()`.
- Mutação de rolagem exige token CSRF via `CsrfMiddleware::validate()`.
- O serviço valida o usuário proprietário da operação, contratos e estratégia, reduzindo acesso cruzado entre usuários.
- SQL de rolagens usa prepared statements.
- `.env.example` é indicado como modelo sem segredos reais; `public/index.php` lê `.env` se presente.
- Erros internos de rolagem são devolvidos como mensagem genérica HTTP 500; exceção não é exposta na resposta.
- **Pendente de auditoria:** rate limiting/login, política de sessão e cookies, hash de senha, autorização nos outros controllers, validação global CSRF, CORS, headers de segurança, logs, proteção de segredos e comportamento em produção.

## 10. Testes e evidências

### `tests/Feature/RolloverServiceTest.php`

- **Setup:** PDO SQLite em memória, foreign keys habilitadas, schema de fixture, migrações de operações/posições e rolagens e uma operação de origem.
- **`testRolloverLinksBothLegsTracksNetCashAndLeavesCorrectRemainder()`:** verifica sucesso/status 201, fluxo líquido e custos esperados, UUID, número de operações/rolagens/históricos, consulta por UUID, quantidade remanescente e nota de que fluxo não é lucro.
- **`testFailureWritingHistoryRollsBackBothLegsAndRolloverRecord()`:** cria trigger SQLite que força falha no histórico e verifica rollback das operações, rolagem e alocação.
- **Evidência:** os testes existem no código. O status do projeto declara explicitamente que `composer test` não foi executado nesta sessão. Portanto, **não marcar como testado/validado** até executar e registrar o resultado real.

### Comandos operacionais

```bash
composer install
cp .env.example .env
composer test
php scripts/run-migrations.php
php -S 0.0.0.0:8000 -t public
```

O teste com SQLite em memória não substitui execução das migrações no PostgreSQL 16. Validar ambos os bancos e registrar logs/resultados em documentação de status ou CI.

## 11. Módulos funcionais — índice de navegação

| Módulo | Arquivos/caminhos conhecidos | Estado |
|---|---|---|
| Autenticação e usuários | `routes/web.php`, `routes/api.php`, `app/Controllers/AuthController.php` (referenciado pelas rotas) | Implementação indicada; detalhes de métodos/testes pendentes |
| Ativos | `routes/api.php`, `FinancialRegistrationController` (handler) | Rotas implementadas; serviço/repositório e testes a mapear |
| Contratos de opções | `routes/api.php`, `FinancialRegistrationController` | Rotas implementadas; validações e relações completas a mapear |
| Estratégias | `routes/api.php`, `FinancialRegistrationController` | Rotas implementadas; regras/testes a mapear |
| Operações | `routes/api.php`, `OperationController`, tabelas `operations` e `operation_closures` | Implementação inicial; validação pendente |
| Rolagens | `RolloverController`, `RolloverService`, migração `20261023_000004`, teste de feature | Implementação inicial; não validada |
| Posições e resultados | `/api/positions`, `OperationController`, tabelas de operações/alocações | Implementação indicada; política de custos pendente |
| Motor financeiro | `src/` segundo arquitetura | Inventário de fonte pendente |
| Dashboard | `DashboardController`, `/dashboard` | Rota confirmada; componentes e dependências a mapear |
| Relatórios/exportações | Sem evidência de fonte consultada | Não confirmado |
| Integrações/cotações | `POST /api/quotes` | Endpoint declarado; provedor e tratamento a confirmar |
| Breakeven | Sem evidência de fonte consultada | Não confirmado |
| Segurança | Middleware de autenticação/CSRF e sessão referenciados | Auditoria completa pendente |
| Infraestrutura | `Dockerfile`, `docker-compose.yml`, `config/`, `.env.example` | Arquivos referenciados; conteúdo parcial/não inspecionado |
| Testes | `tests/Feature/RolloverServiceTest.php`, configuração PHPUnit | Testes presentes; execução pendente |

## 12. Limitações, riscos e pendências conhecidas

1. **Testes não executados:** PHPUnit, runner de migrações e validação PostgreSQL não foram executados nesta sessão.
2. **Compatibilidade de banco:** executar migrações e testes em SQLite e PostgreSQL 16.
3. **Custos em encerramentos parciais:** a alocação proporcional do custo da operação original precisa revisão de regra e arredondamento.
4. **Concorrência:** duas rolagens simultâneas podem consultar o mesmo remanescente antes de gravar; definir lock/controle de concorrência compatível com PostgreSQL e SQLite.
5. **Rolagens sucessivas:** testar múltiplas rolagens parciais, múltiplas aberturas e referências entre operações.
6. **Interface de rolagens:** integração dos endpoints à interface ainda consta como pendente.
7. **Motor TypeScript:** falta inventário confirmado de arquivos, símbolos, fórmulas, consumidores e testes.
8. **Mapa completo de módulos:** controllers, serviços, repositórios, validators, eventos, configurações e funções públicas fora de rolagens ainda exigem inspeção arquivo a arquivo.
9. **Migrações:** validar reexecução, rollback e diferenças de tipos/constraints entre bancos.
10. **Verificação automatizada deste mapa:** não foi confirmada a existência de script que compare referências documentais com o sistema de arquivos.

## 13. Protocolo obrigatório para mudanças futuras

Em cada alteração:

- [ ] Ler este mapa e os documentos de arquitetura/status/decisões.
- [ ] Inspecionar o código real e localizar todos os chamadores/referências relevantes.
- [ ] Atualizar este mapa na mesma mudança que alterar arquivos, funções públicas, endpoints, schema, regras financeiras, dependências ou testes.
- [ ] Documentar entradas, tipos, retorno, erros, dependências, chamadores, testes e regras de negócio para símbolos relevantes.
- [ ] Criar/ajustar testes junto com a implementação.
- [ ] Executar testes e migrações aplicáveis; registrar comandos e resultados reais.
- [ ] Manter separados os estados implementado, testado, validado, planejado e descontinuado.
- [ ] Revisar links/caminhos citados e verificar se os arquivos ainda existem.
- [ ] Para regra financeira, registrar fórmula, unidade, sinal, arredondamento e exemplos de referência.
- [ ] Não declarar o requisito concluído apenas porque a documentação foi atualizada.

## 14. Próxima tarefa recomendada para completar o mapa

1. Obter inventário recursivo completo do repositório, incluindo arquivos ocultos relevantes.
2. Ler todos os controllers, services, repositories, middleware, support, validators e configurações.
3. Inventariar todas as migrações e documentar tabelas/constraints/índices/relacionamentos.
4. Inventariar o motor TypeScript e seus exports, tipos, enums, fórmulas e call sites.
5. Mapear telas/componentes, chamadas de API, eventos e integrações externas.
6. Relacionar testes por módulo e executar a suíte em ambiente configurado.
7. Criar um verificador de links/caminhos e, se viável, extração automatizada de símbolos sem sobrescrever a documentação manual de regras financeiras.
