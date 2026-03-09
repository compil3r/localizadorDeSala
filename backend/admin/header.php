<?php
// Menu superior do painel admin. Define $navCurrent antes de incluir:
// 'ucs' | 'oferta' | 'coordenadores' | null
$navCurrent = $navCurrent ?? null;
$user = $user ?? auth_current_user();
?>
<header class="admin-header mb-4">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary rounded-2">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">Salas UniSenac</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $navCurrent === 'ucs' ? 'active' : '' ?>" href="disciplinas.php">Unidades curriculares</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $navCurrent === 'oferta' ? 'active' : '' ?>" href="index.php">Oferta 2026/1</a>
                    </li>
                    <?php if ($user['role'] === 'ADMIN'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $navCurrent === 'coordenadores' ? 'active' : '' ?>" href="coordenadores.php">Coordenadores</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <span class="navbar-text text-white-50"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                <a class="btn btn-outline-light btn-sm ms-2" href="login.php?logout=1">Sair</a>
            </div>
        </div>
    </nav>
</header>
