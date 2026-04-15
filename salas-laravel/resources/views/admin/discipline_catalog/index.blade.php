@extends('layouts.admin')

@section('title', 'Catálogo de UCs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Catálogo de UCs</h1>
            <p class="mb-0 text-muted">
                Edite o <strong>curso mãe</strong> (`owning_course_id`) das unidades curriculares.
            </p>
        </div>
        <div>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.disciplines.index') }}">
                Voltar para a matriz
            </a>
        </div>
    </div>

    <div class="page-content">
        <table class="table table-hover align-middle bg-white">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome da disciplina</th>
                <th>Curso pertencente</th>
                <th>Ação</th>
            </tr>
            </thead>
            <tbody>
            @forelse($disciplines as $disc)
                <tr>
                    <td>{{ $disc->id }}</td>
                    <td>{{ $disc->name }}</td>
                    <td>
                        @if($disc->owningCourse)
                            <span class="badge bg-light text-dark">{{ $disc->owningCourse->code }} – {{ $disc->owningCourse->name }}</span>
                        @else
                            <span class="badge bg-secondary">Sem curso definido</span>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit"
                                data-id="{{ $disc->id }}"
                                data-name="{{ e($disc->name) }}"
                                data-course-id="{{ $disc->owning_course_id ?? '' }}">
                            Editar
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Nenhuma disciplina cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modal-backdrop" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Editar disciplina</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" id="modal-form" class="modal-body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="modal-id" value="">

                    <div class="mb-3">
                        <label class="form-label" for="modal-name">Nome da disciplina</label>
                        <input class="form-control" type="text" name="name" id="modal-name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modal-course">Curso pertencente</label>
                        <select class="form-select" name="owning_course_id" id="modal-course" required>
                            <option value="">Selecione um curso</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} – {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const modal = new bootstrap.Modal(document.getElementById('modal-backdrop'));
                const form = document.getElementById('modal-form');
                const idInput = document.getElementById('modal-id');
                const nameInput = document.getElementById('modal-name');
                const courseSelect = document.getElementById('modal-course');

                document.querySelectorAll('.btn-edit').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const id = this.dataset.id;
                        const name = this.dataset.name;
                        const courseId = this.dataset.courseId;
                        idInput.value = id || '';
                        nameInput.value = name || '';
                        courseSelect.value = courseId || '';
                        form.action = '{{ route("admin.disciplines.update", ["discipline" => 999]) }}'.replace('999', id);
                        modal.show();
                    });
                });
            })();
        </script>
    @endpush
@endsection

