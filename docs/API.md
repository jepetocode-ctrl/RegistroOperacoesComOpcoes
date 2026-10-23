# API

## Visão geral

A API segue um padrão JSON e separa rotas públicas e protegidas. A autenticação ainda será implementada na Etapa 2.

## Endpoints iniciais

- `GET /health`
- `GET /api/status`

## Padrões de resposta

Sucesso:

```json
{
  "success": true,
  "data": {}
}
```

Erro:

```json
{
  "success": false,
  "message": "Descrição do erro"
}
```

## Proximidade com o escopo completo

Os endpoints financeiros e de autenticação serão adicionados conforme a etapa correspondente.
