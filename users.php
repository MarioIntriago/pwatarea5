<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['Administrator']);

function goUsers(string $type, string $message): void
{
    redirect('users.php?' . $type . '=' . urlencode($message));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $roleId = (int)($_POST['role_id'] ?? 0);

            if ($username === '' || $email === '' || $password === '' || $roleId <= 0) {
                goUsers('error', 'Completa todos los campos para crear el usuario.');
            }

            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $roleId]);
            goUsers('success', 'Usuario creado correctamente.');
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $roleId = (int)($_POST['role_id'] ?? 0);

            if ($id <= 0 || $username === '' || $email === '' || $roleId <= 0) {
                goUsers('error', 'Datos inválidos para actualizar el usuario.');
            }

            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, password = ?, role_id = ? WHERE id = ?');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $roleId, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?');
                $stmt->execute([$username, $email, $roleId, $id]);
            }

            if ($id === (int)currentUser()['id']) {
                $stmt = $pdo->prepare('SELECT users.*, roles.name AS role_name FROM users INNER JOIN roles ON users.role_id = roles.id WHERE users.id = ?');
                $stmt->execute([$id]);
                $updated = $stmt->fetch();
                $_SESSION['user'] = [
                    'id' => $updated['id'],
                    'username' => $updated['username'],
                    'email' => $updated['email'],
                    'role_id' => $updated['role_id'],
                    'role_name' => $updated['role_name']
                ];
            }

            goUsers('success', 'Usuario actualizado correctamente.');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)currentUser()['id']) {
                goUsers('error', 'No puedes eliminar tu propio usuario mientras estás conectado.');
            }
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            goUsers('success', 'Usuario eliminado correctamente.');
        }
    } catch (PDOException $e) {
        goUsers('error', 'No se pudo completar la operación. Revisa que el correo no esté repetido y que el usuario no tenga préstamos registrados.');
    }
}

$roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
$users = $pdo->query('SELECT users.*, roles.name AS role_name FROM users INNER JOIN roles ON users.role_id = roles.id ORDER BY users.id')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1 class="fw-bold mb-3">Gestión de usuarios</h1>

<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Crear nuevo usuario</div>
    <div class="card-body">
        <form method="post" class="row g-3 needs-validation" novalidate>
            <input type="hidden" name="action" value="create">
            <div class="col-md-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="username" class="form-control" required>
                <div class="invalid-feedback">Campo obligatorio.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" required>
                <div class="invalid-feedback">Ingresa un correo válido.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control password-check" required minlength="6">
                <div class="invalid-feedback">Mínimo 6 caracteres.</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Rol</label>
                <select name="role_id" class="form-select" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= e(spanishRole($role['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-success w-100">Crear</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold">Usuarios registrados</div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Nueva contraseña</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>
                <tr>
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <td><?= $row['id'] ?></td>
                        <td><input type="text" name="username" class="form-control form-control-sm" value="<?= e($row['username']) ?>" required></td>
                        <td><input type="email" name="email" class="form-control form-control-sm" value="<?= e($row['email']) ?>" required></td>
                        <td>
                            <select name="role_id" class="form-select form-select-sm" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= $role['id'] == $row['role_id'] ? 'selected' : '' ?>><?= e(spanishRole($role['name'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="password" name="password" class="form-control form-control-sm" placeholder="Opcional"></td>
                        <td class="text-nowrap">
                            <button class="btn btn-warning btn-sm">Guardar</button>
                    </form>
                            <form method="post" class="d-inline delete-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
