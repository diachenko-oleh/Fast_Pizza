<?php
session_start();
require __DIR__ . '/products.php';

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

function find_product($products, $id) {
  foreach ($products as $p) if ($p['id'] == $id) return $p;
  return null;
}

function find_by_key($arr, $key) {
  foreach ($arr as $a) if ($a['key'] === $key) return $a;
  return null;
}

if (!empty($_POST['action']) && $_POST['action'] === 'add_to_cart') {
  header('Content-Type: application/json');
  
  if (!empty($_POST['id'])) {
    $id = intval($_POST['id']);
    $p = find_product($products, $id);
    if ($p) {
      $k = 'pizza_' . $id;
      if (!isset($_SESSION['cart'][$k])) {
        $_SESSION['cart'][$k] = ['type'=>'pizza','id'=>$id,'name'=>$p['name'],'price'=>$p['price'],'img'=>$p['img'],'qty'=>0];
      }
      $_SESSION['cart'][$k]['qty']++;
      echo json_encode(['success' => true, 'message' => $p['name'] . ' додана до кошика']);
      exit;
    }
  }
  
  if (!empty($_POST['drink_key'])) {
    $dkey = $_POST['drink_key'];
    $d = find_by_key($drinks, $dkey);
    if ($d) {
      $k = 'drink_' . $d['key'];
      if (!isset($_SESSION['cart'][$k])) {
        $_SESSION['cart'][$k] = ['type'=>'drink','key'=>$d['key'],'name'=>$d['name'],'price'=>$d['price'],'img'=>'images/drink.jpg','qty'=>0];
      }
      $_SESSION['cart'][$k]['qty']++;
      echo json_encode(['success' => true, 'message' => $d['name'] . ' додана до кошика']);
      exit;
    }
  }
  
  echo json_encode(['success' => false, 'message' => 'Помилка додавання товару']);
  exit;
}

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
						<button class="add-button" data-id="<?php echo $p['id']; ?>" onclick="addToCart(this)">Додати</button>
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
						<button class="add-button" data-drink="<?php echo htmlspecialchars($d['key']); ?>" onclick="addToCart(this)">Додати</button>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		</main>

		<script>
			function addToCart(button) {
				const id = button.getAttribute('data-id');
				const drinkKey = button.getAttribute('data-drink');
				
				const formData = new FormData();
				formData.append('action', 'add_to_cart');
				if (id) formData.append('id', id);
				if (drinkKey) formData.append('drink_key', drinkKey);
				
				fetch('index.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						showNotification(data.message);
						button.classList.add('added');
						setTimeout(() => button.classList.remove('added'), 1500);
					} else {
						alert('Помилка: ' + data.message);
					}
				})
				.catch(error => console.error('Помилка:', error));
			}
			
			function showNotification(message) {
				const notification = document.createElement('div');
				notification.className = 'notification';
				notification.textContent = message;
				document.body.appendChild(notification);
				
				setTimeout(() => {
					notification.classList.add('show');
				}, 10);
				
				setTimeout(() => {
					notification.classList.add('removing');
					setTimeout(() => notification.remove(), 500);
				}, 2500);
			}
		</script>

		<?php require __DIR__ . '/footer.php'; ?>