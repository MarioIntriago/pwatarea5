<?php
require_once __DIR__ . '/auth.php';
$user = currentUser();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biblioteca Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">Biblioteca Online</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <?php if ($user): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="catalog.php">Catálogo</a></li>
                <?php if (hasRole(['Administrator'])): ?>
                    <li class="nav-item"><a class="nav-link" href="users.php">Usuarios</a></li>
                <?php endif; ?>
                <?php if (hasRole(['Administrator', 'Librarian'])): ?>
                    <li class="nav-item"><a class="nav-link" href="books.php">Libros</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="transactions.php">Préstamos</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 text-white">
                <span class="small"><?= e($user['username']) ?> | <?= e(spanishRole($user['role_name'])) ?></span>
                <a class="btn btn-light btn-sm" href="logout.php">Salir</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container py-4">
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
