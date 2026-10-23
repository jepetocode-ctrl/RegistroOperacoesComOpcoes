# Decisões técnicas

## Arquitetura

- Projeto estruturado em camadas com responsabilidades separadas.
- PHP 8.3+ como backend e front controller por `public/index.php`.
- PostgreSQL como banco principal, com compatibilidade SQLite para desenvolvimento e testes.
- Migrações versionadas e executadas pelo script interno.

## Convenções

- Namespace `App\` para código PHP.
- PSR-4 via Composer.
- Roteamento por arrays e classes de controller.
- Respostas JSON padronizadas e códigos HTTP consistentes.

## Segurança

- `.env.example` sem segredos reais.
- Endpoints de mutação financeira exigem sessão autenticada e token CSRF.
- Dados de operação, estratégia e contratos são validados no escopo do usuário autenticado.

## Ledger e rolagens

- Operações financeiras existentes são preservadas; correções devem ser compensatórias, sem mutar o histórico.
- Cada rolagem tem UUID único e referências à operação de origem e às duas pernas executadas.
- Encerramento, alocação, abertura, registro de rolagem e histórico são gravados em uma transação única.
- Custos e fluxo líquido são armazenados separadamente.
- Crédito/débito líquido é fluxo de caixa, não lucro realizado por definição.
- Resultado realizado segue registrado nas alocações de encerramento existentes e permanece conceitualmente separado do fluxo da rolagem.

## Motor financeiro

- Integração TypeScript separada do backend e da interface.
- Núcleo financeiro independente de sessão, banco e visual.

## Validação pendente

- A implementação inicial da Etapa 6 não deve ser considerada concluída até execução de testes e migrações em SQLite e PostgreSQL.
- Revisar alocação de custos e concorrência de rolagens antes de uso financeiro real.
