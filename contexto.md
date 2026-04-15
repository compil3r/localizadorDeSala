# Contexto do projeto (localizadorDeSala / salas-laravel)

Este arquivo consolida **o que foi implementado** na árvore de trabalho atual (incluindo alterações **ainda não commitadas**) e serve como **referência para migração** da versão que está no servidor para esta versão.

> **Nota sobre o estado do Git:** no momento da geração deste documento, o `git status` mostrava muitos arquivos do backend PHP legado (`backend/`, `data/`) como **removidos** no working tree, além de diversas mudanças e arquivos novos em `salas-laravel/`. Antes de migrar para produção, confirme se a remoção do legado é intencional e se o deploy deve ou não incluir esses deletes.

---

## 1. Visão geral

- O painel administrativo está em **Laravel** (`salas-laravel/`).
- Foi evoluída a área de **matriz curricular** (`curriculum_matrix`), com UI em abas, edição inline via modal, e **filtro por curso selecionado** (sessão).
- Foi introduzido um **seletor de curso no topo** do layout admin (quando o usuário tem mais de um curso), com troca via **POST** e persistência em **sessão**.
- O menu **“Oferta 2026/1”** passa a apontar para as **ofertas do curso atualmente selecionado** (quando existe `currentCourse` na sessão/middleware).
- Existem **comandos Artisan** para importar/verificar matriz a partir de CSV e para “bootstrap” a partir de ofertas.
- Há um módulo **FIC** (rotas sob `admin/fic`, middleware `admin`) com controllers/views/migrations/seed não listados aqui em detalhe, mas presentes no repositório.

---

## 2. Funcionalidades implementadas (detalhamento)

### 2.1 Matriz curricular (`/admin/disciplines`)

- **Fonte de dados:** tabela `curriculum_matrix` + joins em `courses` e `disciplines` (inclui curso “mãe” via `disciplines.owning_course_id`).
- **Classificação de tipo (PRÓPRIA / COMPARTILHADA / OPTATIVA):** calculada no controller a partir de `is_optional` e comparação do curso da linha com o curso mãe da disciplina.
- **Aba “Por semestre”:** listagem em **uma tabela por curso**, colunas **Sem.**, **Disciplina**, **Tipo**, **Mãe**, **Editar**. Optativas (`is_optional = 1`) **não aparecem** nesta aba.
- **Aba “Optativas”:** agrupa por curso quando necessário; colunas **Sem.**, **Disciplina**, **Mãe**, **Editar** (sem coluna “Curso” na tabela; o nome do curso pode aparecer no cabeçalho do bloco quando há mais de um curso no contexto).
- **Edição da matriz:** modal com `course_semester` e checkbox `is_optional`; submit **PUT** em `admin.curriculum-matrix.update`.
- **Cabeçalho da página:** título **“Matriz curricular”** mantido; subtítulo em cinza com **nome do curso selecionado** (via `currentCourse` injetado pelo middleware).
- **Estilo “slim”:** classes `matrix-slim` + CSS dedicado em `public/css/admin.css`.
- **Cores por bloco de semestre:** alternância ao mudar o valor de `course_semester` na ordenação; cinza **bem sutil** aplicado em `tr` e `td` (para não ser sobrescrito pelo Bootstrap).

### 2.2 Catálogo de disciplinas (`/admin/disciplines/catalog`)

- Tela para edição de disciplinas (nome e `owning_course_id`), com modal.
- Continua existindo no backend; o link no menu principal pode não estar visível em todas as telas (depende do layout), mas a rota permanece.

### 2.3 Curso selecionado (contexto global)

- **Sessão:** chave `current_course_id`.
- **Middleware:** `App\Http\Middleware\SetCurrentCourse` (alias `current.course` no `Kernel.php`).
  - Define o curso atual como o primeiro curso permitido quando a sessão está vazia ou inválida.
  - **Coordenador:** cursos onde `courses.coordinator_id` coincide com o do usuário.
  - **Admin:** todos os cursos (ordenados por código).
  - Injeta em `request()->attributes`: `allowedCourses`, `currentCourse`.
- **Troca de curso:** `POST /admin/switch-course` → `CourseSwitchController@switch` (`admin.switch-course`).
  - Valida `course_id` e permissões.
  - Redireciona mantendo contexto quando possível (ex.: `admin.offerings.index`, `admin.disciplines.index`, `admin.disciplines.catalog`, `admin.courses.index`).
