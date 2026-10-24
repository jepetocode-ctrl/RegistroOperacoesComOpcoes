# Arquitetura

## Estado arquitetural atual

O projeto vive em uma fase de compatibilidade: o núcleo antigo continua funcionando e o Laravel 12 foi adicionado como bootstrap modernizador. A arquitetura real é híbrida, não totalmente migrada.

### Camadas existentes

1. Frontend legado em PHP/HTML e views em `resources/views/`
2. Controladores e serviços próprios em `app/`
3. Repositórios e acesso via PDO
4. Banco versionado por migrações em `database/migrations/`
5. Compatibilidade Laravel em `bootstrap/`, `routes/` e `artisan`
6. Motor financeiro em `src/financial-engine`

## Padrões e regras atuais

- PHP 8.3+ e Composer PSR-4
- Laravel 12 como bootstrap e camada de runtime moderno
- compatibilidade por `legacy_*` e `App\Database\LegacyMigrations`
- `strategy_legs` como tabela estrutural adicional para múltiplas pernas
- payoff determinístico centralizado no motor TypeScript

## Riscos de compatibilidade

- autenticação e sessões ainda seguem o padrão legado em alguns handlers
- rotas Laravel coexistem com controllers legados e a camada UI ainda não foi migrada em bloco
- uso de `STRATEGY_JSON` legado e da tabela `strategy_legs` exige política de escrita consistente para evitar divergência entre campos

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

Este formato deve ser a base para qualquer migração final do cadastro estratégico.

## Validação executada

- `php artisan route:list --except-vendor` passou
- `curl http://127.0.0.1:8020/health` retornou HTTP 200
- `composer test` passou
- `npm test --prefix src/financial-engine` passou

## Observação

O projeto ainda não está em estado final de conversão completa para Laravel, mas a base híbrida now está operacional e o motor financeiro já está validado em cenário determinístico.
