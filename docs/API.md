# API

## Autenticação

Os endpoints financeiros exigem sessão autenticada. A API responde JSON; chamadas de escrita devem enviar o token CSRF da sessão conforme o mecanismo existente de autenticação.

## Cadastros financeiros

Recursos: `assets`, `option-contracts` e `strategies`.

- `GET /api/{recurso}`: lista somente registros do usuário autenticado.
- `GET /api/{recurso}/{id}`: consulta registro pertencente ao usuário.
- `POST /api/{recurso}`: cria registro.
- `PUT /api/{recurso}/{id}` ou `PATCH /api/{recurso}/{id}`: atualiza registro.
- Para desativar, atualize `status` para `INACTIVE`; para reativar, use `ACTIVE`.

Ativos: `symbol`, `name`, opcional `asset_type` e `status`.
Contratos: `asset_id`, `code`, `option_type` (`CALL` ou `PUT`), `strike_price`, `expiration_date` (AAAA-MM-DD), opcional `contract_multiplier` e `status`.
Estratégias: `name`, opcional `description`, `legs` (lista JSON) e `status`.

## Respostas

Sucesso: `{"success":true,"data":{}}`. Listas retornam `data` como lista. Falhas de validação retornam HTTP 422; sessão ausente retorna 401; registro alheio ou inexistente retorna 404. Respostas de erro não expõem detalhes internos.

Contratos referenciam ativos do mesmo usuário. Símbolos de ativos e códigos de contratos são únicos por usuário. Registros pertencentes a outros usuários não são retornados nem alterados.
