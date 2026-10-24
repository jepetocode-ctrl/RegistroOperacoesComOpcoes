# Próximos passos

## Prioridade 1: concluir migração de runtime

1. Mover autenticação e sessão para Laravel nativo, sem dependência de `App\Support\SessionManager`.
2. Migrar controladores revertidos para rotas do framework sem o adaptador legado.
3. Consolidar o cadastro de estratégias e posições no backend Laravel.

## Prioridade 2: modelar e persistir pernas

1. Realizar `INSERT` em `strategy_legs` para cada perna da estratégia.
2. Validar exclusão/integridade para estrutura não suportada.
3. Reabrir e editar estratégia via endpoint Laravel completo.

## Prioridade 3: ampliar motor financeiro

1. Implementar regras de payoff para spreads, straddles e iron condor mais robustos.
2. Definir payoffs não suportados sem resultados enganosos.
3. Integrar pontos de equilíbrio e cenário contínuo em API final.

## Prioridade 4: expor interface e validação real

1. Criar formulário de cadastro multi-perna via Laravel Blade ou Livewire.
2. Validar reabertura da posição com dados persistidos.
3. Validar UI, autenticação e edição em navegador.

## Validação necessária antes de fechamento

- cadastro de estratégia com múltiplas pernas via UI
- persistência correta de todas as pernas
- reabertura e edição da posição
- identificação explícita de estruturas não suportadas
- soma do payoff consolidado com quantidades, multiplicadores e sinais corretos
- custo/crédito inicial incorporado ao resultado líquido
- passagens de testes automatizados relevantes

## Risco principal

A arquitetura atual está híbrida e funcional em partes, mas a conversão final para Laravel com UI e persistência estruturada exige uma etapa de migração explícita, não só bootstrap e motor financeiro.
