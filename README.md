# RegistroOperacoesComOpcoes

Aplicação para registrar, acompanhar e analisar operações com opções.

## Estado da aplicação

O projeto mantém um runtime híbrido: Laravel 12 inicializa a aplicação e suas migrações, enquanto rotas, controladores, sessão e persistência financeira existentes continuam usando a camada legada em PHP/PDO. O fluxo de posições estruturadas integra modelos, pernas e operações avulsas sem substituir o ledger financeiro.

### Posições estruturadas

- Catálogo inicial e modelos próprios com slots, proporções e restrições; modelos legados são convertidos sem descartar o JSON original e marcados para revisão quando necessário.
- Builder em `/registros` para criar, clonar e editar modelos, selecionar contratos por perna e visualizar fluxo/custos antes do registro.
- Registro atômico de todas as pernas, titularidade de ativo/contratos, classificação declarada versus inferida, auditoria e compatibilidade com o ledger atual.
- Dashboard em `/dashboard` com posições agrupadas, pernas, fluxo inicial, resultados, exposição no vencimento e ações de encerramento/rolagem em lotes-base.
- Operações avulsas seguem em área separada. Operações de uma posição estruturada não podem ser editadas ou encerradas por um formulário avulso.
- O catálogo oferece suporte de payoff para opções simples, spreads verticais, straddles/strangles, butterflies e iron condor. Outros modelos podem ser registrados, mas são identificados como não reconhecidos ou sem payoff suportado quando aplicável.
- Ativos-objeto ainda não podem compor o ledger como pernas de `ACTION`; templates dependentes de ações permanecem indisponíveis. Payoff com vencimentos diferentes não é calculado.

O motor financeiro TypeScript continua disponível em `src/financial-engine` e possui testes próprios. O payoff de posições estruturadas é atualmente calculado por uma implementação PHP; paridade completa entre os dois motores e a migração do runtime para Laravel nativo permanecem trabalho futuro.

## Requisitos e execução

- PHP 8.3+
- Composer 2+
- SQLite para desenvolvimento/testes; PostgreSQL também é suportado pelo projeto
- Node.js/npm para compilar e testar `src/financial-engine`

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan route:list --except-vendor
composer test
npm test --prefix src/financial-engine
```

## Documentação

- [API](docs/API.md)
- [Banco de dados](docs/DATABASE.md)
- [Regras financeiras](docs/FINANCIAL_RULES.md)
- [Status do projeto](docs/PROJECT_STATUS.md)
- [Próximos passos](docs/NEXT_STEPS.md)
