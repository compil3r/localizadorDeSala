// Configurações simples de ordenação e comportamento
const CONFIG = {
  sortCoursesByName: true,
  sortDisciplinesByName: true,
  dataUrl: "./data/cursos_manha.json",
};

const state = {
  cursos: [],
  selectedCourseId: null,
};

// Referências de elementos
const dom = {};

function cacheDom() {
  dom.screenCourses = document.getElementById("screen-courses");
  dom.screenCourseDetail = document.getElementById("screen-course-detail");
  dom.coursesGrid = document.getElementById("courses-grid");
  dom.disciplineList = document.getElementById("discipline-list");
  dom.btnHome = document.getElementById("btn-home");
  dom.breadcrumbHome = document.getElementById("breadcrumb-home");
  dom.breadcrumbCourseName = document.getElementById("breadcrumb-course-name");
  dom.detailCourseTitle = document.getElementById("detail-course-title");
  dom.detailCourseSubtitle = document.getElementById("detail-course-subtitle");
}

async function init() {
  cacheDom();
  setupEvents();
  await loadCursos();
}

function setupEvents() {
  const goHome = () => showScreen("courses");

  dom.btnHome?.addEventListener("click", goHome);
  dom.breadcrumbHome?.addEventListener("click", goHome);
}

async function loadCursos() {
  try {
    const response = await fetch(CONFIG.dataUrl, {
      cache: "no-store",
    });

    if (!response.ok) {
      throw new Error(`Erro ao carregar dados (${response.status})`);
    }

    const data = await response.json();
    state.cursos = Array.isArray(data.cursos) ? data.cursos : [];

    renderCourses();
  } catch (error) {
    console.error(error);
    showDataError(
      "Não foi possível carregar a lista de cursos.",
      "Verifique se o arquivo data/cursos.json está acessível. " +
        "Para uso em kiosk, rode o projeto em um servidor local simples (por exemplo: python -m http.server)."
    );
  }
}

function showDataError(title, message) {
  if (!dom.coursesGrid) return;
  dom.coursesGrid.innerHTML = `
    <div class="discipline-card">
      <h3 class="discipline-name">${title}</h3>
      <p class="screen-description">${message}</p>
    </div>
  `;
}

function renderCourses() {
  if (!dom.coursesGrid) return;

  let cursos = [...state.cursos];

  if (CONFIG.sortCoursesByName) {
    cursos.sort((a, b) => {
      const nameA = (a.nomeCurto || a.nome || "").toLocaleUpperCase("pt-BR");
      const nameB = (b.nomeCurto || b.nome || "").toLocaleUpperCase("pt-BR");
      return nameA.localeCompare(nameB);
    });
  }

  if (cursos.length === 0) {
    showDataError(
      "Nenhum curso cadastrado.",
      "Edite o arquivo data/cursos.json para adicionar cursos, disciplinas e salas."
    );
    return;
  }

  const fragment = document.createDocumentFragment();

  cursos.forEach((curso) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "course-card";
    button.dataset.courseId = curso.id || "";

    const codigo = curso.codigoCurto || curso.sigla || "";
    const nome = curso.nome || "";

    button.innerHTML = `
      <div class="course-card__code">${codigo || nome}</div>
      <div class="course-card__name">${nome || "&nbsp;"}</div>
    `;

    button.addEventListener("click", () => {
      openCourse(curso.id);
    });

    fragment.appendChild(button);
  });

  dom.coursesGrid.innerHTML = "";
  dom.coursesGrid.appendChild(fragment);
}

function openCourse(courseId) {
  const curso = state.cursos.find((c) => c.id === courseId);
  if (!curso) return;

  state.selectedCourseId = courseId;

  const courseName = curso.nome || curso.codigoCurto || curso.sigla || "Curso";
  if (dom.breadcrumbCourseName) {
    dom.breadcrumbCourseName.textContent = courseName;
  }
  if (dom.detailCourseTitle) {
    dom.detailCourseTitle.textContent = courseName;
  }

  renderDisciplines(curso);
  showScreen("detail");
}

function renderDisciplines(curso) {
  if (!dom.disciplineList) return;

  let disciplinas = Array.isArray(curso.disciplinas) ? [...curso.disciplinas] : [];

  if (CONFIG.sortDisciplinesByName) {
    disciplinas.sort((a, b) => {
      const nameA = (a.nome || "").toLocaleUpperCase("pt-BR");
      const nameB = (b.nome || "").toLocaleUpperCase("pt-BR");
      return nameA.localeCompare(nameB);
    });
  }

  const fragment = document.createDocumentFragment();

  disciplinas.forEach((disciplina) => {
    const card = document.createElement("article");
    card.className = "discipline-card";

    const docente = disciplina.docente || disciplina.professor;
    const sala = disciplina.sala || "";

    const roomLabel = sala ? `SALA ${String(sala).toUpperCase()}` : "SALA A DEFINIR";

    card.innerHTML = `
      <div class="discipline-main">
        <h3 class="discipline-name">${disciplina.nome || ""}</h3>
        <span class="badge-room">${roomLabel}</span>
      </div>
      ${
        docente
          ? `<div class="discipline-meta"><span class="tag tag--docente">${docente}</span></div>`
          : ""
      }
    `;

    fragment.appendChild(card);
  });

  dom.disciplineList.innerHTML = "";
  dom.disciplineList.appendChild(fragment);

  // Garante que o topo da lista esteja visível ao trocar de curso
  dom.disciplineList.scrollTop = 0;
}

function showScreen(which) {
  if (!dom.screenCourses || !dom.screenCourseDetail) return;

  const isCourses = which === "courses";

  dom.screenCourses.classList.toggle("screen--active", isCourses);
  dom.screenCourseDetail.classList.toggle("screen--active", !isCourses);

  // Botão "Início" só aparece quando NÃO estamos na tela inicial
  if (dom.btnHome) {
    dom.btnHome.style.visibility = isCourses ? "hidden" : "visible";
  }
}

// Inicialização
window.addEventListener("DOMContentLoaded", init);

