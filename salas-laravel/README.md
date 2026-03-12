# Salas UniSenac – Laravel

Versão Laravel do sistema, desenvolvida em paralelo ao backend PHP atual. O sistema em produção continua em `../backend` até a troca.

## Requisitos

- PHP 8.1+
- Composer
- MySQL/MariaDB (mesmo banco do sistema atual ou novo)

## Configuração

1. Copie o `.env.example` para `.env` (já feito na instalação).
2. Ajuste o banco no `.env`:
   - **Banco novo (desenvolvimento):** use `DB_DATABASE=start_unisenac` (ou outro nome) e crie o banco. Ao rodar `php artisan migrate`, todas as tabelas serão criadas.
   - **Banco existente (mesmo do PHP atual):** use as mesmas credenciais do projeto principal (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Ao rodar `php artisan migrate`, as migrations que já existem no banco serão ignoradas e apenas a coluna `remember_token` será adicionada na tabela `users`, se ainda não existir.
3. Para subpasta no servidor (ex.: `/salas`), configure `APP_URL=https://seusite.com/salas`.

## Migrations

As migrations em `database/migrations/2025_02_27_*` replicam o schema do `backend/schema.sql`:

- `coordinators` → `users` → `courses` → `teachers` → `disciplines` → `course_offerings`
- Se a tabela já existir, a migration correspondente não faz nada (uso do banco atual).
- A migration `2025_02_27_000007_add_remember_token_to_users_table` adiciona a coluna que o Laravel Auth usa na tabela `users`.

Comando:

```bash
php artisan migrate
```

## Models

- `App\Models\User` – autenticação com `password_hash` e `getAuthPassword()`
- `App\Models\Coordinator`, `Course`, `Discipline`, `Teacher`, `CourseOffering` – relacionamentos conforme o schema

## Próximos passos (a implementar)

- Auth (login, middleware, “coordenador só vê seus cursos”)
- Rotas e controllers do admin (cursos, ofertas, disciplinas, coordenadores)
- **Kiosk:** `GET /kiosk` — Blade com cursos/disciplinas por turno e dia (timezone Brasil); recarrega a cada 10 min. Sem API.

## Rodar em desenvolvimento

```bash
php artisan serve
```

Para testar em subpasta (ex.: `/salas`):

```bash
php artisan serve --path=salas
```

Ou use o servidor web apontando o document root para `salas-laravel/public`.
