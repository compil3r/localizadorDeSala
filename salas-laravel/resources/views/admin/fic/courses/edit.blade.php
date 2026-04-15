@extends('layouts.admin')

@section('title', 'Cronograma — '.$course->name)

@section('content')
    @php
        $fmtTime = function ($v) {
            if ($v === null || $v === '') {
                return '';
            }
            if ($v instanceof \DateTimeInterface) {
                return $v->format('H:i');
            }
            $s = (string) $v;

            return strlen($s) >= 5 ? substr($s, 0, 5) : $s;
        };
    @endphp
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.fic.areas.index') }}">Áreas FIC</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.fic.courses.index', $area) }}">{{ $area->name }}</a></li>
            <li class="breadcrumb-item active">{{ $course->name }}</li>
        </ol>
    </nav>
    <h1 class="h3 mb-2">{{ $course->name }}</h1>
    <p class="text-muted mb-4">Cronograma por data (encontros). Cada bloco é uma aula ou evento no calendário.</p>

    <form method="post" action="{{ route('admin.fic.courses.update', [$area, $course]) }}" class="row g-3 mb-5 col-lg-10">
        @csrf
        @method('PUT')
        <div class="col-md-8">
            <label class="form-label" for="name">Nome do curso</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $course->name) }}" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label" for="sort_order">Ordem</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control"
                   value="{{ old('sort_order', $course->sort_order) }}" min="0">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                       {{ old('is_active', $course->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Ativo</label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Salvar dados do curso</button>
        </div>
    </form>

    <h2 class="h5 mb-3">Encontros</h2>
    @forelse($course->sessions as $s)
        <div class="border rounded p-3 mb-3 bg-white shadow-sm">
            <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-start">
                <form method="post" action="{{ route('admin.fic.sessions.update', [$area, $course, $s]) }}" class="flex-grow-1 w-100">
                    @csrf
                    @method('PUT')
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-2">
                            <label class="form-label">Data</label>
                            <input type="date" name="session_date" class="form-control form-control-sm"
                                   value="{{ old('session_date', $s->session_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Início</label>
                            <input type="time" name="starts_at" class="form-control form-control-sm" value="{{ old('starts_at', $fmtTime($s->starts_at)) }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Fim</label>
                            <input type="time" name="ends_at" class="form-control form-control-sm" value="{{ old('ends_at', $fmtTime($s->ends_at)) }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Sala</label>
                            <input type="text" name="room" class="form-control form-control-sm" maxlength="64" value="{{ old('room', $s->room) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Docente</label>
                            <input type="text" name="docente" class="form-control form-control-sm" maxlength="255" value="{{ old('docente', $s->docente) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Rótulo (opcional)</label>
                            <input type="text" name="label" class="form-control form-control-sm" maxlength="255" value="{{ old('label', $s->label) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $s->sort_order) }}" min="0">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Salvar</button>
                        </div>
                    </div>
                </form>
                <form method="post" action="{{ route('admin.fic.sessions.destroy', [$area, $course, $s]) }}"
                      onsubmit="return confirm('Remover este encontro?');" class="flex-shrink-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-muted mb-4">Nenhum encontro cadastrado. Use o formulário abaixo.</p>
    @endforelse

    <h2 class="h5 mb-3">Adicionar encontro</h2>
    <form method="post" action="{{ route('admin.fic.sessions.store', [$area, $course]) }}" class="row g-2 col-lg-10 mb-5">
        @csrf
        <div class="col-md-2">
            <label class="form-label">Data</label>
            <input type="date" name="session_date" class="form-control @error('session_date') is-invalid @enderror"
                   value="{{ old('session_date') }}" required>
            @error('session_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Início</label>
            <input type="time" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Fim</label>
            <input type="time" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sala</label>
            <input type="text" name="room" class="form-control" maxlength="64" value="{{ old('room') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Docente</label>
            <input type="text" name="docente" class="form-control" maxlength="255" value="{{ old('docente') }}">
        </div>
        <div class="col-md-8">
            <label class="form-label">Rótulo (opcional)</label>
            <input type="text" name="label" class="form-control" maxlength="255" value="{{ old('label') }}" placeholder="Ex.: Aula 1 — Boas-vindas">
        </div>
        <div class="col-md-2">
            <label class="form-label">Ordem</label>
            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Adicionar</button>
        </div>
    </form>

    <form method="post" action="{{ route('admin.fic.courses.destroy', [$area, $course]) }}"
          onsubmit="return confirm('Excluir este curso e todo o cronograma?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm">Excluir curso</button>
    </form>
@endsection
