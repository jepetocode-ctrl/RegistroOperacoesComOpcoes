# Manual de configuração do PostgreSQL

Este manual descreve como configurar a conexão PostgreSQL no projeto **RegistroOperacoesComOpcoes**.

## 1. Onde alterar os dados de conexão

O arquivo principal de configuração por ambiente é **`.env`**, na raiz do projeto. Se ele ainda não existir, copie o modelo:

```bash
cp .env.example .env
```

Edite as variáveis abaixo:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=registro_opcoes
DB_USERNAME=postgres
DB_PASSWORD=troque_esta_senha
```

| Variável | Finalidade | Exemplo |
|---|---|---|
| `DB_CONNECTION` | Driver selecionado pela aplicação | `pgsql` |
| `DB_HOST` | Endereço/IP do servidor PostgreSQL | `127.0.0.1` ou nome do serviço Docker |
| `DB_PORT` | Porta do PostgreSQL | `5432` |
| `DB_DATABASE` | Nome do banco de dados | `registro_opcoes` |
| `DB_USERNAME` | Usuário de conexão | `postgres` |
| `DB_PASSWORD` | Senha do usuário | definida no ambiente |
| `DB_URL` | URL de conexão, se suportada pela implementação utilizada | não é consumida pelo conector PDO customizado atual |
| `DB_SSLMODE` | Preferência de SSL configurada em `config/database.php` | `prefer` |

**Importante sobre URL:** embora `DB_URL` e `DB_SSLMODE` estejam declaradas em `config/database.php`, a conexão PostgreSQL implementada atualmente em `app/Support/Database.php` monta o DSN usando `DB_HOST`, `DB_PORT` e `DB_DATABASE`, e passa usuário/senha separadamente ao PDO. Ela não utiliza `DB_URL` nem aplica `DB_SSLMODE` nesse DSN. Para alterar a conexão com o código atual, configure as variáveis individuais acima. Para usar uma URL ou exigir SSL, será necessário adaptar o código de conexão e testar a mudança.

## 2. Como o projeto lê a configuração

- **`.env`**: contém os valores específicos do ambiente; não deve ser versionado com credenciais reais.
- **`config/database.php`**: lê as variáveis `DB_*` e define os perfis `pgsql` e `sqlite`, além do driver padrão.
- **`app/Support/Database.php`**: cria a conexão PDO. Para PostgreSQL, usa host, porta, nome do banco, usuário e senha.
- **`docker-compose.yml`**: define os valores do contêiner PostgreSQL local. Se mudar as credenciais desse serviço, mantenha os valores consistentes com `.env`.

O projeto pode usar SQLite para desenvolvimento/testes. Para selecionar PostgreSQL, defina explicitamente `DB_CONNECTION=pgsql`.

## 3. Subir o PostgreSQL com Docker Compose

O arquivo `docker-compose.yml` já define um serviço PostgreSQL na porta `5432`, com banco `registro_opcoes` e usuário `postgres`. Para iniciar:

```bash
docker compose up -d postgres
docker compose ps
docker compose logs postgres
```

No exemplo versionado, a senha do contêiner está definida como `postgres`. Para uso compartilhado ou produção, substitua essa senha por um segredo forte e não reutilize credenciais de exemplo.

**Atenção ao host:** se a aplicação roda diretamente na máquina, normalmente use `DB_HOST=127.0.0.1`. Se roda em outro contêiner na mesma rede do Compose, use o nome do serviço, por exemplo `DB_HOST=postgres`. Dentro de um contêiner, `127.0.0.1` aponta para o próprio contêiner, não para o serviço PostgreSQL.

## 4. Verificar os requisitos PHP

A conexão usa PDO. Confirme que o PHP possui o driver PostgreSQL `pdo_pgsql` habilitado:

```bash
php -m
```

Na lista de módulos, procure `PDO` e `pdo_pgsql`. Se `pdo_pgsql` estiver ausente, instale/habilite a extensão compatível com a versão do PHP e reinicie o processo PHP. A implementação atual pode trocar silenciosamente para SQLite quando o driver PostgreSQL não está disponível; por isso, confirme o driver antes de considerar a configuração validada.

## 5. Criar/atualizar as tabelas

Depois de configurar o PostgreSQL e confirmar o driver, execute as migrações conforme o fluxo do projeto:

```bash
php artisan migrate --force
```

Execute esse comando somente depois de conferir que `DB_CONNECTION=pgsql` e que os demais valores apontam para o ambiente correto. Em produção, faça backup e valide o plano de migração antes de aplicar alterações no banco.

## 6. Diagnóstico de problemas comuns

- **Connection refused / conexão recusada:** confirme se o serviço está ativo, se a porta está publicada e se `DB_HOST` está correto.
- **Password authentication failed:** verifique `DB_USERNAME` e `DB_PASSWORD`; confirme também as credenciais definidas no serviço PostgreSQL.
- **Database does not exist:** confirme `DB_DATABASE` e crie o banco antes de executar as migrações.
- **could not find driver:** habilite/instale `pdo_pgsql` na mesma instalação PHP usada para executar a aplicação.
- **A aplicação continua usando SQLite:** confira `DB_CONNECTION=pgsql`, a disponibilidade de `pdo_pgsql` e o carregamento das variáveis de ambiente pelo processo que executa a aplicação.

## 7. Segurança

- Não envie o arquivo `.env` com senhas reais ao GitHub.
- Use credenciais diferentes para desenvolvimento, homologação e produção.
- Conceda ao usuário da aplicação somente os privilégios necessários.
- Guarde segredos de produção em um gerenciador de segredos ou nas variáveis seguras da plataforma de implantação.
- Se uma senha real for publicada acidentalmente, rotacione-a; apagar o arquivo do último commit não elimina necessariamente o histórico.

## Referências do próprio projeto

- [Modelo de variáveis de ambiente](../.env.example)
- [Configuração de banco](../config/database.php)
- [Implementação PDO](../app/Support/Database.php)
- [Docker Compose](../docker-compose.yml)
- [Documentação do banco](DATABASE.md)
