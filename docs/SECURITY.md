# Segurança

## Princípios

- Sessões seguras e uso de cookies com atributos apropriados.
- Autenticação obrigatória em áreas protegidas.
- Proteção CSRF em formulários.
- Nunca versionar segredos reais.
- Hash seguro para senhas e tokens.

## Atualidade da base

A infraestrutura atual prepara a entrada para a etapa de autenticação, com `APP_ENV`, `.env.example` e estrutura de sessão pronta para evolução.

## Regras que devem ser aplicadas na Etapa 2

- Login e logout com sessão segura.
- Primeiro acesso com troca obrigatória de senha.
- Redefinição de senha com política específica.
- Logs de auditoria sem dados sensíveis.
