@extends('layouts.admin')

@section('title', 'Ofertas – ' . $course->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Ofertas – {{ $course->name }} ({{ $course->code }})</h1>
            <p class="mb-0 text-muted">Inclui disciplinas próprias, optativas e compartilhadas deste curso.</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-open-offering">Nova oferta</button>
        </div>
    </div>

    <div class="page-content">
        <table class="table table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Turno</th>
                    <th class="sortable" data-sort="dia" role="button" tabindex="0">Dia <span class="sort-indicator" aria-hidden="true"></span></th>
                    <th>Sala</th>
                    <th>Origem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($offerings as $o)
                    <tr data-dia="{{ $o['dia_semana'] }}">
                        <td>{{ $o['disciplina'] }}</td>
                        <td>{{ $o['professor'] }}</td>
                        <td>{{ $o['turno'] }}</td>
                        <td>{{ $o['dia_semana'] }}</td>
                        <td>{{ $o['sala'] ?? '—' }}</td>
                        <td><span class="pill">{{ $o['origin_type'] }}</span></td>
                        <td>
                            @if($o['origin_type'] === 'PROPRIA')
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-edit-offering me-1"
                                        data-offering-id="{{ $o['id'] }}"
                                        data-teacher-id="{{ $o['teacher_id'] }}"
                                        data-turno="{{ $o['turno'] }}"
                                        data-dia="{{ $o['dia_semana'] }}"
                                        data-room="{{ $o['sala'] ?? '' }}"
                                        title="Alterar docente, turno, dia e sala (afeta cursos que compartilham esta UC)">
                                    Editar
                                </button>
                            @endif
                            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-offering"
                                    data-offering-id="{{ $o['id'] }}"
                                    data-origin-type="{{ $o['origin_type'] }}"
                                    data-other-count="{{ $o['other_courses_count'] }}">
                                Remover
                            </button>
                        </td>
                    </tr>
                @endforeach
                @if($offerings->isEmpty())
                    <tr><td colspan="7">Nenhuma oferta cadastrada ainda.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <form id="form-delete-offering" method="post" action="{{ route('admin.offerings.destroy', $course) }}" style="display:none">
        @csrf
        <input type="hidden" name="offering_id" id="delete-offering-id">
        <input type="hidden" name="delete_scope" id="delete-scope">
    </form>

    <div class="modal fade" id="modal-delete-all" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Remover oferta</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p id="delete-all-msg">Esta oferta está presente em outros cursos. Remover irá remover de todos. Deseja continuar?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-delete-all">Sim, remover de todos</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-edit-offering" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Editar oferta própria</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="{{ route('admin.offerings.update', $course) }}" id="form-edit-offering" class="modal-body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="offering_id" id="edit-offering-id" value="">
                    <p class="text-muted small mb-3">A alteração vale para todos os cursos que compartilham esta unidade curricular.</p>
                    <div class="mb-3">
                        <label class="form-label" for="edit-teacher">Docente</label>
                        <select class="form-select" name="teacher_id" id="edit-teacher" required>
                            <option value="">Selecione o docente</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-turno">Turno</label>
                        <select class="form-select" name="turno" id="edit-turno" required>
                            <option value="MANHA">Manhã</option>
                            <option value="NOITE">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-dia">Dia da semana</label>
                        <select class="form-select" name="dia_semana" id="edit-dia" required>
                            <option value="SEG">Segunda</option>
                            <option value="TER">Terça</option>
                            <option value="QUA">Quarta</option>
                            <option value="QUI">Quinta</option>
                            <option value="SEX">Sexta</option>
                            <option value="SAB">Sábado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-room">Sala</label>
                        <input type="text" class="form-control" name="room" id="edit-room" maxlength="32" placeholder="Ex: 203">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-offering-backdrop" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Adicionar nova oferta</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="{{ route('admin.offerings.store', $course) }}" class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Oferta</label>
                        <select class="form-select" name="offering_slot_id" id="offering-select" required>
                            <option value="">Selecione uma oferta de outro curso</option>
                            @foreach($allOfferings as $o)
                                @php
                                    $label = ($o['course_code'] ?? '??') . ' | ' . $o['discipline_name'] . ' | ' . $o['turno'] . ' | Prof. ' . $o['professor_name'];
                                    $extra = array_filter([$o['dia_semana'] ?? '', $o['room'] ?? '']);
                                    if (!empty($extra)) $label .= ' · ' . implode(', ', $extra);
                                @endphp
                                <option value="{{ $o['offering_slot_id'] }}" title="{{ $label }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Curso | Disciplina | Turno | Professor (dia, sala)</div>
                        @if(empty($allOfferings))
                            <div class="form-text text-muted">Nenhuma oferta de outro curso disponível.</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo da oferta</label>
                        <select class="form-select" name="origin_type" required>
                            <option value="COMPARTILHADA">Compartilhada</option>
                            <option value="OPTATIVA">Optativa</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const DIA_ORDEM = { SEG: 1, TER: 2, QUA: 3, QUI: 4, SEX: 5, SAB: 6 };
            const tbody = document.querySelector('.page-content table tbody');
            const thDia = document.querySelector('th[data-sort="dia"]');
            let sortDiaAsc = true;
            function sortByDia() {
                if (!tbody || !thDia) return;
                const rows = Array.from(tbody.querySelectorAll('tr[data-dia]'));
                const fallback = (v) => DIA_ORDEM[v] ?? 99;
                rows.sort((a, b) => {
                    const va = fallback(a.dataset.dia);
                    const vb = fallback(b.dataset.dia);
                    return sortDiaAsc ? va - vb : vb - va;
                });
                rows.forEach(r => tbody.appendChild(r));
                const ind = thDia.querySelector('.sort-indicator');
                if (ind) ind.textContent = sortDiaAsc ? ' ▲' : ' ▼';
                sortDiaAsc = !sortDiaAsc;
            }
            thDia && thDia.addEventListener('click', sortByDia);
            thDia && thDia.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sortByDia(); } });
            sortByDia();

            const modalNew = new bootstrap.Modal(document.getElementById('modal-offering-backdrop'));
            const modalEdit = new bootstrap.Modal(document.getElementById('modal-edit-offering'));
            const modalDeleteAll = new bootstrap.Modal(document.getElementById('modal-delete-all'));
            const formDelete = document.getElementById('form-delete-offering');
            const deleteOfferingId = document.getElementById('delete-offering-id');
            const deleteScope = document.getElementById('delete-scope');
            const msgDeleteAll = document.getElementById('delete-all-msg');
            const btnConfirmDeleteAll = document.getElementById('btn-confirm-delete-all');

            document.getElementById('btn-open-offering') && document.getElementById('btn-open-offering').addEventListener('click', () => modalNew.show());

            document.querySelectorAll('.btn-edit-offering').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('edit-offering-id').value = this.dataset.offeringId;
                    document.getElementById('edit-teacher').value = this.dataset.teacherId;
                    document.getElementById('edit-turno').value = this.dataset.turno;
                    document.getElementById('edit-dia').value = this.dataset.dia;
                    document.getElementById('edit-room').value = this.dataset.room || '';
                    modalEdit.show();
                });
            });

            document.querySelectorAll('.btn-remove-offering').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.dataset.offeringId;
                    const origin = this.dataset.originType;
                    const otherCount = parseInt(this.dataset.otherCount || '0', 10);
                    deleteOfferingId.value = id;
                    if (origin === 'COMPARTILHADA' || origin === 'OPTATIVA') {
                        if (confirm('Remover esta oferta do curso?')) { deleteScope.value = 'current'; formDelete.submit(); }
                    } else if (otherCount > 0) {
                        msgDeleteAll.textContent = 'Esta oferta está presente em ' + otherCount + ' outro(s) curso(s). Remover irá remover de todos. Deseja continuar?';
                        modalDeleteAll.show();
                    } else {
                        if (confirm('Remover esta oferta?')) { deleteScope.value = 'current'; formDelete.submit(); }
                    }
                });
            });

            btnConfirmDeleteAll && btnConfirmDeleteAll.addEventListener('click', function () {
                deleteScope.value = 'all';
                formDelete.submit();
            });
        })();
    </script>
    @endpush
@endsection
