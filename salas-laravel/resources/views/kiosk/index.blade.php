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
            const state = {
                cursos: window.KIOSK_DATA || [],
                meta: window.KIOSK_META || {},
                stack: [{ kind: 'courses' }]
            };
            const dom = {};
            function cacheDom() {
                dom.screenCourses = document.getElementById('screen-courses');
                dom.screenCourseDetail = document.getElementById('screen-course-detail');
                dom.coursesGrid = document.getElementById('courses-grid');
                dom.disciplineList = document.getElementById('discipline-list');
                dom.btnBackCourses = document.getElementById('btn-back-courses');
                dom.detailCourseTitle = document.getElementById('detail-course-title');
                dom.detailCourseSubtitle = document.getElementById('detail-course-subtitle');
            }
            function showScreen(which) {
                if (!dom.screenCourses || !dom.screenCourseDetail) return;
                const isCourses = which === 'courses';
                dom.screenCourses.classList.toggle('screen--active', isCourses);
                dom.screenCourseDetail.classList.toggle('screen--active', !isCourses);
            }
            function stackTop() {
                return state.stack[state.stack.length - 1];
            }
            function stackPush(frame) {
                state.stack.push(frame);
                renderFromStack();
            }
            function stackPop() {
                if (state.stack.length <= 1) return;
                state.stack.pop();
                renderFromStack();
            }
            function orderedCourseTiles() {
                const raw = state.cursos || [];
                const grads = raw.filter(function (c) { return (c.type || 'grad') === 'grad'; })
                    .sort(function (a, b) { return (a.nome || '').localeCompare(b.nome || '', 'pt-BR'); });
                const hubs = raw.filter(function (c) { return c.type === 'fic_hub'; });
                return grads.concat(hubs);
            }
            function renderCourses() {
                if (!dom.coursesGrid) return;
                const turno = (state.meta.turno || '').toUpperCase();
                dom.coursesGrid.classList.toggle('course-grid--single-column', turno === 'MANHA');
                const cursos = orderedCourseTiles();
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
                    btn.className = 'course-card' + (curso.type === 'fic_hub' ? ' course-card--fic-hub' : '');
                    btn.innerHTML = '<div class="course-card__code">' + (curso.codigoCurto || curso.nome || '') + '</div><div class="course-card__name">' + (curso.nome || '\u00A0') + '</div>';
                    btn.addEventListener('click', function () {
                        if (curso.type === 'fic_hub') {
                            stackPush({
                                kind: 'grad_detail',
                                title: curso.nome || 'Área',
                                subtitle: 'Consulte abaixo as aulas, salas e docentes desta área.',
                                disciplinas: curso.disciplinas || [],
                                skipNomeSort: true,
                                isFic: true
                            });
                            return;
                        }
                        stackPush({
                            kind: 'grad_detail',
                            title: curso.nome || curso.codigoCurto || 'Curso',
                            disciplinas: curso.disciplinas || []
                        });
                    });
                    dom.coursesGrid.appendChild(btn);
                });
            }
            function appendDisciplineCard(container, d) {
                const card = document.createElement('article');
                card.className = 'discipline-card';
                const main = document.createElement('div');
                main.className = 'discipline-main';
                const h = document.createElement('h3');
                h.className = 'discipline-name';
                h.textContent = d.nome || '';
                main.appendChild(h);
                const sala = d.sala ? ('SALA ' + String(d.sala).toUpperCase()) : 'SALA A DEFINIR';
                const badge = document.createElement('span');
                badge.className = 'badge-room';
                badge.textContent = sala;
                main.appendChild(badge);
                card.appendChild(main);
                if (d.docente) {
                    const meta = document.createElement('div');
                    meta.className = 'discipline-meta';
                    const tag = document.createElement('span');
                    tag.className = 'tag tag--docente';
                    tag.textContent = d.docente;
                    meta.appendChild(tag);
                    card.appendChild(meta);
                }
                container.appendChild(card);
            }
            function renderDetail(frame) {
                if (!dom.disciplineList || !dom.detailCourseTitle) return;
                if (frame.kind === 'grad_detail') {
                    dom.detailCourseTitle.textContent = frame.title || 'Curso';
                    if (dom.detailCourseSubtitle) {
                        dom.detailCourseSubtitle.textContent = frame.subtitle || 'Consulte abaixo as disciplinas e salas deste curso.';
                    }
                    dom.disciplineList.innerHTML = '';
                    var list = (frame.disciplinas || []).slice();
                    if (!frame.skipNomeSort) {
                        list.sort(function (a, b) {
                            return (a.nome || '').localeCompare(b.nome || '', 'pt-BR');
                        });
                    }
                    if (list.length === 0) {
                        var empty = document.createElement('div');
                        empty.className = 'discipline-card';
                        var msg = frame.isFic
                            ? 'Nenhum encontro cadastrado para esta área no momento.'
                            : 'Nenhuma disciplina listada para este curso.';
                        empty.innerHTML = '<h3 class="discipline-name">Sem informações</h3><p class="screen-description">' + msg + '</p>';
                        dom.disciplineList.appendChild(empty);
                        return;
                    }
                    list.forEach(function (d) {
                        appendDisciplineCard(dom.disciplineList, d);
                    });
                }
            }
            function renderFromStack() {
                var top = stackTop();
                if (top.kind === 'courses') {
                    showScreen('courses');
                    renderCourses();
                    return;
                }
                showScreen('detail');
                renderDetail(top);
            }
            function scheduleReload() {
                setTimeout(function () { location.replace(location.pathname + '?t=' + Date.now()); }, RELOAD_MS);
            }
            cacheDom();
            dom.btnBackCourses && dom.btnBackCourses.addEventListener('click', function () { stackPop(); });
            renderFromStack();
            scheduleReload();
        })();
    </script>
</body>
</html>
