@extends('layouts.admin')

@section('title', 'Coordenadores')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Coordenadores</h1>
            <p class="mb-0 text-muted">Crie e gerencie contas de coordenadores, incluindo reset de senha.</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-open-create">Novo coordenador</button>
        </div>
    </div>

    <div class="page-content">
        <table class="table table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail (login)</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['coordinator_name'] }}</td>
                        <td>{{ $row['email'] ?? '—' }}</td>
                        <td>
                            @if($row['user_id'] ?? null)
                                <span class="pill">{{ ($row['active'] ?? false) ? 'Ativo' : 'Inativo' }}</span>
                            @else
                                <span class="text-muted">Sem usuário</span>
                            @endif
                        </td>
                        <td>
                            @if($row['user_id'] ?? null)
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-reset-pw"
                                        data-user-id="{{ $row['user_id'] }}"
                                        data-name="{{ e($row['coordinator_name']) }}">
                                    Resetar senha
                                </button>
                            @else
                                <em class="text-muted">Sem usuário vinculado</em>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum coordenador cadastrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modal-create" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Novo coordenador</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="{{ route('admin.coordinators.store') }}" class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input class="form-control" type="text" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input class="form-control" type="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha inicial</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Criar coordenador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-reset-pw" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Resetar senha</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form method="post" action="{{ route('admin.coordinators.reset-password') }}" id="form-reset-pw" class="modal-body">
                    @csrf
                    <input type="hidden" name="user_id" id="reset-user-id">
                    <p class="mb-3 text-muted" id="reset-pw-name"></p>
                    <div class="mb-3">
                        <label class="form-label">Nova senha</label>
                        <input class="form-control" type="password" name="new_password" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Alterar senha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const modalCreate = new bootstrap.Modal(document.getElementById('modal-create'));
            const modalResetPw = new bootstrap.Modal(document.getElementById('modal-reset-pw'));
            const inputUserId = document.getElementById('reset-user-id');
            const resetPwName = document.getElementById('reset-pw-name');

            document.getElementById('btn-open-create') && document.getElementById('btn-open-create').addEventListener('click', () => modalCreate.show());

            document.querySelectorAll('.btn-reset-pw').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    inputUserId.value = this.dataset.userId;
                    resetPwName.textContent = 'Alterar senha de: ' + (this.dataset.name || '');
                    modalResetPw.show();
                });
            });
        })();
    </script>
    @endpush
@endsection
