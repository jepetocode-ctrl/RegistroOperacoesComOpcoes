# Status do projeto

## Etapa atual

Etapa 4 — Cadastros e banco de dados financeiro: implementação inicial realizada; validação automatizada ainda pendente.

## Última etapa concluída

Etapa 3 — motor financeiro TypeScript presente no repositório com testes unitários. A documentação anterior estava desatualizada em relação à autenticação existente no código; autenticação possui controladores, sessão e testes, mas requer revisão integral dos critérios de segurança antes de ser declarada concluída.

## Implementado nesta etapa

- Migração para ativos, contratos de opções e estratégias.
- Repositório com consultas e mutações sempre escopadas por usuário.
- Serviço de aplicação e validações de payload.
- Endpoints JSON para listar, consultar, criar e atualizar cadastros.
- Desativação/reativação lógica por status.
- Verificação de que um contrato só referencia ativo pertencente ao mesmo usuário.
- Documentação inicial dos endpoints.

## Verificação

Não foi possível executar PHPUnit, migrações ou testes HTTP nesta sessão: o acesso disponível permitiu inspecionar e alterar arquivos no GitHub, mas não disponibilizou um checkout executável do projeto. Nenhum teste é declarado como aprovado nesta alteração.

## Riscos e pendências conhecidas

- Revisar proteção CSRF para métodos de escrita na API de cadastros.
- Executar migrações e testes em SQLite e PostgreSQL.
- Adicionar testes de integração para isolamento entre usuários, constraints, edição e desativação.
- Rever a ativação de foreign keys no SQLite e tratamento de datas/JSON entre drivers.
- A interface de cadastros ainda não foi implementada.
- Verificar que o middleware de autenticação bloqueia corretamente usuários obrigados a trocar senha.

## Próximo passo recomendado

Adicionar testes automatizados de repositório/serviço/API, executar toda a suíte em ambiente com dependências instaladas e corrigir eventuais diferenças de driver antes de considerar a Etapa 4 concluída.
