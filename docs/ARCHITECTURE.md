# Arquitetura

## Objetivo

Organizar a aplicação em camadas para manter a separação de responsabilidades e permitir continuidade sem reescrever módulos existentes.

## Camadas

1. Apresentação: HTML, CSS e JavaScript/TypeScript do frontend.
2. Controladores HTTP: recepção de requisições, validação básica, resposta padronizada.
3. Serviços de aplicação: regras de negócio e orquestração.
4. Repositórios: acessos ao banco por abstração.
5. Banco de dados: schema versionado e migrações.
6. Motor financeiro TypeScript: cálculos independentes da interface.
7. Integrações externas: cotações e APIs de terceiros.
8. Testes: unitários, integração e feature.

## Fluxo de execução

- Interface -> Controlador -> Serviço -> Repositório -> Banco de dados
- Interface -> Motor TypeScript -> Resultado validado

## Convenções

- PHP 8.3+.
- Composer PSR-4 com namespace `App\`.
- Banco principal PostgreSQL, com compatibilidade SQLite para desenvolvimento/testes.
- Regras financeiras permanecem fora dos controladores.
- Ledger de operações é append-only; histórico financeiro não deve ser apagado ou reescrito.
- A gravação das duas pernas de uma rolagem, alocação de encerramento, identidade da rolagem e histórico ocorre na mesma transação PDO.
- UUID identifica a rolagem; operações de encerramento e abertura mantêm IDs próprios e referência entre si.
- Fluxo de caixa líquido e custos são métricas separadas do resultado realizado.

## Padrões de erro

- Falhas de validação retornam HTTP 422.
- Requisições não autenticadas retornam HTTP 401.
- Erros internos retornam HTTP 500 com payload padronizado.

## Continuidade

Consulte `docs/PROJECT_STATUS.md` e `docs/NEXT_STEPS.md` antes de alterações estruturais. A Etapa 6 foi validada no checkout atual pela suíte PHPUnit e pela execução de migrações; o próximo foco é refinamento de concorrência, UX e regras de custo quando houver novas operações de rolagem.
