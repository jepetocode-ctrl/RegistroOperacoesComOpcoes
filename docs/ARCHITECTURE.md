# Arquitetura

## Estado arquitetural atual

O projeto vive em uma fase de compatibilidade: o núcleo antigo continua funcionando e o Laravel 12 foi adicionado como bootstrap modernizador. A arquitetura real é híbrida, não totalmente migrada.

### Camadas existentes

1. Frontend legado em PHP/HTML e views em `resources/views/`
2. Controladores e serviços próprios em `app/`
3. Repositórios e acesso via PDO
4. Banco versionado por migrações em `database/migrations/`
5. Compatibilidade Laravel em `bootstrap/`, `routes/` e `artisan`
6. Motor financeiro independente em `src/financial-engine`
7. Catálogo e serviço PHP de posições estruturadas com repositórios PDO

## Padrões e regras atuais

- PHP 8.3+ e Composer PSR-4
- Laravel 12 como bootstrap e camada de runtime moderno
- compatibilidade por `legacy_*` e `App\Database\LegacyMigrations`
- `strategy_templates` / `strategy_template_slots` definem modelos e slots
- `structured_positions` / `structured_position_legs` agrupam instâncias e vinculam operações do ledger
- o payoff estruturado é calculado em PHP; o motor TypeScript segue separado, sem chamada em runtime

## Riscos de compatibilidade

- autenticação e sessões ainda seguem o padrão legado em alguns handlers
- rotas Laravel coexistem com controllers legados e a UI é servida por views PHP
- migração de JSON legado preserva o conteúdo original e marca formatos ilegíveis para revisão
- a existência de implementações de payoff PHP e TypeScript cria uma lacuna de paridade

## Modelo de domínio canônico

A estrutura atual de perna foi estabilizada em código com o tipo `CanonicalOptionLeg` e segue os campos:

- `instrumentType`
- `side`
- `optionType`
- `strike`
- `expirationDate`
- `quantity`
- `premium`
- `multiplier`
- `costs`

Este formato continua relevante para a biblioteca TypeScript; o ledger estruturado atual usa slots normalizados e operações vinculadas.

## Validação executada

- `php artisan route:list --except-vendor` passou
- `curl http://127.0.0.1:8020/health` retornou HTTP 200
- `composer test`
- `npm test --prefix src/financial-engine`

## Observação

O projeto ainda não está convertido integralmente para Laravel. O fluxo de posições estruturadas opera na base híbrida; catálogo, ledger de ações e payoff multi-expiração seguem limitados.
