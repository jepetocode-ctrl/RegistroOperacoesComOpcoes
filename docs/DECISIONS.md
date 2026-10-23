# Decisões técnicas

## Arquitetura

- Projeto estruturado em camadas com responsabilidades separadas.
- PHP 8.3+ como backend e front controller por `public/index.php`.
- PostgreSQL como banco principal, com compatibilidade em arquitetura e estrutura. 

## Convenções

- Namespace `App\` para código PHP.
- PSR-4 via Composer.
- Roteamento simples por arrays e controller classes.
- Migrações versionadas e executadas por script interno.

## Tratamento de erros

- Respostas JSON padronizadas em API.
- Códigos HTTP consistentes: 200, 401, 404, 422 e 500.

## Segurança

- `.env.example` sem segredos reais.
- Sessão e senha serão tratadas em etapa específica.

## Motor financeiro

- Integração em TypeScript separada do backend e da interface.
- Núcleo financeiro deve ser independente de sessão, banco e visual.
