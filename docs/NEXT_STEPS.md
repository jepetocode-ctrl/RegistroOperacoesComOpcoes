# Próximos passos

## Etapa atual

Etapa 4 — Cadastros e banco de dados financeiro.

## Prioridade imediata

1. Executar `composer test` e `php scripts/run-migrations.php` em ambiente configurado.
2. Adicionar testes para CRUD de ativos, contratos e estratégias.
3. Testar que usuário A não consulta, altera ou referencia dados do usuário B.
4. Testar duplicidade por usuário, validação de campos, status inativo e restrições de chave estrangeira em PostgreSQL e SQLite.
5. Aplicar proteção CSRF em todos os métodos de escrita autenticados.
6. Criar a interface HTML/CSS/JavaScript para os cadastros.

## Critérios de aceitação da Etapa 4

- Registros persistidos e consultáveis.
- Relações de contratos com ativos consistentes e limitadas ao mesmo usuário.
- Isolamento de dados verificado por testes.
- Edição e desativação lógica cobertas por testes.
- Migrações executam em PostgreSQL e no fallback SQLite.
- APIs autenticadas e protegidas contra CSRF.

## Observação de continuidade

A documentação anterior indicava Etapa 2 como atual, embora já existissem fluxos de autenticação e código do motor financeiro. Consulte `docs/PROJECT_STATUS.md` para distinguir funcionalidades existentes de critérios ainda não verificados.
