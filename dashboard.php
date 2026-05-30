<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$activeLoans = (int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE date_of_return IS NULL')->fetchColumn();
$totalLoans = (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
$user = currentUser();
include __DIR__ . '/includes/header.php';
?>
<div class="row align-items-center mb-4">
    <div class="col-lg-8">
        <h1 class="fw-bold">Panel principal</h1>
        <p class="text-muted mb-0">Bienvenido, <?= e($user['username']) ?>. Tu rol es <strong><?= e(spanishRole($user['role_name'])) ?></strong>.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Usuarios</div>
                <div class="display-6 fw-bold"><?= $totalUsers ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Libros</div>
                <div class="display-6 fw-bold"><?= $totalBooks ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Préstamos activos</div>
                <div class="display-6 fw-bold"><?= $activeLoans ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Transacciones</div>
                <div class="display-6 fw-bold"><?= $totalLoans ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php if (hasRole(['Administrator'])): ?>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Gestión de usuarios</h5>
                <p class="card-text">Crear, editar, eliminar y asignar roles a los usuarios.</p>
                <a href="users.php" class="btn btn-primary">Abrir usuarios</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (hasRole(['Administrator', 'Librarian'])): ?>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Gestión de libros</h5>
                <p class="card-text">Agregar, editar y eliminar libros del catálogo.</p>
                <a href="books.php" class="btn btn-primary">Abrir libros</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Catálogo y préstamos</h5>
                <p class="card-text">Explorar libros, solicitar préstamos y devolver ejemplares.</p>
                <a href="catalog.php" class="btn btn-primary">Ver catálogo</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
