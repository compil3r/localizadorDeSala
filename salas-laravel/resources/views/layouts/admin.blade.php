<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Painel') – Salas UniSenac</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="container py-4">
        <header class="admin-header mb-4">
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary rounded-2">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold" href="{{ route('admin.courses.index') }}">Salas UniSenac</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"
                            aria-controls="adminNav" aria-expanded="false" aria-label="Menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="adminNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link {{ ($navCurrent ?? '') === 'ucs' ? 'active' : '' }}"
                                   href="{{ route('admin.disciplines.index') }}">Unidades curriculares</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ ($navCurrent ?? '') === 'oferta' ? 'active' : '' }}"
                                   href="{{ route('admin.courses.index') }}">Oferta 2026/1</a>
                            </li>
                            @if(auth()->user()->isAdmin())
                                <li class="nav-item">
                                    <a class="nav-link {{ ($navCurrent ?? '') === 'coordenadores' ? 'active' : '' }}"
                                       href="{{ route('admin.coordinators.index') }}">Coordenadores</a>
                                </li>
                            @endif
                        </ul>
                        <span class="navbar-text text-white-50">{{ auth()->user()->name }}</span>
                        <form method="post" action="{{ route('logout') }}" class="d-inline ms-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm">Sair</button>
                        </form>
                    </div>
                </div>
            </nav>
        </header>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-warning alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
