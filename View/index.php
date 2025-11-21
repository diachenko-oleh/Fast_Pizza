<?php
session_start();

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'add_to_cart') {
  header('Content-Type: application/json');

  if (!empty($_POST['id'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'] ?? ('Піца #' . $id);
    $price = is_numeric($_POST['price']) ? floatval($_POST['price']) : 0;
    $img = $_POST['img'] ?? '';

    $k = 'pizza_' . $id;
    if (!isset($_SESSION['cart'][$k])) {
      $_SESSION['cart'][$k] = [
        'type' => 'pizza',
        'id'   => $id,
        'name' => $name,
        'price'=> $price,
        'img'  => $img,
        'qty'  => 0
      ];
    }
    $_SESSION['cart'][$k]['qty']++;
    echo json_encode(['success' => true, 'message' => $name . ' додана до кошика']);
    exit;
  }

  if (!empty($_POST['drink_key'])) {
    $dkey = $_POST['drink_key'];
    $name = $_POST['name'] ?? ('Напій ' . $dkey);
    $price = is_numeric($_POST['price']) ? floatval($_POST['price']) : 0;

    $k = 'drink_' . $dkey;
    if (!isset($_SESSION['cart'][$k])) {
      $_SESSION['cart'][$k] = [
        'type' => 'drink',
        'key'  => $dkey,
        'name' => $name,
        'price'=> $price,
        'qty'  => 0
      ];
    }
    $_SESSION['cart'][$k]['qty']++;
    echo json_encode(['success' => true, 'message' => $name . ' додана до кошика']);
    exit;
  }

  echo json_encode(['success' => false, 'message' => 'Помилка додавання товару']);
  exit;
}

$page_title = 'FAST PIZZA — Меню';
require __DIR__ . '/../Model/products.php';

foreach ($drinks as &$d) {
    if (!isset($d['key']) || empty($d['key'])) {
        $d['key'] = 'drink_' . $d['id']; 
    }
}
unset($d);

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
                    <button class="add-button"
                            data-drink="<?php echo htmlspecialchars($d['key']); ?>"
                            onclick="addToCart(this)">
                            Додати
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<script>
function addToCart(button) {
    const id = button.getAttribute('data-id');
    const drinkKey = button.getAttribute('data-drink');
    let name = '';
    let price = 0;
    let img = '';

    if (id) {
        const article = button.closest('.product');
        name = article?.querySelector('.pname')?.textContent?.trim() || '';
        const priceText = article?.querySelector('.price')?.textContent || '';
        price = parseFloat(priceText.replace(/[^0-9.,]/g, '').replace(',', '.')) || 0;
        img = article?.querySelector('img')?.getAttribute('src') || '';
    }

    if (drinkKey) {
        const article = button.closest('.drink');
        name = article?.querySelector('.dname')?.textContent?.trim() || name;
        const priceText = article?.querySelector('.price')?.textContent || '';
        price = parseFloat(priceText.replace(/[^0-9.,]/g, '').replace(',', '.')) || price;
    }

    const formData = new FormData();
    formData.append('action', 'add_to_cart');
    if (id) formData.append('id', id);
    if (drinkKey) formData.append('drink_key', drinkKey);
    if (name) formData.append('name', name);
    formData.append('price', String(price));
    if (img) formData.append('img', img);

    button.classList.add('added');
    showNotification((name || 'Товар') + ' додається...');

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message);
        } else {
            alert('Помилка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Помилка:', error);
        alert('Помилка мережі. Спробуйте ще раз.');
    })
    .finally(() => {
        setTimeout(() => button.classList.remove('added'), 900);
    });
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