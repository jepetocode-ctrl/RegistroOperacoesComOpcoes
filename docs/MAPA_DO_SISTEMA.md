# MAPA_DO_SISTEMA — RegistroOperacoesComOpcoes

> **Instrução para qualquer IA:** leia este arquivo antes de planejar ou editar. Ele é contexto inicial, não substitui a inspeção do código atual. Verifique branch, arquivos, chamadas e testes; não invente funções, dependências, regras ou resultados. Separe fatos confirmados, hipóteses, pendências e planos. Preserve as regras financeiras e de segurança. Faça mudanças pequenas, adicione testes e atualize este mapa e a documentação relacionada junto com mudanças de contratos, rotas, schema ou regras. Nunca afirme que testes/migrações foram executados sem evidência.

> Fonte de verdade: código atual do repositório e documentos versionados. Se houver divergência, investigue e corrija documentação e código. Consolidação: 2026-10-23.

## 1. Objetivo

Plataforma completa para registro de operações com opções, reconhecimento automático de estratégias, precificação, payoff, simulação de cenários e análise de riscos. Esta é a visão do produto, não uma declaração de que todos esses recursos estão concluídos. O código identificado inclui cadastros, operações/posições, rolagens e funções iniciais de precificação; reconhecimento geral de estratégias e payoff multi-perna devem ser verificados antes de serem tratados como implementados.

## 2. Stack: fatos e divergências

- PHP 8.3+, Composer, PSR-4, PDO e roteador próprio.
- Telas PHP com HTML/CSS/JavaScript; motor financeiro separado em TypeScript.
- PostgreSQL 16 como banco principal; SQLite usado como opção de desenvolvimento/testes em partes do projeto.
- PHPUnit 11; pacote TypeScript usa TypeScript e Node.js para build/testes.
- Docker e Docker Compose estão presentes.
- **Laravel não foi confirmado no código consultado:** `composer.json` não declara Laravel como dependência, e o projeto usa `App\Support\Router` e PDO. Tratar Laravel como divergência a esclarecer, não como framework ativo, até evidência em contrário.

Arquivos: `composer.json`, `package.json`, `src/financial-engine/package.json`, `config/database.php`, `.env.example`, `Dockerfile`, `docker-compose.yml`.

## 3. Arquitetura

```text
Browser/HTTP -> routes/web.php ou routes/api.php -> app/Support/Router
 -> app/Controllers -> app/Services -> app/Repositories / PDO
 -> database/migrations
Motor financeiro separado: src/financial-engine/
Views: resources/views/
Testes: tests/ e src/financial-engine/tests/
```

Responsabilidades: routes declaram endpoints; controllers tratam HTTP; services orquestram regras de negócio; repositories persistem; validators verificam payloads; middleware/support oferecem autenticação, CSRF, sessão, roteamento, banco, respostas e auditoria; views apresentam dados; motor TypeScript contém cálculos puros e testáveis. Não duplicar regras financeiras em controllers/views.

## 4. Diretórios e arquivos principais

- `app/Controllers/`: autenticação, dashboard, cadastro, operações, rolagens, usuários.
- `app/Middleware/`: `AuthMiddleware.php`, `CsrfMiddleware.php`.
- `app/Repositories/`: `FinancialRegistrationRepository.php`, `OperationRepository.php`, `UserRepository.php`.
- `app/Services/`: `AuthService.php`, `FinancialRegistrationService.php`, `OperationService.php`, `RolloverService.php`.
- `app/Support/`: banco PDO, roteador, sessão, request/response, auditoria e helpers.
- `app/Validators/`: `AuthValidator.php`, `FinancialRegistrationValidator.php`.
- `config/`: configurações de app e banco.
- `database/migrations/`: migrações versionadas.
- `docs/`: API, arquitetura, banco, decisões, regras financeiras, mapa, status, próximos passos e segurança.
- `public/index.php`: front controller.
- `resources/views/`: telas PHP de autenticação, dashboard, administração e registro.
- `routes/web.php`, `routes/api.php`: rotas web/API.
- `scripts/`: runner de migrações, servidor local e verificações.
- `src/financial-engine/src/index.ts`: motor TypeScript.
- `tests/Feature/`: testes PHPUnit.
- Raiz: `README.md`, `composer.json`, `package.json`, `phpunit.xml`, `.env.example`, `Dockerfile`, `docker-compose.yml`.

## 5. Rotas/módulos

Rotas web incluem `/registros`, POST de ativos/contratos/estratégias/operações, login/logout, dashboard e administração de usuários. API inclui autenticação, `/api/assets`, `/api/option-contracts`, `/api/strategies`, `/api/operations`, `/api/positions`, `/api/quotes` e `/api/rollovers`. Verifique `routes/web.php` e `routes/api.php` para o contrato atual; existência de rota não garante implementação completa nem cobertura de testes.

## 6. Regras de negócio críticas — não violar

1. **Ledger append-only:** preservar operações históricas; correções por lançamentos compensatórios e trilha de auditoria.
2. **Rolagem atômica:** encerramento, alocação, abertura, registro da rolagem e histórico na mesma transação. Qualquer falha deve provocar rollback integral.
3. **Métricas separadas:** fluxo bruto/líquido, custos, valor de mercado, P&L não realizado e resultado realizado são conceitos distintos. Crédito/débito líquido não é automaticamente lucro.
4. **Isolamento por usuário:** verificar titularidade do usuário autenticado para operações, contratos, estratégias e dados financeiros relacionados. Nunca confiar somente em IDs fornecidos pelo cliente.
5. **Precisão:** persistir valores financeiros em NUMERIC/DECIMAL; documentar arredondamento e tolerâncias; não usar ponto flutuante binário como representação monetária persistida.
6. **Motor centralizado:** cálculos financeiros em TypeScript, com entradas/saídas explícitas e testes; evitar duplicação nas views/controllers e validar resultados antes de persistir.
7. **Segurança:** autenticação nas rotas privadas, CSRF nas mutações conforme padrões existentes, prepared statements e erros internos genéricos ao cliente.
8. **Integridade de mercado:** não fabricar cotações, strikes listados, liquidez ou preços; distinguir dados informados, históricos, calculados e indisponíveis.

