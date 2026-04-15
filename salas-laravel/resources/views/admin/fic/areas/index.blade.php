@extends('layouts.admin')

@section('title', 'Áreas FIC')

@section('content')
    <h1 class="h3 mb-3">Cursos FIC / livres</h1>
    <p class="text-muted mb-4">Áreas temáticas (ex.: Gastronomia), cursos curtos e cronograma por data. A graduação não é alterada aqui.</p>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span></span>
        <a href="{{ route('admin.fic.areas.create') }}" class="btn btn-primary btn-sm">Nova área</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Totem após graduação</th>
                <th>Ordem</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($areas as $a)
                <tr>
                    <td>{{ $a->name }}</td>
                    <td><code>{{ $a->slug }}</code></td>
                    <td>{{ $a->kiosk_after_graduation ? 'Sim' : 'Não' }}</td>
                    <td>{{ $a->sort_order }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.fic.courses.index', $a) }}" class="btn btn-outline-primary btn-sm">Cursos</a>
                        <a href="{{ route('admin.fic.areas.edit', $a) }}" class="btn btn-outline-secondary btn-sm">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhuma área. Crie a área Gastronomia ou importe via seeder.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
