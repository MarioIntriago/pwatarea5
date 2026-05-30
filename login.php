<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Ingresa usuario/correo y contraseña.';
    } else {
        $stmt = $pdo->prepare('SELECT users.*, roles.name AS role_name FROM users INNER JOIN roles ON users.role_id = roles.id WHERE users.email = :email OR users.username = :username LIMIT 1');
$stmt->execute([
    'email' => $login,
    'username' => $login
]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name']
            ];
            redirect('dashboard.php');
        } else {
            $error = 'Credenciales incorrectas.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Biblioteca Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow login-card">
        <div class="card-body p-4">
            <h1 class="h3 text-center mb-1 fw-bold text-primary">Biblioteca Online</h1>
            <p class="text-center text-muted mb-4">Acceso de usuarios por roles</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label">Usuario o correo</label>
                    <input type="text" name="login" class="form-control" required autofocus>
                    <div class="invalid-feedback">Este campo es obligatorio.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="invalid-feedback">Este campo es obligatorio.</div>
                </div>
                <button class="btn btn-primary w-100" type="submit">Ingresar</button>
            </form>
            <hr>
            <div class="small text-muted">
                <strong>Usuarios de prueba:</strong><br>
                Admin: admin / admin123<br>
                Bibliotecario: bibliotecario / biblio123<br>
                Lector: lector / lector123
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
