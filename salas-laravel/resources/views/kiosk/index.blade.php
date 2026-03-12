<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Salas UniSenac - Localizador de Salas</title>
    <link rel="stylesheet" href="{{ $assetBase }}/css/kiosk.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app safe-area">
        <header class="kiosk-header">
            <div class="kiosk-header__titles">
                <h1 class="app-title">SALAS UNISENAC</h1>
                <p class="app-subtitle">aprender fazendo muda tudo</p>
                <hr class="kiosk-header__divider">
            </div>
        </header>

        <div class="kiosk-scroll">
        <main class="kiosk-main">
            <section id="screen-courses" class="screen screen--active screen--courses" aria-label="Lista de cursos">
                <div class="screen-body">
                    <h2 class="screen-title">Escolha seu curso</h2>
                    <p class="screen-description">Toque no curso para ver as disciplinas e salas.</p>
                    <div id="courses-grid" class="course-grid" aria-live="polite"></div>
                </div>
            </section>

            <section id="screen-course-detail" class="screen" aria-label="Disciplinas do curso selecionado">
                <div class="screen-body">
                    <div class="screen-top">
                        <h2 class="screen-title screen-title--detail" id="detail-course-title"></h2>
                        <p class="screen-description" id="detail-course-subtitle">
                            Consulte abaixo as disciplinas e salas deste curso.
                        </p>
                    </div>
                    <div id="discipline-list" class="discipline-list" aria-live="polite"></div>
                    <div class="screen-actions">
                        <button id="btn-back-courses" class="btn-primary btn-back" type="button">Voltar aos cursos</button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="kiosk-footer">
            <div class="kiosk-footer__logos">
                <div class="logo logo--senac" aria-label="Logo Senac"></div>
                <div class="logo logo--sistema-comercio" aria-label="Logo Sistema Comércio">
                    <img src="{{ $assetBase }}/images/logouni.png" alt="Sistema Comércio" loading="lazy" onerror="this.style.display='none'">
                </div>
            </div>
        </footer>
        </div>
    </div>

    <script>
        window.KIOSK_DATA = @json($cursos);
        window.KIOSK_META = @json($meta);
    </script>
    <script>
        (function () {
            const RELOAD_MS = 10 * 60 * 1000;
            const state = { cursos: window.KIOSK_DATA || [], meta: window.KIOSK_META || {}, selectedCourseId: null };
            const dom = {};
            function cacheDom() {
                dom.screenCourses = document.getElementById('screen-courses');
                dom.screenCourseDetail = document.getElementById('screen-course-detail');
                dom.coursesGrid = document.getElementById('courses-grid');
                dom.disciplineList = document.getElementById('discipline-list');
                dom.btnBackCourses = document.getElementById('btn-back-courses');
                dom.detailCourseTitle = document.getElementById('detail-course-title');
            }
            function showScreen(which) {
                if (!dom.screenCourses || !dom.screenCourseDetail) return;
                const isCourses = which === 'courses';
                dom.screenCourses.classList.toggle('screen--active', isCourses);
                dom.screenCourseDetail.classList.toggle('screen--active', !isCourses);
            }
            function renderCourses() {
                if (!dom.coursesGrid) return;
                const turno = (state.meta.turno || '').toUpperCase();
                dom.coursesGrid.classList.toggle('course-grid--single-column', turno === 'MANHA');
                const cursos = [...state.cursos].sort((a, b) => (a.nome || '').localeCompare(b.nome || '', 'pt-BR'));
                if (cursos.length === 0) {
                    const msg = state.meta.mensagem || state.meta.dia_semana === 'DOM'
                        ? 'Nenhuma aula neste horário.'
                        : 'Nenhum curso com aulas neste horário e dia. Verifique o painel admin.';
                    dom.coursesGrid.innerHTML = '<div class="discipline-card"><h3 class="discipline-name">Nenhum curso no momento.</h3><p class="screen-description">' + msg + '</p></div>';
                    return;
                }
                dom.coursesGrid.innerHTML = '';
                cursos.forEach(function (curso) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'course-card';
                    btn.innerHTML = '<div class="course-card__code">' + (curso.codigoCurto || curso.nome || '') + '</div><div class="course-card__name">' + (curso.nome || '\u00A0') + '</div>';
                    btn.addEventListener('click', function () {
                        state.selectedCourseId = curso.id;
                        if (dom.detailCourseTitle) dom.detailCourseTitle.textContent = curso.nome || curso.codigoCurto || 'Curso';
                        const disciplinas = (curso.disciplinas || []).sort((a, b) => (a.nome || '').localeCompare(b.nome || '', 'pt-BR'));
                        dom.disciplineList.innerHTML = '';
                        disciplinas.forEach(function (d) {
                            const card = document.createElement('article');
                            card.className = 'discipline-card';
                            const sala = d.sala ? ('SALA ' + String(d.sala).toUpperCase()) : 'SALA A DEFINIR';
                            card.innerHTML = '<div class="discipline-main"><h3 class="discipline-name">' + (d.nome || '') + '</h3><span class="badge-room">' + sala + '</span></div>' +
                                (d.docente ? '<div class="discipline-meta"><span class="tag tag--docente">' + d.docente + '</span></div>' : '');
                            dom.disciplineList.appendChild(card);
                        });
                        showScreen('detail');
                    });
                    dom.coursesGrid.appendChild(btn);
                });
            }
            function scheduleReload() {
                setTimeout(function () { location.replace(location.pathname + '?t=' + Date.now()); }, RELOAD_MS);
            }
            cacheDom();
            dom.btnBackCourses && dom.btnBackCourses.addEventListener('click', function () { showScreen('courses'); });
            renderCourses();
            scheduleReload();
        })();
    </script>
</body>
</html>
