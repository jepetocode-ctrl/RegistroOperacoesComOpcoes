# RegistroOperacoesComOpcoes

Sistema para registro das operações realizadas com opções na bolsa de valores, além de realizar simulações para preços alvos e apuração de lucros ou prejuízos.

## Visão geral

Este projeto evolui por etapas, preservando a separação entre apresentação, controladores HTTP, serviços de aplicação, repositórios, banco de dados, motor financeiro TypeScript, testes e documentação.

## Etapas registradas

- Etapa 0: planejamento e documentação.
- Etapa 1: fundação técnica, configuração, migrações e inicialização local.
- Etapa 5: operações e posições validadas pela suíte PHPUnit e migrações.
- Etapa 6: rolagens validadas pela suíte PHPUnit e migrações. A transação de encerramento + abertura + histórico registrado foi verificada no checkout atual.

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
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── package.json
├── phpunit.xml
└── README.md
```

## Requisitos

- PHP 8.3+
- Composer 2+
- Docker + Docker Compose (opcional para PostgreSQL local)
- PostgreSQL 16

O ambiente local pode usar SQLite como fallback quando PDO PostgreSQL não estiver disponível. PostgreSQL continua sendo o banco principal.

## Instalação e execução

```bash
composer install
cp .env.example .env
composer test
php scripts/run-migrations.php
php -S 0.0.0.0:8000 -t public
```

## API de rolagens

- `POST /api/rollovers`: registra encerramento e abertura numa única transação. Requer sessão autenticada e token CSRF.
- `GET /api/rollovers`: lista o histórico de rolagens do usuário autenticado.
- `GET /api/rollovers/{uuid}`: consulta uma rolagem pelo identificador único.

O registro guarda as operações de origem e as duas pernas, estratégia, custos e fluxo líquido. Crédito/débito líquido representa fluxo de caixa e não é classificado automaticamente como lucro.

## Verificação atual

Comando executado no checkout atual:

```bash
cd /workspaces/RegistroOperacoesComOpcoes && composer test && php scripts/run-migrations.php
```

Resultado confirmado:

- `OK (10 tests, 36 assertions)`
- Migrações executadas com sucesso até `20261023_000004_create_rollovers.php`

## Continuidade

Consulte `docs/ARCHITECTURE.md`, `docs/PROJECT_STATUS.md`, `docs/NEXT_STEPS.md` e `docs/DECISIONS.md` antes de mudanças estruturais. A Etapa 6 está validada no código atual; os próximos esforços continuam focados em concorrência, interface e refinamentos de regras financeiras.
