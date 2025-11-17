<?php
require __DIR__ . '/products.php';
$page_title = 'FAST PIZZA — Меню';
require __DIR__ . '/header.php';
?>

		<main class="container">
			<section class="product-grid">
				<?php foreach ($products as $p): ?>
					<article class="product">
						<div class="thumb">
							<?php if (!empty($p['img'])): ?>
								<img src="<?php echo htmlspecialchars($p['img']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
							<?php else: ?>
								<div class="thumb-placeholder">Зображення</div>
							<?php endif; ?>
						</div>
						<div class="pmeta">
							<span class="pname"><?php echo htmlspecialchars($p['name']); ?></span>
							<span class="price"><?php echo $p['price']; ?> грн</span>
						</div>
						<a class="add-button" href="cart.php?add=<?php echo $p['id']; ?>">Додати</a>
					</article>
				<?php endforeach; ?>
			</section>

		<section class="drinks">
			<h2>Напої</h2>
			<div class="drinks-list">
				<?php foreach ($drinks as $d): ?>
					<article class="drink">
						<div class="dmeta">
							<span class="dname"><?php echo htmlspecialchars($d['name']); ?></span>
							<span class="price"><?php echo $d['price']; ?> грн</span>
						</div>
						<a class="add-button" href="cart.php?add_drink=<?php echo urlencode($d['key']); ?>">Додати</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		</main>

		<?php require __DIR__ . '/footer.php'; ?>
