# Banco de dados

## Motor principal

O banco principal da aplicação será PostgreSQL. A configuração de exemplo local usa o serviço `postgres` descrito em `docker-compose.yml`.

## Convenções

- Persistência em transações explícitas.
- Valores financeiros em `NUMERIC`/`DECIMAL`.
- Chaves primárias e estrangeiras sempre documentadas.
- Índices por usuário, ativo, estratégia e data.
- Migrações versionadas em `database/migrations/`.

## Estrutura inicial

As migrações iniciais incluem tabelas base para suporte do sistema, com foco em ambiente e auditoria.

Próximas migrações devem incluir:

- `users`
- `assets`
- `option_contracts`
- `strategies`
- `operations`
- `rollovers`
- `market_quotes`
- `settings`
- `audit_logs`

## Acesso

A conexão é configurada via `config/database.php` e variáveis de ambiente em `.env`.
