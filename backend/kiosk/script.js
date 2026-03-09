// Remove prefixo "Curso Superior de Tecnologia em" dos nomes de curso
function shortCourseName(name) {
  if (!name || typeof name !== "string") return name || "";
  return name.replace(/^Curso Superior de Tecnologia em\s+/i, "").trim() || name;
}

// Configurações simples de ordenação e comportamento
const CONFIG = {
  sortCoursesByName: true,
  sortDisciplinesByName: true,
  // API do backend (auto-detecta: /kiosk/ usa ../api; raiz usa /backend/api)
  apiUrl: (() => {
    const p = location.pathname;
    return p.includes("/kiosk") ? "../api/kiosk.php" : "/salas/backend/api/kiosk.php";
  })(),
  reloadIntervalMs: 10 * 60 * 1000, // 10 minutos
};

const state = {
  cursos: [],
  selectedCourseId: null,
  meta: {},
};

const dom = {};

function cacheDom() {
  dom.screenCourses = document.getElementById("screen-courses");
  dom.screenCourseDetail = document.getElementById("screen-course-detail");
  dom.coursesGrid = document.getElementById("courses-grid");
  dom.disciplineList = document.getElementById("discipline-list");
  dom.btnBackCourses = document.getElementById("btn-back-courses");
  dom.detailCourseTitle = document.getElementById("detail-course-title");
  dom.detailCourseSubtitle = document.getElementById("detail-course-subtitle");
}

async function init() {
  cacheDom();
  setupEvents();
  await loadCursos();
  scheduleReload();
}

function scheduleReload() {
  if (CONFIG.reloadIntervalMs > 0) {
    setTimeout(() => {
      location.replace(location.pathname + "?t=" + Date.now());
    }, CONFIG.reloadIntervalMs);
  }
}

function setupEvents() {
  const goHome = () => showScreen("courses");
  dom.btnBackCourses?.addEventListener("click", goHome);
}

async function loadCursos() {
  try {
    const response = await fetch(CONFIG.apiUrl, {
      cache: "no-store",
      headers: { Accept: "application/json" },
    });

    if (!response.ok) {
      throw new Error(`Erro ao carregar dados (${response.status})`);
    }

    const data = await response.json();
    state.cursos = Array.isArray(data.cursos) ? data.cursos : [];
    state.meta = data.meta || {};

    renderCourses();
  } catch (error) {
    console.error(error);
    showDataError(
      "Não foi possível carregar a lista de cursos.",
      "Verifique se o backend está rodando e acessível. " +
        "O sistema filtra cursos e disciplinas pelo horário atual e dia da semana."
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

  if (dom.coursesGrid) {
    const turno = (state.meta.turno || "").toUpperCase();
    dom.coursesGrid.classList.toggle("course-grid--single-column", turno === "MANHA");
  }

  if (CONFIG.sortCoursesByName) {
    cursos.sort((a, b) => {
      const nameA = (a.nomeCurto || shortCourseName(a.nome) || "").toLocaleUpperCase("pt-BR");
      const nameB = (b.nomeCurto || shortCourseName(b.nome) || "").toLocaleUpperCase("pt-BR");
      return nameA.localeCompare(nameB);
    });
  }

  if (cursos.length === 0) {
    const msg = state.meta.mensagem || state.meta.dia_semana === "DOM"
      ? "Nenhuma aula neste horário."
      : "Nenhum curso com aulas neste horário e dia. Verifique o painel admin.";
    showDataError("Nenhum curso no momento.", msg);
    return;
  }

  const fragment = document.createDocumentFragment();

  cursos.forEach((curso) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "course-card";
    button.dataset.courseId = curso.id || "";

    const codigo = curso.codigoCurto || curso.sigla || "";
    const nome = shortCourseName(curso.nome || "");

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

  const courseName = shortCourseName(curso.nome) || curso.codigoCurto || curso.sigla || "Curso";
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

  dom.disciplineList.scrollTop = 0;
}

function showScreen(which) {
  if (!dom.screenCourses || !dom.screenCourseDetail) return;

  const isCourses = which === "courses";

  dom.screenCourses.classList.toggle("screen--active", isCourses);
  dom.screenCourseDetail.classList.toggle("screen--active", !isCourses);
}

window.addEventListener("DOMContentLoaded", init);
