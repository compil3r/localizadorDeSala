# START UniSenac – Localizador de Salas (kiosk)

Site simples em HTML/CSS/JS para rodar em modo kiosk (touch) em um display vertical, inspirado no visual neon/retro do evento START UniSenac.

## Como rodar

> Importante: o arquivo `data/cursos.json` é carregado via `fetch`, então é recomendado rodar o projeto em um servidor local simples (em vez de abrir o `index.html` direto no navegador).

1. Abra esta pasta no terminal:

   ```bash
   cd /Users/vitorhugo/Projetos/localizadorDeSala
   ```

2. Rode um servidor estático simples (escolha uma das opções):

   - Python 3:

     ```bash
     python -m http.server 8000
     ```

   - Node (npx serve, se tiver instalado):

     ```bash
     npx serve .
     ```

3. Abra no navegador (em modo portrait se estiver testando em desktop/notebook):

   - `http://localhost:8000` (ou a porta usada)

4. Para uso em flipchart/kiosk:

   - Abra o navegador em tela cheia (F11 ou modo kiosk).
   - Garanta que a tela está em orientação **vertical (portrait)**.

## Onde editar os cursos / disciplinas / salas

O conteúdo vem do arquivo:

- `data/cursos.json`

Estrutura de exemplo:

```json
{
  "cursos": [
    {
      "id": "ads",
      "codigoCurto": "ADS",
      "nome": "Análise e Desenvolvimento de Sistemas",
      "turnoPadrao": "Manhã / Noite",
      "disciplinas": [
        {
          "nome": "Linguagem de Programação II",
          "sala": "203",
          "turno": "Noite",
          "observacao": "Laboratório"
        }
      ]
    }
  ]
}
``+

### Campos do JSON

- **cursos**: array de cursos.
- **id**: identificador único (string, sem espaços) – usado internamente.
- **codigoCurto**: sigla ou nome curto que aparece em destaque no botão do curso (ex.: `"ADS"`, `"IA & CD"`).
- **nome**: nome completo do curso (ex.: `"Análise e Desenvolvimento de Sistemas"`).
- **turnoPadrao** (opcional, apenas informativo): turno geral do curso (ex.: `"Manhã"`, `"Noite"`, `"Manhã / Noite"`). **Não é exibido** no botão da página inicial.
- **disciplinas**: array com as disciplinas daquele curso.

Cada item de **disciplinas** tem:

- **nome**: nome da disciplina (ex.: `"Banco de Dados I"`).
- **sala**: sala correspondente (ex.: `"105"`).
- **docente** (opcional): nome do professor/docente (ex.: `"Prof. João da Silva"`).
- **turno** (opcional, apenas informativo): ex.: `"Manhã"`, `"Noite"` – **não é exibido como flag**, mas você pode manter no JSON para controle interno.
- **observacao** (opcional): ex.: `"Laboratório"`, `"Auditório"` – também não é exibido como flag no layout atual.

### Ordenação

Por padrão:

- Cursos são ordenados pelo nome (ou sigla).
- Disciplinas são ordenadas alfabeticamente pelo campo `nome`.

Se quiser manter exatamente a ordem do JSON:

1. Abra `script.js`.
2. No topo, altere as flags:

```js
const CONFIG = {
  sortCoursesByName: false,
  sortDisciplinesByName: false,
  dataUrl: "./data/cursos.json"
};
```

## Onde colocar / trocar assets

Pasta padrão:

- `assets/`

O HTML já está preparado para buscar os seguintes arquivos (se não existirem, o layout continua funcionando, apenas sem mostrar a imagem – os elementos são escondidos automaticamente):

- `assets/logo-start.png`
- `assets/logo-unisenac.png`
- `assets/logo-senac.png`
- `assets/logo-sistema-comercio.png`

Para usar:

1. Salve os arquivos de logo com esses nomes dentro da pasta `assets/`.
2. As imagens aparecerão automaticamente no header/rodapé.

### Grid de fundo

Atualmente a grade (grid) é gerada via CSS (`repeating-linear-gradient`), sem precisar de imagem.

Se você tiver um asset de grid oficial:

1. Salve o arquivo em `assets/` (ex.: `assets/grid.png`).
2. No arquivo `styles.css`, procure por:

   ```css
   .app-background__grid {
   ```

3. Dentro dessa regra, você pode trocar o fundo para usar uma imagem:

   ```css
   .app-background__grid {
     position: absolute;
     inset: 0;
     background-image: url("./assets/grid.png");
     background-size: cover;
     opacity: 0.35;
     mix-blend-mode: screen;
   }
   ```

Se o asset não existir, apenas mantenha o fundo em CSS (como está por padrão).

### Shapes / elementos geométricos neon

Os shapes neon (triângulo, círculo, linha) são feitos em CSS, sem depender de imagens.

- Procure em `styles.css` por:
  - `.shape--triangle`
  - `.shape--line`
  - `.shape--circle`

Nessas regras você pode ajustar:

- **Tamanho**: `width`, `height`.
- **Posição**: `top`, `left`, `right`, etc.
- **Cores / brilho**: `border`, `background`, `box-shadow`.

Se quiser trocar por imagens:

1. Crie sua imagem em `assets/` (ex.: `assets/shape-triangle.png`).
2. Troque o conteúdo da classe para usar `background-image` e `background-size: contain;`.

## Ajustando a paleta de cores e o “tamanho” visual

No início de `styles.css` há um bloco de variáveis CSS em `:root`:

```css
:root {
  --color-bg-1: #07051b;
  --color-bg-2: #1a0651;
  --color-bg-3: #020820;
  --color-primary: #4cfffb;
  --color-accent: #ff5af1;
  --color-highlight: #f9ff7b;
  --button-height: 88px;
  --safe-area-padding: clamp(16px, 4vh, 32px);
  /* ... */
}
```

A partir daí você consegue:

- **Trocar paleta**: alterando `--color-bg-*`, `--color-primary`, `--color-accent`, `--color-highlight`.
- **Mudar altura mínima dos botões grandes**: `--button-height` (recomendado ≥ `72px`).
- **Ajustar “margem interna” da tela**: `--safe-area-padding` (modo “safe area” para não colar nas bordas).

## Onde ajustar posicionamento dos shapes

Ainda em `styles.css`, as regras:

- `.shape--triangle`
- `.shape--line`
- `.shape--circle`

controlam o layout dos elementos neon (semelhantes ao cartaz).

Exemplo (trecho simplificado):

```css
.shape--triangle {
  width: 240px;
  height: 240px;
  border: 4px solid var(--color-primary);
  clip-path: polygon(0 0, 100% 0, 0 100%);
  top: 6%;
  left: -4%;
}
```

Você pode:

- Mover: alterando `top`, `left`, `right`, `bottom`.
- Redimensionar: alterando `width` e `height`.
- Mudar cor/glow: alterando `border`, `box-shadow`.

## Resumo de UX / funcionamento

- Tela 1 (**Cursos**):
  - Header com título “START UNISENAC” + subtítulo “Conectando você ao futuro!”.
  - Botão discreto “Início” sempre disponível no canto superior direito.
  - Grid de cursos com no máximo 2 colunas (em telas menores vira 1 coluna).
  - Botões grandes, com glow neon, pensados para toque em pé (altura mínima ~88px).

- Tela 2 (**Disciplinas e Salas**):
  - Breadcrumb: `Cursos > [Nome do curso]`.
  - Destaque do curso selecionado em um card neon.
  - Lista de disciplinas em cards grandes:
    - Nome da disciplina em destaque.
    - Sala em pill neon grande (`SALA 203`, `SALA 105`, etc.).
    - Nome do docente mostrado em uma flag logo abaixo (quando o campo `docente` estiver preenchido).
  - Botão grande “Voltar aos cursos” sempre visível no final da tela.

Toda a navegação é feita em uma única página (`index.html`) via JavaScript (`script.js`), sem recarregar a página, para ser rápido e fluido em modo kiosk.***