- **Grupo de rotas admin:** `Route::middleware(['auth', 'current.course'])->prefix('admin')...` em `routes/web.php`.

### 2.4 Layout admin (`resources/views/layouts/admin.blade.php`)

- Seletor de curso no topo quando `allowedCourses->count() > 1`.
- **Sem botão:** o `<select>` usa `onchange="this.form.submit()"`.
- **Alinhamento à direita:** CSS em `.course-switcher` / `.course-switcher-form`.
- **Seta do select:** evitar `background: transparent` no shorthand (usa-se `background-color: transparent` para não apagar a `background-image` do `form-select` do Bootstrap).

### 2.5 FIC (admin)

- Rotas em `routes/web.php`: prefixo `admin/fic`, middleware `['auth','admin']`.
- CRUD de áreas, cursos e sessões (controllers em `Admin/Fic*Controller.php`, views em `resources/views/admin/fic/`, migration `2026_04_14_000001_create_fic_tables.php`, seeder `FicGastronomiaSeeder.php`).

### 2.6 Comandos Artisan (matrizes)

| Comando | Arquivo | Função (resumo) |
|--------|---------|-------------------|
| `matrizes:import` | `ImportMatrizesCsvCommand.php` | Importa CSV (`--file`, `--dry-run`, `--create-missing-disciplines`). |
| `matrizes:verify` | `VerifyMatrizesCsvCommand.php` | Verifica CSV (`--file`, `--output`). |
| `matrizes:bootstrap-from-offerings` | `BootstrapCurriculumFromOfferingsCommand.php` | Preenche/atualiza matriz a partir de ofertas (assinatura multi-linha no código). |

Arquivo de exemplo na raiz do repo: `matrizes.csv` (não versionado obrigatoriamente; ajuste caminho no deploy).

### 2.7 Modelos e migrations relevantes (novos / não commitados)

- `CurriculumMatrix` + migrations `2026_03_18_000006_create_curriculum_matrix_table.php` (e relacionadas: students, student_course_remaining).
- Ajustes em `Course`, `Discipline`, `DatabaseSeeder.php` (conferir diff no deploy).

### 2.8 Kiosk

- Alterações em `KioskController`, `kiosk.css`, `kiosk/index.blade.php` (detalhes no diff; incluir na revisão de deploy se o kiosk for publicado junto).

---

## 3. Arquivos e rotas que mais importam para suporte

### 3.1 Backend (Laravel)

| Área | Arquivo |
|------|---------|
| Matriz + catálogo + update matrix | `salas-laravel/app/Http/Controllers/Admin/DisciplineController.php` |
| Troca de curso | `salas-laravel/app/Http/Controllers/Admin/CourseSwitchController.php` |
| Middleware curso atual | `salas-laravel/app/Http/Middleware/SetCurrentCourse.php` |
| Alias middleware | `salas-laravel/app/Http/Kernel.php` |
| Rotas | `salas-laravel/routes/web.php` |

### 3.2 Frontend (admin)

| Área | Arquivo |
|------|---------|
| Layout + select curso | `salas-laravel/resources/views/layouts/admin.blade.php` |
| Matriz (abas, modal, tabelas) | `salas-laravel/resources/views/admin/disciplines/index.blade.php` |
| Catálogo UCs | `salas-laravel/resources/views/admin/discipline_catalog/index.blade.php` |
| Estilos | `salas-laravel/public/css/admin.css` |

### 3.3 Rotas nomeadas úteis

- `admin.switch-course` — `POST /admin/switch-course`
- `admin.disciplines.index` — `GET /admin/disciplines`
- `admin.disciplines.catalog` — `GET /admin/disciplines/catalog`
- `admin.disciplines.update` — `PUT /admin/disciplines/{discipline}`
- `admin.curriculum-matrix.update` — `PUT /admin/curriculum-matrix/{curriculum_matrix}`
- `admin.offerings.index` — `GET /admin/courses/{course}/offerings`
- `admin.fic.*` — ver `routes/web.php`

---

## 4. Guia de migração: servidor (última versão em produção) → esta versão

Use este roteiro como **checklist**. Ajuste caminhos, usuário do sistema e stack (nginx/apache, php-fpm) ao ambiente real.

### 4.1 Antes de começar

