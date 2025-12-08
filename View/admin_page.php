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
				<div class="admin-title">Панель адміністратора</div>
			</div>
			<div>
				<a href="menu_page.php" class="btn btn-outline-secondary">Перейти до сайту</a>
			</div>
		</div>

		<?php if (!empty($msg)): ?>
			<div class="admin-message"><?php echo htmlspecialchars($msg); ?></div>
		<?php endif; ?>

		<section class="mb-3">
			<p>Додавати або редагувати товари можна на сторінці редагування товарів.</p>
			<a href="admin_edit_page.php" class="btn btn-primary">Перейти до товарів</a>
			<a href="admin_orders.php" class="btn btn-warning">Переглянути замовлення</a>
			<a href="admin_courier.php" class="btn btn-info text-white">Керування кур'єрами</a>
		</section>
	</div>
</main>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>