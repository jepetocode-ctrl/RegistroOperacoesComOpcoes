# RegistroOperacoesComOpcoes

Aplicação para cadastro e acompanhamento de operações com opções, com uma experiência de boleta multi-pernas inspirada em plataformas de trading: selecione uma estratégia, preencha contratos e preços por perna, revise débito/crédito/custos e registre a execução em conjunto.

## Registrar uma operação

1. Cadastre o ativo e os contratos ativos em `/registros`.
2. Na seção **Nova operação**, escolha uma estrutura e o ativo-objeto.
3. Informe lotes-base, contratos, preços executados, custos e horário de execução.
4. Revise a prévia. O servidor valida novamente titularidade, tipos, strikes, vencimentos e proporções.
5. A boleta só é salva se todas as pernas forem registradas em uma única transação.

As execuções aparecem agrupadas por estratégia, com fluxo inicial separado do resultado realizado e da exposição aberta. Uma operação avulsa continua disponível para uma única perna. Posições multi-pernas não podem ser alteradas usando as ações de operações avulsas.

## Domínio e limitações

- O ledger existente continua sendo a fonte das operações, custos e alocações de encerramento.
- Estratégias têm slots e restrições. Custos gerais são alocados entre slots por quantidade de contratos e multiplicadores; custos específicos continuam na perna informada.
- Operações e contratos são isolados por usuário.
- O payoff em vencimento é indisponível para estruturas não reconhecidas, vencimentos diferentes ou pernas de ativo-objeto sem suporte no ledger.
- O projeto ainda usa runtime híbrido Laravel/PHP legado. O motor TypeScript é independente; a paridade automatizada com o cálculo PHP de posições estruturadas é uma pendência conhecida.

## Requisitos e execução

- PHP 8.3+
- Composer 2+
- SQLite para desenvolvimento/testes; PostgreSQL para produção
- Node.js/npm para testar o motor financeiro

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan route:list --except-vendor
composer test
npm test --prefix src/financial-engine
```

## Documentação

- [API](docs/API.md)
- [Banco de dados](docs/DATABASE.md)
- [Regras financeiras](docs/FINANCIAL_RULES.md)
- [Arquitetura](docs/ARCHITECTURE.md)
- [Status do projeto](docs/PROJECT_STATUS.md)
