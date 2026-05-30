<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['Administrator', 'Librarian']);

function goBooks(string $type, string $message): void
{
    redirect('books.php?' . $type . '=' . urlencode($message));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $year = $_POST['year'] !== '' ? (int)$_POST['year'] : null;
            $genre = trim($_POST['genre'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 0);

            if ($title === '' || $author === '' || $quantity < 0) {
                goBooks('error', 'Completa título, autor y una cantidad válida.');
            }

            $stmt = $pdo->prepare('INSERT INTO books (title, author, `year`, genre, quantity) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$title, $author, $year, $genre, $quantity]);
            goBooks('success', 'Libro agregado correctamente.');
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $year = $_POST['year'] !== '' ? (int)$_POST['year'] : null;
            $genre = trim($_POST['genre'] ?? '');
            $quantity = (int)($_POST['quantity'] ?? 0);

            if ($id <= 0 || $title === '' || $author === '' || $quantity < 0) {
                goBooks('error', 'Datos inválidos para actualizar el libro.');
            }

            $activeStmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE book_id = ? AND date_of_return IS NULL');
            $activeStmt->execute([$id]);
            $active = (int)$activeStmt->fetchColumn();
            if ($quantity < $active) {
                goBooks('error', 'La cantidad no puede ser menor que los préstamos activos de ese libro.');
            }

            $stmt = $pdo->prepare('UPDATE books SET title = ?, author = ?, `year` = ?, genre = ?, quantity = ? WHERE id = ?');
            $stmt->execute([$title, $author, $year, $genre, $quantity, $id]);
            goBooks('success', 'Libro actualizado correctamente.');
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
            $stmt->execute([$id]);
            goBooks('success', 'Libro eliminado correctamente.');
        }
    } catch (PDOException $e) {
        goBooks('error', 'No se pudo completar la operación. El libro puede tener transacciones registradas.');
    }
}

$books = $pdo->query('SELECT books.*, (books.quantity - COALESCE(active.total, 0)) AS available FROM books LEFT JOIN (SELECT book_id, COUNT(*) AS total FROM transactions WHERE date_of_return IS NULL GROUP BY book_id) active ON books.id = active.book_id ORDER BY books.id DESC')->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1 class="fw-bold mb-3">Gestión de libros</h1>

<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Agregar libro</div>
    <div class="card-body">
        <form method="post" class="row g-3 needs-validation" novalidate>
            <input type="hidden" name="action" value="create">
            <div class="col-md-3">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Autor</label>
                <input type="text" name="author" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Año</label>
                <input type="number" name="year" class="form-control" min="0" max="2100">
            </div>
            <div class="col-md-2">
                <label class="form-label">Género</label>
                <input type="text" name="genre" class="form-control">
            </div>
            <div class="col-md-1">
                <label class="form-label">Cantidad</label>
                <input type="number" name="quantity" class="form-control" min="0" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-success w-100">Agregar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold">Catálogo administrable</div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Año</th>
                    <th>Género</th>
                    <th>Cantidad</th>
                    <th>Disponibles</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $book['id'] ?>">
                        <td><?= $book['id'] ?></td>
                        <td><input type="text" name="title" class="form-control form-control-sm" value="<?= e($book['title']) ?>" required></td>
                        <td><input type="text" name="author" class="form-control form-control-sm" value="<?= e($book['author']) ?>" required></td>
                        <td><input type="number" name="year" class="form-control form-control-sm" value="<?= e((string)$book['year']) ?>"></td>
                        <td><input type="text" name="genre" class="form-control form-control-sm" value="<?= e($book['genre']) ?>"></td>
                        <td><input type="number" name="quantity" class="form-control form-control-sm" min="0" value="<?= $book['quantity'] ?>" required></td>
                        <td><span class="badge bg-info text-dark"><?= max(0, (int)$book['available']) ?></span></td>
                        <td class="text-nowrap">
                            <button class="btn btn-warning btn-sm">Guardar</button>
                    </form>
                            <form method="post" class="d-inline delete-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $book['id'] ?>">
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
