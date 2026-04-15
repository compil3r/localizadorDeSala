@extends('layouts.admin')

@section('title', 'Cursos FIC — '.$area->name)

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.fic.areas.index') }}">Áreas FIC</a></li>
            <li class="breadcrumb-item active">{{ $area->name }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Cursos em {{ $area->name }}</h1>
        <a href="{{ route('admin.fic.courses.create', $area) }}" class="btn btn-primary btn-sm">Novo curso</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Ativo</th>
                <th>Ordem</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($courses as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->is_active ? 'Sim' : 'Não' }}</td>
                    <td>{{ $c->sort_order }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.fic.courses.edit', [$area, $c]) }}" class="btn btn-outline-primary btn-sm">Cronograma</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhum curso nesta área.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
