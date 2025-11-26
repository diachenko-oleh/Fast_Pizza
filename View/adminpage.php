<?php
session_start();
$page_title = 'FAST PIZZA — Admin';

require __DIR__ . '/../Model/admin_products.php';

$products = get_all_products();
$msg = $_GET['msg'] ?? '';

?>

<!doctype html>
<html lang="uk">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'FAST PIZZA'; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="styles.css">
	<link rel="stylesheet" href="admin.css">
	<style>body{padding-left:24px;padding-right:24px;background:#f5f7fb;}@media(min-width:1200px){body{padding-left:60px;padding-right:60px;}}</style>
</head>
<body>

<main class="admin-wrapper">
	<div class="admin-card">
		<div class="admin-header">
			<div>
				<div class="admin-title">Панель администратора — Товари</div>
				<div class="text-muted">Керуйте товарами: додайте, редагуйте або видаліть позиції.</div>
			</div>
			<div>
				<a href="index.php" class="btn btn-outline-secondary">Перейти на сайт</a>
			</div>
		</div>

		<?php if (!empty($msg)): ?>
			<div class="admin-message"><?php echo htmlspecialchars($msg); ?></div>
		<?php endif; ?>

		<section class="admin-add mb-3">
			<h5>Додати товар</h5>
			<form method="post" action="../Presenter/admin_actions.php" class="admin-create-form">
				<input type="hidden" name="action" value="create">
				<input type="text" name="name" class="form-control" placeholder="Назва" required style="max-width:320px; display:inline-block;">
				<input type="number" step="0.01" name="price" class="form-control" placeholder="Ціна" value="0" style="max-width:140px; display:inline-block;">
				<label class="d-inline-flex align-items-center ms-2">
					<input type="checkbox" name="isPizza" value="1" class="form-check-input me-2"> Піца
				</label>
				<button type="submit" class="btn btn-primary ms-3">Додати</button>
			</form>
		</section>

		<form method="post" action="../Presenter/admin_actions.php">
			<input type="hidden" name="action" value="bulk_update">

			<div class="table-responsive">
			<table class="table admin-table table-hover align-middle">
				<thead>
					<tr>
						<th style="width:64px;">ID</th>
						<th>Назва</th>
						<th style="width:140px;">Ціна</th>
						<th style="width:120px;">Піца</th>
						<th style="width:140px;">Дії</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($products as $p): ?>
					<tr>
						<td>
							<?php echo $p['id']; ?>
							<input type="hidden" name="id[]" value="<?php echo $p['id']; ?>">
						</td>
						<td>
							<input type="text" name="name[]" value="<?php echo htmlspecialchars($p['name']); ?>" class="form-control">
						</td>
						<td>
							<input type="number" step="0.01" name="price[]" value="<?php echo $p['price']; ?>" class="form-control">
						</td>
						<td>
							<input type="checkbox" name="isPizza[]" value="<?php echo $p['id']; ?>" <?php echo !empty($p['ispizza']) ? 'checked' : ''; ?> class="form-check-input">
						</td>
						<td class="admin-actions">
							<button type="submit" name="delete_id" value="<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Видалити товар?');">Видалити</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>

			<div class="d-flex justify-content-end mt-3">
				<button type="submit" class="btn btn-success">Зберегти зміни</button>
			</div>
		</form>
	</div>
</main>

<script>
function copyIndex(id) {
	const row = Array.from(document.querySelectorAll('input[name="id[]"]')).find(i => i.value == id);
	if (!row) return;
	const tr = row.closest('tr');
	const name = tr.querySelector('input[name="name[]"]').value;
	const price = tr.querySelector('input[name="price[]"]').value;
	const addName = document.querySelector('form.admin-create-form input[name="name"]');
	const addPrice = document.querySelector('form.admin-create-form input[name="price"]');
	if (addName) addName.value = name + ' (копія)';
	if (addPrice) addPrice.value = price;
	window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>