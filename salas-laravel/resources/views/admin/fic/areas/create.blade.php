@extends('layouts.admin')

@section('title', 'Nova área FIC')

@section('content')
    <h1 class="h3 mb-3">Nova área</h1>
    <form method="post" action="{{ route('admin.fic.areas.store') }}" class="col-lg-8">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">Nome</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">Slug (URL)</label>
            <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                   value="{{ old('slug') }}" required pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="gastronomia">
            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Somente letras minúsculas, números e hífen.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="sort_order">Ordem de exibição</label>
            <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
        </div>
        <div class="form-check mb-4">
            <input type="hidden" name="kiosk_after_graduation" value="0">
            <input type="checkbox" name="kiosk_after_graduation" id="kiosk_after_graduation" class="form-check-input" value="1"
                   {{ old('kiosk_after_graduation', false) ? 'checked' : '' }}>
            <label class="form-check-label" for="kiosk_after_graduation">Mostrar no totem (cartão após os cursos de graduação)</label>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('admin.fic.areas.index') }}" class="btn btn-link">Cancelar</a>
    </form>
@endsection
