# Próximos passos

## Etapa atual

Etapa 2 - Autenticação e segurança.

## Próxima etapa recomendada

Concluir autenticação, login, sessão, troca obrigatória de senha e proteção de rotas.

## Tarefas pendentes

1. Criar tela de login e fluxo de autenticação.
2. Implementar sessão segura com cookie com atributos apropriados.
3. Provisionar usuário administrador inicial.
4. Garantir troca obrigatória da senha no primeiro acesso.
5. Implementar alteração e logout.
6. Adicionar proteção CSRF e controle de permissões.
7. Registrar logs de auditoria sem dados sensíveis.
8. Validar rotas protegidas e APIs restritas.

## Dependências

- Estrutura base pronta.
- Banco configurado.
- Migração inicial executando corretamente.
- Ambiente local operacional com fallback SQLite ou driver PostgreSQL habilitado.

## Critérios de aceitação

- Usuários não autenticados não acessam páginas protegidas.
- Usuário administrador inicial só funciona com política de primeiro acesso.
- Senha salva como hash seguro.
- Alteração de senha valida senha atual.
- Rotas e APIs protegidas verificam autorização no servidor.

## Riscos conhecidos

- Repositório ainda sem autenticação implementada.
- Banco deve estar disponível localmente antes dos testes de sessão.
- Em ambientes sem PDO PostgreSQL, é necessário ativar a extensão ou manter o fallback SQLite apenas para desenvolvimento.
