# Status do projeto

## Etapa atual

Etapa 2 - Autenticação e segurança em andamento.

## Última etapa concluída

Etapa 1: bootstrap, configuração, migração base e verificação de inicialização local.

## Funcionalidades implementadas

- Estrutura base do projeto em camadas.
- Configuração do Composer e do PHPUnit.
- Documentação inicial em `docs/`.
- Arquivos de ambiente com `.env.example` e `.env` locais.
- Front controller e roteamento mínimo.
- Arquitetura inicial e migração base do banco.
- Docker Compose para PostgreSQL local.
- Verificação de boot da aplicação e endpoints HTTP.

## Funcionalidades incompletas

- Autenticação e autorização.
- Módulos financeiros completos.
- Cadastros e operações.
- Dashboard e interfaces de negócio.

## Testes executados

- `./vendor/bin/phpunit --colors=never` -> OK (1 teste, 1 assertion).
- `curl -sS -D - http://127.0.0.1:8000/health` -> HTTP 200 OK.
- `curl -sS http://127.0.0.1:8000/` -> homepage entregue com sucesso.

## Falhas conhecidas

- A instalação local atual não possui o driver PDO PostgreSQL habilitado; o sistema usa fallback para SQLite em desenvolvimento, mantendo a arquitetura principal em PostgreSQL.
- Ainda não há autenticação funcional.
- O motor financeiro TypeScript ainda não foi implementado.

## Arquivos principais alterados

- `README.md`
- `composer.json`
- `docker-compose.yml`
- `config/app.php`
- `config/database.php`
- `public/index.php`
- `routes/web.php`
- `scripts/run-migrations.php`
- `database/migrations/20261023_000001_create_app_base_tables.php`

## Migrações aplicadas

- `20261023_000001_create_app_base_tables`

## Dependências adicionadas

- `phpunit/phpunit`

## Próxima etapa recomendada

Etapa 2 - Autenticação e segurança.
