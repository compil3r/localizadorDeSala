@extends('layouts.admin')

@section('title', 'Oferta 2026/1')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h1 class="h4 mb-1">Oferta 2026/1</h1>
            <p class="mb-0 text-muted">Escolha um curso para gerenciar ofertas (disciplinas próprias, optativas e compartilhadas).</p>
        </div>
    </div>

    <div class="page-content">
        <table class="table table-hover align-middle bg-white">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Curso</th>
                    <th>Coordenador</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td>{{ $course->code }}</td>
                        <td>{{ $course->name }}</td>
                        <td>{{ $course->coordinator?->name ?? '—' }}</td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="{{ route('admin.offerings.index', $course) }}">Gerenciar ofertas</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nenhum curso encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
