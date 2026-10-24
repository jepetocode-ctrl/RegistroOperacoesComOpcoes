# Próximos passos

## Prioridade 1: robustez e migração de runtime

1. Mover autenticação e sessão para Laravel nativo, sem dependência de `App\Support\SessionManager`.
2. Migrar controladores revertidos para rotas do framework sem o adaptador legado.

## Prioridade 2: consolidar o domínio financeiro

1. Integrar ou manter paridade verificável entre o payoff PHP e o motor TypeScript.
2. Ampliar o ledger para pernas de ativo-objeto antes de habilitar Collar/Fence com ações.
3. Definir regras de risco e payoff para estruturas com vencimentos diferentes sem produzir estimativas enganosas.

## Prioridade 3: ampliar experiência e catálogo

1. Adicionar validação automatizada em navegador para criação, encerramento e rolagem.
2. Ampliar templates somente com regras, limitações e testes correspondentes.
3. Melhorar tratamento de posições legadas desalinhadas, preservando ações seguras pelo ledger.

## Validação necessária antes de fechamento

- criação de modelos próprios e persistência dos slots
- registro atômico de posições estruturadas e trilha de auditoria
- encerramento parcial e rolagem em lotes-base
- identificação explícita de estruturas e payoffs não suportados
- isolamento por usuário
- testes PHP, motor TypeScript, migrações e registro de rotas

## Risco principal

A arquitetura continua híbrida. A UI e a persistência estruturada estão integradas às rotas legadas; a conversão para Laravel nativo e a paridade dos motores financeiros exigem etapas próprias.