1. **Identifique a versão no servidor:** commit/tag ou cópia de arquivos atual.
2. **Defina janela de manutenção** se houver mudança de schema ou downtime.
3. **Confirme o alvo do deploy:** só `salas-laravel/` ou o repositório inteiro. **Atenção:** o working tree atual pode incluir **remoção do backend PHP** (`backend/`, `data/`); não aplique isso em produção sem querer.

### 4.2 Backup obrigatório

1. **Banco de dados:** dump completo (mysqldump ou ferramenta equivalente).
2. **Arquivos:** backup do diretório da aplicação no servidor (especialmente `.env`, `storage/`, `bootstrap/cache/`).

### 4.3 Publicar o código

1. No servidor, no diretório da aplicação:
   - `git fetch` e checkout do **branch/commit** desejado **ou**
   - upload do pacote gerado na CI.
2. Garanta que **todos** os arquivos novos foram copiados (middleware, controllers, migrations, views, `routes/web.php`, etc.).

### 4.4 Dependências PHP

No diretório `salas-laravel/`:

```bash
composer install --no-dev --optimize-autoloader
```

(Em ambiente de staging pode usar `--dev` para testes.)

### 4.5 Variáveis de ambiente (`.env`)

1. Compare o `.env` de produção com o da nova versão (novas chaves podem existir).
2. Confirme: `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, credenciais de banco, `SESSION_*`, mail se usado.

### 4.6 Migrações de banco

No servidor, em `salas-laravel/`:

```bash
php artisan migrate --force
```

- Se alguma migration falhar por FK/tipo de coluna (ex.: `BIGINT` vs `INT UNSIGNED`), **pare**, restaure o backup se necessário, e alinhe o schema com o esperado pelas migrations antes de prosseguir.
- Revise as migrations novas listadas na seção 2.7 (e `2026_04_14_000001_create_fic_tables.php` se o módulo FIC for para produção).

### 4.7 Seeders (somente se fizer parte do processo de vocês)

```bash
php artisan db:seed --force
```

> Rode **somente** seeders planejados (ex.: FIC em ambiente de teste). Evite `db:seed` cego em produção sem saber o que será inserido.

### 4.8 Dados de matriz (opcional, pós-migrate)

Conforme o processo institucional:

```bash
php artisan matrizes:verify --file=/caminho/absoluto/matrizes.csv
php artisan matrizes:import --file=/caminho/absoluto/matrizes.csv
# ou, quando aplicável:
php artisan matrizes:bootstrap-from-offerings
```

Ajuste `--file` e revise relatórios/`--dry-run` antes de importar em produção.

### 4.9 Permissões e storage

```bash
chmod -R ug+rwx storage bootstrap/cache
# se aplicável ao seu deploy:
php artisan storage:link
```

### 4.10 Otimização de cache (produção)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Se algo falhar após `route:cache`, limpe com `php artisan route:clear` e investigue rotas duplicadas ou closures.

### 4.11 Servidor web

- Document root deve apontar para `salas-laravel/public`.
- Reinicie **php-fpm** / serviço PHP após deploy, se necessário.

### 4.12 Verificação pós-deploy (smoke test)

1. Login admin e coordenador.
2. **Select de curso** (se >1 curso): trocar curso e confirmar que **matriz** e link de **ofertas** refletem o curso.
3. **Matriz:** abas Por semestre / Optativas; **Editar** salva sem erro 403 para curso permitido.
4. **Ofertas:** carregar página do curso selecionado.
5. Se FIC estiver em produção: acessar `admin/fic` com usuário admin.

### 4.13 Rollback

1. Restaurar dump do banco.
2. Restaurar código/commit anterior.
3. `php artisan migrate:rollback` **só** se ainda estiver no mesmo estado de migrations e souber exatamente o que será revertido (em produção rollback pode ser delicado).

---

## 5. Itens para decidir antes do merge/deploy

- **Remoção do backend legado** (`backend/`, `data/`): confirmar se o servidor ainda depende desses arquivos ou se o Laravel já é o único backend em produção.
- **Módulo FIC:** entrará em produção na mesma entrega que matriz/select?
- **Política de dados:** importação de `matrizes.csv` vs bootstrap a partir de ofertas.

---

## 6. Histórico deste documento

- Gerado para refletir o estado do repositório no momento da solicitação do usuário (alterações locais + não commitadas). Atualize após novos commits ou antes de cada deploy.
