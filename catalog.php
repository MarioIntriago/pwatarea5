<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

function goCatalog(string $type, string $message): void
{
    redirect('catalog.php?' . $type . '=' . urlencode($message));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'borrow') {
        $bookId = (int)($_POST['book_id'] ?? 0);
        $userId = (int)currentUser()['id'];

        try {
            $stmt = $pdo->prepare('SELECT books.*, (books.quantity - COALESCE(active.total, 0)) AS available FROM books LEFT JOIN (SELECT book_id, COUNT(*) AS total FROM transactions WHERE date_of_return IS NULL GROUP BY book_id) active ON books.id = active.book_id WHERE books.id = ?');
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book || (int)$book['available'] <= 0) {
                goCatalog('error', 'El libro no está disponible.');
            }

            $repeat = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ? AND book_id = ? AND date_of_return IS NULL');
            $repeat->execute([$userId, $bookId]);
            if ((int)$repeat->fetchColumn() > 0) {
                goCatalog('error', 'Ya tienes este libro prestado.');
            }

            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, book_id, date_of_issue, date_of_return) VALUES (?, ?, CURDATE(), NULL)');
            $stmt->execute([$userId, $bookId]);
            goCatalog('success', 'Solicitud de préstamo registrada correctamente.');
        } catch (PDOException $e) {
            goCatalog('error', 'No se pudo registrar el préstamo.');
        }
    }
}

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT books.*, (books.quantity - COALESCE(active.total, 0)) AS available FROM books LEFT JOIN (SELECT book_id, COUNT(*) AS total FROM transactions WHERE date_of_return IS NULL GROUP BY book_id) active ON books.id = active.book_id';
if ($q !== '') {
    $sql .= ' WHERE books.title LIKE :q OR books.author LIKE :q OR books.genre LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY books.title ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <h1 class="fw-bold mb-1">Catálogo de libros</h1>
        <p class="text-muted mb-0">Busca libros por título, autor o género.</p>
    </div>
    <form class="d-flex" method="get">
        <input type="search" name="q" class="form-control me-2" placeholder="Buscar..." value="<?= e($q) ?>">
        <button class="btn btn-primary">Buscar</button>
    </form>
</div>

<div class="row g-3">
<?php if (!$books): ?>
    <div class="col-12"><div class="alert alert-info">No se encontraron libros.</div></div>
<?php endif; ?>
<?php foreach ($books as $book): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm book-card">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <h5 class="card-title mb-0"><?= e($book['title']) ?></h5>
                    <?php if ((int)$book['available'] > 0): ?>
                        <span class="badge bg-success">Disponible</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Agotado</span>
                    <?php endif; ?>
                </div>
                <p class="mb-1"><strong>Autor:</strong> <?= e($book['author']) ?></p>
                <p class="mb-1"><strong>Año:</strong> <?= e((string)$book['year']) ?></p>
                <p class="mb-1"><strong>Género:</strong> <?= e($book['genre']) ?></p>
                <p class="mb-3"><strong>Disponibles:</strong> <?= max(0, (int)$book['available']) ?> / <?= (int)$book['quantity'] ?></p>
                <div class="mt-auto">
                    <?php if (hasRole(['Reader']) && (int)$book['available'] > 0): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="borrow">
                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                            <button class="btn btn-primary w-100">Solicitar libro</button>
                        </form>
                    <?php elseif (hasRole(['Reader'])): ?>
                        <button class="btn btn-secondary w-100" disabled>No disponible</button>
                    <?php else: ?>
                        <a href="transactions.php" class="btn btn-outline-primary w-100">Gestionar préstamo</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
