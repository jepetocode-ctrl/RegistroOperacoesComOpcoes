# Arquitetura

## Objetivo

Organizar a aplicação em camadas para manter a separação de responsabilidades e permitir continuidade por diferentes IAs sem reescrever módulos existentes.

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
- Banco principal PostgreSQL, sem usar recursos exclusivos em excesso.
- Todas as regras financeiras permanecem fora dos controladores.
- O código executável não deve ser substituído por pseudocódigo.

## Padrões de erro

- Falhas de validação retornam HTTP 422.
- Requisições não autenticadas retornam HTTP 401.
- Erros internos retornam HTTP 500 com payload padronizado.

## Continuidade

Os arquivos de documentação, configuração e estrutura desta pasta devem ser usados como referência para a próxima etapa.
