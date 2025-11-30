<?php
session_start();
$page_title = 'FAST PIZZA — Products';

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
				<div class="admin-title">Панель адміністратора — Редагування товарів</div>
			</div>
			<div>
				<a href="admin_page.php" class="btn btn-outline-secondary">Назад до панелі</a>
			</div>
		</div>

		<?php if (!empty($msg)): ?>
			<div class="admin-message"><?php echo htmlspecialchars($msg); ?></div>
		<?php endif; ?>

		<section class="admin-add mb-3">
			<h5>Додати товар</h5>
			<form method="post" action="../Presenter/admin_actions.php" class="admin-create-form" onsubmit="return validatePrice(this);">
				<input type="hidden" name="action" value="create">
				<input type="text" name="name" class="form-control" placeholder="Назва" required style="max-width:320px; display:inline-block;">
				<input type="number" step="0.01" name="price" class="form-control" placeholder="Ціна" min="0.01" required style="max-width:140px; display:inline-block;">
				<label class="d-inline-flex align-items-center ms-2">
					<input type="checkbox" name="isPizza" value="1" class="form-check-input me-2"> Піца
				</label>
				<button type="submit" class="btn btn-primary ms-3">Додати</button>
			</form>
		</section>

		<form method="post" action="../Presenter/admin_actions.php" onsubmit="return validateBulkPrices(this);">
			<input type="hidden" name="action" value="bulk_update">

			<div class="table-responsive">
			<table class="table admin-table table-hover align-middle">
				<thead>
					<tr>
						<th>Назва</th>
						<th style="width:140px;">Ціна</th>
						<th style="width:120px;">Піца</th>
						<th style="width:140px;">Дії</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($products as $p): ?>
					<tr>
						<input type="hidden" name="id[]" value="<?php echo $p['id']; ?>">
						<td>
							<input type="text" name="name[]" value="<?php echo htmlspecialchars($p['name']); ?>" class="form-control" required>
						</td>
						<td>
							<input type="number" step="0.01" name="price[]" value="<?php echo $p['price']; ?>" class="form-control price-input" min="0.01" required>
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



	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
	(function(){
		var isDirty = false;

		function setDirty(){ isDirty = true; }
		function clearDirty(){ isDirty = false; }

		// Attach listeners to inputs inside the admin card
		var adminCard = document.querySelector('.admin-card');
		if (adminCard) {
			var inputs = adminCard.querySelectorAll('input, textarea, select');
			for (var i = 0; i < inputs.length; i++) {
				// ignore readonly/disabled
				if (inputs[i].readOnly || inputs[i].disabled) continue;
				inputs[i].addEventListener('change', setDirty);
				inputs[i].addEventListener('input', setDirty);
			}
		}

		// Clear dirty state when any form is submitted (create / bulk / delete)
		var forms = document.querySelectorAll('form');
		for (var f = 0; f < forms.length; f++) {
			forms[f].addEventListener('submit', function(){
				// small timeout to allow submit to proceed
				clearDirty();
				setTimeout(clearDirty, 1000);
			});
		}

		// beforeunload handler
		window.addEventListener('beforeunload', function(e){
			if (!isDirty) return undefined;
			var confirmationMessage = 'Є незбережені зміни. Ви впевнені, що хочете покинути сторінку?';
			e.returnValue = confirmationMessage; // Gecko, Trident, Chrome 34+
			return confirmationMessage; // Gecko, WebKit, Chrome <34
		});

		// Simple client-side validators referenced in form `onsubmit` attributes
		window.validatePrice = function(form){
			var priceInput = form.querySelector('input[name="price"]');
			if (!priceInput) return true;
			var v = parseFloat(priceInput.value);
			if (!isFinite(v) || v <= 0) {
				alert('Ціна повинна бути більше нуля');
				return false;
			}
			return true;
		};

		window.validateBulkPrices = function(form){
			var prices = form.querySelectorAll('input[name^="price"]');
			for (var i = 0; i < prices.length; i++){
				var v = parseFloat(prices[i].value);
				if (!isFinite(v) || v <= 0){
					var row = prices[i].closest('tr');
					var nameInput = row ? row.querySelector('input[name^="name"]') : null;
					var prod = nameInput ? (nameInput.value || ('рядок ' + (i+1))) : ('рядок ' + (i+1));
					alert('Невірна ціна для: ' + prod + '. Ціна повинна бути більше нуля.');
					return false;
				}
			}
			return true;
		};

	})();
	</script>
</body>
</html>
