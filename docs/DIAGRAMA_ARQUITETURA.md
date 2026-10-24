# Diagrama de arquitetura

Este diagrama representa o fluxo observado no checkout atual. As rotas Laravel encaminham as requisições para os controladores PHP existentes por meio de `LegacyRouteBridge`; o acesso aos dados de negócio continua passando pelos serviços e repositórios PDO.

## Visão geral

```mermaid
flowchart LR
    browser["Navegador"]
    entry["public/index.php<br/>front controller"]
    laravel["Laravel 12<br/>bootstrap/app.php"]
    routes["routes/web.php<br/>routes/api.php"]
    bridge["LegacyRouteBridge"]
    controllers["Controladores PHP<br/>app/Controllers"]
    middleware["Autenticação e CSRF<br/>app/Middleware"]
    services["Serviços<br/>app/Services"]
    validators["Validadores<br/>app/Validators"]
    repositories["Repositórios<br/>app/Repositories"]
    pdo["Conexão PDO<br/>app/Support/Database.php"]
    db[("PostgreSQL<br/>ou SQLite local/testes")]
    views["Views PHP<br/>resources/views"]
    engine["Motor financeiro TypeScript<br/>src/financial-engine"]
    engineTests["Testes do motor<br/>src/financial-engine/tests"]
    phpTests["Testes de funcionalidades<br/>tests/Feature"]
    migrations["Migrações<br/>database/migrations"]

    browser -->|"HTTP: páginas e formulários"| entry
    browser -->|"HTTP: chamadas /api/*"| entry
    entry --> laravel --> routes --> bridge --> controllers
    controllers --> middleware
    controllers --> services
    controllers --> validators
    controllers --> views -->|"HTML"| browser
    services --> repositories --> pdo --> db
    migrations -.->|"criam/evoluem o schema"| db
    phpTests -.-> controllers
    phpTests -.-> services

    engine --> engineTests
```

## Fluxo de uma chamada da dashboard à API

```mermaid
sequenceDiagram
    actor Pessoa
    participant Browser as Navegador
    participant Laravel as Laravel / rotas
    participant Bridge as LegacyRouteBridge
    participant Controller as Controller PHP
    participant Service as Service
    participant Repository as Repository / PDO
    participant DB as PostgreSQL ou SQLite

    Pessoa->>Browser: Abre /dashboard
    Browser->>Laravel: GET /dashboard
    Laravel->>Bridge: Encaminha rota web
    Bridge->>Controller: DashboardController::index()
    Controller-->>Browser: HTML da dashboard
    Browser->>Laravel: GET /api/positions (sessão do navegador)
    Laravel->>Bridge: Encaminha rota de API
    Bridge->>Controller: OperationController::handle()
    Controller->>Controller: Verifica autenticação
    Controller->>Service: Consulta posições do usuário
    Service->>Repository: Lê operações e posições
    Repository->>DB: Consulta via PDO
    DB-->>Repository: Registros
    Repository-->>Service: Dados
    Service-->>Controller: Resultado
    Controller-->>Browser: JSON
```

## Limites e observações

- As rotas declaradas em `routes/web.php` e `routes/api.php` chamam `LegacyRouteBridge`, que despacha os handlers de `routes/legacy_web.php` e `routes/legacy_api.php`.
- Autenticação e proteção CSRF são verificadas pelos controladores/middlewares PHP existentes. A sessão do navegador é usada nas páginas e nas chamadas à API.
- O banco principal configurado é PostgreSQL; SQLite é usado como alternativa local/teste conforme a configuração disponível.
- O motor TypeScript contém funções financeiras independentes e testes próprios. Este diagrama não presume que a dashboard ou os serviços PHP já invoquem diretamente esse pacote.
- A presença de rotas ou componentes no diagrama não garante que integrações externas ou funcionalidades não descritas estejam implementadas.

Para mais contexto, consulte [ARCHITECTURE.md](./ARCHITECTURE.md), [API.md](./API.md) e [DATABASE.md](./DATABASE.md).
