# RegistroOperacoesComOpcoes

Sistema para registro das operações realizadas com opções na bolsa de valores, além de realizar simulações para preços alvos e apuração de lucros ou prejuízos.

## Visão geral

Este projeto foi estruturado para evoluir em etapas, preservando separação entre apresentação, controladores HTTP, serviços de aplicação, regras de negócio, repositórios, banco de dados, motor financeiro TypeScript, testes e documentação.

## Etapas implementadas

- Etapa 0: planejamento e documentação.
- Etapa 1: fundação técnica, configuração, migrações e inicialização local.

## Estrutura principal

```text
.
├── app/
├── config/
├── database/
├── docs/
├── public/
├── resources/
├── routes/
├── src/
├── storage/
├── tests/
├── .env.example
├── .gitignore
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── package.json
├── phpunit.xml
├── README.md
└── ...
```

## Requisitos

- PHP 8.3+
- Composer 2+
- Docker + Docker Compose (opcional para banco PostgreSQL local)
- PostgreSQL 16 (via container local recomendado)

> Nota: no ambiente atual, a extensão PDO PostgreSQL não está habilitada no runtime local. O projeto usa fallback para SQLite em desenvolvimento para permitir a aplicação iniciar e os testes executarem sem quebrar a arquitetura principal baseada em PostgreSQL.

## Instalação

```bash
composer install
cp .env.example .env
```

Se for usar o banco local em container:

```bash
docker compose up -d postgres
```

Para usar PostgreSQL explicitamente no ambiente com a extensão habilitada, ajuste `DB_CONNECTION=pgsql` em `.env`.

## Execução local

```bash
php -S 0.0.0.0:8000 -t public
```

Acesse:

- http://localhost:8000/
- http://localhost:8000/health

## Testes

```bash
composer test
```

## Trabalho em andamento

A próxima etapa é a autenticação e segurança, conforme o plano do projeto.

## Continuidade

A documentação em `docs/` deve ser consultada antes de qualquer alteração estrutural.
