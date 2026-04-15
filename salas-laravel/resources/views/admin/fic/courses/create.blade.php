@extends('layouts.admin')

@section('title', 'Novo curso FIC')

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.fic.areas.index') }}">Áreas FIC</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.fic.courses.index', $area) }}">{{ $area->name }}</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol>
    </nav>
    <h1 class="h3 mb-3">Novo curso</h1>
    <form method="post" action="{{ route('admin.fic.courses.store', $area) }}" class="col-lg-8">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">Nome do curso</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required maxlength="255" placeholder="Ex.: Oficina de veganos">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="sort_order">Ordem na lista</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
        </div>
        <div class="form-check mb-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   {{ old('is_active', '1') != '0' ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Ativo (aparece no totem)</label>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('admin.fic.courses.index', $area) }}" class="btn btn-link">Cancelar</a>
    </form>
@endsection