## 7. Estratégias e pernas: estado observado

`resources/views/registry/index.php` e `RegistryController::storeStrategy()` atendem o cadastro de estratégia. O controller converte o campo `legs` de JSON para array PHP; campo vazio vira lista vazia. `FinancialRegistrationValidator` exige nome e verifica que `legs`, se presente, é array, mas não valida cada perna individualmente no código consultado.

Logo, não presumir que já existe reconhecimento automático de estratégias, validação completa de pernas, payoff multi-perna, break-even, risco máximo/ilimitado ou simulação completa. Ao implementar, definir contrato tipado de perna (instrumento/contrato, CALL/PUT, BUY/SELL, strike, vencimento, quantidade, prêmio/preço, multiplicador e custos), validar relações e combinações e escrever testes de casos financeiros conhecidos. Distinguir estratégia declarada manualmente de classificação calculada.

## 8. Motor financeiro identificado

Arquivo: `src/financial-engine/src/index.ts`.

- Tipos `OptionType = CALL | PUT` e classificação `ITM | ATM | OTM`.
- `blackScholes()`: preço, valores intrínseco/extrínseco, delta, gamma, theta, vega, rho, probabilidade neutra ao risco ITM, classificação, distância percentual e d1/d2.
- Entradas incluem spot, strike, volatilidade, taxa, dividend yield, prazo em anos e tipo.
- `findReverseStrike()`: busca de strike para preço-alvo, com limites, tolerância, máximo de iterações e estados `OK`, `NO_SOLUTION`, `MAX_ITERATIONS`.
- O código trata explicitamente prazo zero e volatilidade efetivamente zero.
- Testes/build: `cd src/financial-engine && npm test`.

A probabilidade ITM é neutra ao risco, não uma previsão garantida. Resultados teóricos não são cotações de mercado. Este motor identificado não comprova, por si só, payoff multi-perna ou reconhecimento automático de estratégias. Confirmar integração com dashboard antes de declarar a integração concluída.

## 9. Persistência e operações

PostgreSQL é o banco principal; SQLite é suportado em partes do ambiente de testes. Migrações em `database/migrations/`; execução por `php scripts/run-migrations.php`. Entidades observadas incluem usuários, ativos, contratos de opções, estratégias, operações, alocações de encerramento, cotações, histórico e rolagens. Antes de alterar schema, conferir constraints, índices, todas as migrações e compatibilidade entre bancos. Não presumir que reexecutar migrações é seguro sem conferir o runner.

Rolagens usam UUID e registram encerramento/abertura e histórico. Revisar alocação proporcional de custos e concorrência em rolagens simultâneas antes de mudanças nessa área.

## 10. Testes e comandos

```bash
composer install
cp .env.example .env
composer test
php scripts/run-migrations.php
cd src/financial-engine && npm test
```

A presença de testes não comprova execução. Testes SQLite não substituem validação PostgreSQL; build do motor não comprova integração com a interface. Registrar comandos e resultados reais em `docs/PROJECT_STATUS.md` ou CI.

## 11. Documentação complementar

Ler conforme a tarefa: `docs/ARCHITECTURE.md`, `docs/API.md`, `docs/DATABASE.md`, `docs/DECISIONS.md`, `docs/FINANCIAL_RULES.md`, `docs/PROJECT_STATUS.md`, `docs/NEXT_STEPS.md`, `docs/SECURITY.md` e `README.md`.

## 12. Pendências que precisam de confirmação

- Concluir/verificar integração da dashboard com o motor TypeScript sem duplicação de fórmulas.
- Verificar validação visual e responsividade em navegador.
- Confirmar ou implementar validação individual das pernas.
- Confirmar ou implementar reconhecimento automático de estratégias, payoff multi-perna, break-even, métricas de risco e cenários.
- Testar rolagens parciais/sucessivas, concorrência e alocação de custos.
- Executar testes e migrações nos bancos suportados, especialmente PostgreSQL.
- Auditar autenticação, autorização e CSRF em todos os handlers de mutação.
- Esclarecer divergência entre Laravel informado e arquitetura PHP própria encontrada.

Consulte `docs/PROJECT_STATUS.md` e `docs/NEXT_STEPS.md` antes de iniciar uma pendência para evitar duplicação de trabalho.

## 13. Protocolo de trabalho obrigatório

1. Ler este mapa e os documentos do escopo.
2. Inspecionar branch, código, chamadores, schema e testes atuais.
3. Respeitar ledger append-only, transações, isolamento por usuário e semântica financeira.
4. Centralizar fórmulas no motor financeiro e adicionar testes unitários/regressão.
5. Para cálculos novos, documentar fórmula, unidades, sinais, multiplicadores, custos, arredondamento, limites e exemplos.
6. Atualizar mapa e documentação relacionada junto com alterações de rotas, contratos, regras, dependências ou schema.
7. Executar testes aplicáveis e relatar o resultado real; declarar explicitamente o que não foi executado.
8. Separar fatos confirmados, hipóteses, decisões propostas e trabalho futuro.
9. Nunca declarar uma funcionalidade concluída apenas porque sua tela, endpoint ou documentação existe.
