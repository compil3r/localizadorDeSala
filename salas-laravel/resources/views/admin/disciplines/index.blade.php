@extends('layouts.admin')

@section('title', 'Matriz curricular')

@section('content')
    @php
        $currentCourse = request()->attributes->get('currentCourse');
        $showCourseBlockHeading = ($matrixByCourse->count() ?? 0) > 1;
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Matriz curricular</h1>
            <p class="mb-0 text-muted">
                {{ $currentCourse ? ('Curso: ' . $currentCourse->name) : 'Edite o semestre e marque optativas na matriz.' }}
            </p>
        </div>
    </div>

    <div class="page-content matrix-slim">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-por-semestre-tab" data-bs-toggle="tab"
                        data-bs-target="#tab-por-semestre" type="button" role="tab"
                        aria-controls="tab-por-semestre" aria-selected="true">Por semestre</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-optativas-tab" data-bs-toggle="tab"
                        data-bs-target="#tab-optativas" type="button" role="tab"
                        aria-controls="tab-optativas" aria-selected="false">
                    Optativas
                    @if(isset($optionalRows) && $optionalRows->count() > 0)
                        <span class="badge bg-success ms-1 align-middle" style="font-size:.65rem">{{ $optionalRows->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-por-semestre" role="tabpanel" aria-labelledby="tab-por-semestre-tab">
            @forelse($matrixByCourse as $courseCode => $courseRows)
                @php
                    $courseName = $courseRows->first()->course_name ?? '';
                    $sortedRows = $courseRows
                        ->filter(fn ($r) => (int) $r->is_optional === 0)
                        ->sortBy(function ($r) {
                            return sprintf('%04d-%s', (int) $r->course_semester, (string) $r->discipline_name);
                        });
                @endphp
                @if($sortedRows->count() > 0)
                    @php
                        $prevSemester = null;
                        $semBlock = 0;
                    @endphp
                    <section class="matrix-course mb-3 pb-3 border-bottom border-light">
                        @if($showCourseBlockHeading)
                            <h2 class="h6 matrix-course-title mb-1">{{ $courseCode }}</h2>
                            <p class="text-muted small mb-2">{{ $courseName }}</p>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0 matrix-table">
                                <colgroup>
                                    <col class="col-sem">
                                    <col>
                                    <col class="col-type">
                                    <col class="col-mother">
                                    <col class="col-action">
                                </colgroup>
                                <thead>
                                <tr>
                                    <th class="col-sem" scope="col">Sem.</th>
                                    <th scope="col">Disciplina</th>
                                    <th class="col-type" scope="col">Tipo</th>
                                    <th class="col-mother" scope="col">Mãe</th>
                                    <th class="col-action text-end" scope="col"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($sortedRows as $r)
                                    @php
                                        $semester = (int) $r->course_semester;
                                        if ($prevSemester !== null && $prevSemester !== $semester) {
                                            $semBlock = 1 - $semBlock; // alterna ao mudar de semestre
                                        }
                                        $prevSemester = $semester;
                                        $semBgClass = $semBlock === 0 ? 'sem-bg-odd' : 'sem-bg-even';

                                        $badgeClass = 'bg-secondary';
                                        if ($r->type === 'PRÓPRIA') $badgeClass = 'bg-light text-dark';
                                        elseif ($r->type === 'COMPARTILHADA') $badgeClass = 'bg-warning text-dark';
                                        elseif ($r->type === 'OPTATIVA') $badgeClass = 'bg-success';
                                        $courseLabel = e($r->course_code . ' – ' . $r->course_name);
                                    @endphp
                                    <tr class="{{ $semBgClass }}">
                                        <td class="col-sem text-muted">{{ $r->course_semester }}</td>
                                        <td>{{ $r->discipline_name }}</td>
                                        <td class="col-type"><span class="badge {{ $badgeClass }}">{{ $r->type }}</span></td>
                                        <td class="col-mother text-muted small">
                                            @if(!empty($r->mother_course_code))
                                                {{ $r->mother_course_code }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="col-action text-end">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-edit-matrix"
                                                    data-matrix-id="{{ $r->matrix_id }}"
                                                    data-course-label="{{ $courseLabel }}"
                                                    data-discipline="{{ e($r->discipline_name) }}"
                                                    data-semester="{{ (int) $r->course_semester }}"
                                                    data-is-optional="{{ (int) $r->is_optional }}">
                                                Editar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            @empty
                <p class="text-muted small mb-0">Nenhuma matriz curricular encontrada para os cursos disponíveis.</p>
            @endforelse
        </div>

        <div class="tab-pane fade" id="tab-optativas" role="tabpanel" aria-labelledby="tab-optativas-tab">
            @if(isset($optionalRows) && $optionalRows->count() > 0)
                <p class="matrix-hint text-muted mb-2">UCs com optativa na matriz.</p>
                @php
                    $optionalGrouped = $optionalRows
                        ->sortBy(function ($r) {
                            return sprintf('%s-%04d-%s', (string) ($r->course_code ?? ''), (int) $r->course_semester, (string) $r->discipline_name);
                        })
                        ->groupBy('course_code');
                @endphp

                @foreach($optionalGrouped as $optCourseCode => $optRowsByCourse)
                    @php
                        $optCourseName = $optRowsByCourse->first()->course_name ?? '';
                        $prevSemester = null;
                        $semBlock = 0;
                    @endphp
                    <section class="matrix-course mb-3 pb-3 border-bottom border-light">
                        @if($optionalGrouped->count() > 1)
                            <h2 class="h6 matrix-course-title mb-1">{{ $optCourseCode }}</h2>
                            <p class="text-muted small mb-2">{{ $optCourseName }}</p>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0 matrix-table">
                                <colgroup>
                                    <col class="col-sem">
                                    <col>
                                    <col class="col-mother">
                                    <col class="col-action">
                                </colgroup>
                                <thead>
                                <tr>
                                    <th class="col-sem" scope="col">Sem.</th>
                                    <th scope="col">Disciplina</th>
                                    <th class="col-mother" scope="col">Mãe</th>
                                    <th class="col-action text-end" scope="col"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($optRowsByCourse as $r)
                                    @php
                                        $semester = (int) $r->course_semester;
                                        if ($prevSemester !== null && $prevSemester !== $semester) {
                                            $semBlock = 1 - $semBlock;
                                        }
                                        $prevSemester = $semester;
                                        $semBgClass = $semBlock === 0 ? 'sem-bg-odd' : 'sem-bg-even';

                                        $courseLabel = e(($r->course_code ?? '') . ' – ' . ($r->course_name ?? ''));
                                    @endphp
                                    <tr class="{{ $semBgClass }}">
                                        <td class="col-sem text-muted">{{ $r->course_semester }}</td>
                                        <td>{{ $r->discipline_name }}</td>
                                        <td class="col-mother text-muted small">
                                            @if(!empty($r->mother_course_code))
                                                {{ $r->mother_course_code }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="col-action text-end">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-edit-matrix"
                                                    data-matrix-id="{{ $r->matrix_id }}"
                                                    data-course-label="{{ $courseLabel }}"
                                                    data-discipline="{{ e($r->discipline_name) }}"
                                                    data-semester="{{ (int) $r->course_semester }}"
                                                    data-is-optional="{{ (int) $r->is_optional }}">
                                                Editar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endforeach
            @else
                <p class="text-muted small mb-0">Nenhuma UC optativa na matriz.</p>
            @endif
        </div>
        </div>
    </div>

    <div class="modal fade" id="modal-edit-matrix" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Editar item da matriz</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" id="form-edit-matrix" class="modal-body">
                    @csrf
                    @method('PUT')
                    <p class="text-muted small mb-2" id="matrix-modal-summary"></p>
                    <div class="mb-3">
                        <label class="form-label" for="matrix-semester">Semestre do curso</label>
                        <input type="number" class="form-control" name="course_semester" id="matrix-semester"
                               min="1" max="30" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_optional" value="1" id="matrix-optativa">
                        <label class="form-check-label" for="matrix-optativa">UC optativa na matriz</label>
                    </div>
                    <p class="text-muted small mb-0">Desmarcar optativa altera o tipo para PRÓPRIA ou COMPARTILHADA, conforme o curso mãe da disciplina.</p>
                    <div class="modal-footer px-0 pb-0 border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const modalEl = document.getElementById('modal-edit-matrix');
                const modal = new bootstrap.Modal(modalEl);
                const form = document.getElementById('form-edit-matrix');
                const summary = document.getElementById('matrix-modal-summary');
                const semesterInput = document.getElementById('matrix-semester');
                const optCheckbox = document.getElementById('matrix-optativa');
                const matrixUpdateBase = @json(url('/admin/curriculum-matrix'));

                document.querySelectorAll('.btn-edit-matrix').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const id = this.dataset.matrixId;
                        const courseLabel = this.dataset.courseLabel || '';
                        const discipline = this.dataset.discipline || '';
                        semesterInput.value = this.dataset.semester || '1';
                        optCheckbox.checked = (this.dataset.isOptional === '1');
                        summary.textContent = courseLabel + ' · ' + discipline;
                        form.action = matrixUpdateBase.replace(/\/$/, '') + '/' + encodeURIComponent(id);
                        modal.show();
                    });
                });
            })();
        </script>
    @endpush
@endsection
