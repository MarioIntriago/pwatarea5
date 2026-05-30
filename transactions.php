<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

function goTransactions(string $type, string $message): void
{
    redirect('transactions.php?' . $type . '=' . urlencode($message));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'issue' && hasRole(['Administrator', 'Librarian'])) {
            $userId = (int)($_POST['user_id'] ?? 0);
            $bookId = (int)($_POST['book_id'] ?? 0);

            $reader = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role_id = 3');
            $reader->execute([$userId]);
            if (!$reader->fetch()) {
                goTransactions('error', 'Selecciona un lector válido.');
            }

            $bookStmt = $pdo->prepare('SELECT books.*, (books.quantity - COALESCE(active.total, 0)) AS available FROM books LEFT JOIN (SELECT book_id, COUNT(*) AS total FROM transactions WHERE date_of_return IS NULL GROUP BY book_id) active ON books.id = active.book_id WHERE books.id = ?');
            $bookStmt->execute([$bookId]);
            $book = $bookStmt->fetch();
            if (!$book || (int)$book['available'] <= 0) {
                goTransactions('error', 'No hay ejemplares disponibles.');
            }

            $repeat = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ? AND book_id = ? AND date_of_return IS NULL');
            $repeat->execute([$userId, $bookId]);
            if ((int)$repeat->fetchColumn() > 0) {
                goTransactions('error', 'Ese lector ya tiene ese libro prestado.');
            }

            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, book_id, date_of_issue, date_of_return) VALUES (?, ?, CURDATE(), NULL)');
            $stmt->execute([$userId, $bookId]);
            goTransactions('success', 'Préstamo registrado correctamente.');
        }

        if ($action === 'return') {
            $transactionId = (int)($_POST['transaction_id'] ?? 0);

            if (hasRole(['Reader'])) {
                $stmt = $pdo->prepare('UPDATE transactions SET date_of_return = CURDATE() WHERE id = ? AND user_id = ? AND date_of_return IS NULL');
                $stmt->execute([$transactionId, currentUser()['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE transactions SET date_of_return = CURDATE() WHERE id = ? AND date_of_return IS NULL');
                $stmt->execute([$transactionId]);
            }

            if ($stmt->rowCount() === 0) {
                goTransactions('error', 'No se pudo registrar la devolución.');
            }
            goTransactions('success', 'Devolución registrada correctamente.');
        }
    } catch (PDOException $e) {
        goTransactions('error', 'No se pudo completar la operación.');
    }
}

$readers = [];
$availableBooks = [];
if (hasRole(['Administrator', 'Librarian'])) {
    $readers = $pdo->query('SELECT id, username, email FROM users WHERE role_id = 3 ORDER BY username')->fetchAll();
    $availableBooks = $pdo->query('SELECT books.*, (books.quantity - COALESCE(active.total, 0)) AS available FROM books LEFT JOIN (SELECT book_id, COUNT(*) AS total FROM transactions WHERE date_of_return IS NULL GROUP BY book_id) active ON books.id = active.book_id WHERE (books.quantity - COALESCE(active.total, 0)) > 0 ORDER BY books.title')->fetchAll();
}

if (hasRole(['Reader'])) {
    $stmt = $pdo->prepare('SELECT transactions.*, users.username, users.email, books.title, books.author FROM transactions INNER JOIN users ON transactions.user_id = users.id INNER JOIN books ON transactions.book_id = books.id WHERE transactions.user_id = ? ORDER BY transactions.id DESC');
    $stmt->execute([currentUser()['id']]);
} else {
    $stmt = $pdo->query('SELECT transactions.*, users.username, users.email, books.title, books.author FROM transactions INNER JOIN users ON transactions.user_id = users.id INNER JOIN books ON transactions.book_id = books.id ORDER BY transactions.id DESC');
}
$transactions = $stmt->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<h1 class="fw-bold mb-3">Préstamos y devoluciones</h1>

<?php if (hasRole(['Administrator', 'Librarian'])): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Registrar préstamo para un lector</div>
    <div class="card-body">
        <form method="post" class="row g-3 needs-validation" novalidate>
            <input type="hidden" name="action" value="issue">
            <div class="col-md-5">
                <label class="form-label">Lector</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Seleccione un lector</option>
                    <?php foreach ($readers as $reader): ?>
                        <option value="<?= $reader['id'] ?>"><?= e($reader['username']) ?> - <?= e($reader['email']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Selecciona un lector.</div>
            </div>
            <div class="col-md-5">
                <label class="form-label">Libro disponible</label>
                <select name="book_id" class="form-select" required>
                    <option value="">Seleccione un libro</option>
                    <?php foreach ($availableBooks as $book): ?>
                        <option value="<?= $book['id'] ?>"><?= e($book['title']) ?> | disponibles: <?= (int)$book['available'] ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Selecciona un libro.</div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-success w-100">Prestar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header fw-semibold"><?= hasRole(['Reader']) ? 'Mis préstamos' : 'Todas las transacciones' ?></div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if (!hasRole(['Reader'])): ?><th>Lector</th><?php endif; ?>
                    <th>Libro</th>
                    <th>Autor</th>
                    <th>Fecha préstamo</th>
                    <th>Fecha devolución</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$transactions): ?>
                <tr><td colspan="8" class="text-center text-muted">No hay transacciones registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($transactions as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <?php if (!hasRole(['Reader'])): ?><td><?= e($row['username']) ?></td><?php endif; ?>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['author']) ?></td>
                    <td><?= e($row['date_of_issue']) ?></td>
                    <td><?= $row['date_of_return'] ? e($row['date_of_return']) : '-' ?></td>
                    <td>
                        <?php if ($row['date_of_return']): ?>
                            <span class="badge bg-secondary">Devuelto</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Prestado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$row['date_of_return']): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="transaction_id" value="<?= $row['id'] ?>">
                                <button class="btn btn-sm btn-primary">Devolver</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">Finalizado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
