<?php
session_start();
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
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
                        <img src="images/pizza.jpg" alt="<?php echo htmlspecialchars($p['name']); ?>" />
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
        name = article.querySelector('.pname').textContent.trim();
        price = parseFloat(article.querySelector('.price').textContent.replace(/[^0-9.]/g, ''));
        img = article.querySelector('img')?.getAttribute('src') || '';
    }

    if (drinkKey) {
        const article = button.closest('.drink');
        name = article.querySelector('.dname').textContent.trim();
        price = parseFloat(article.querySelector('.price').textContent.replace(/[^0-9.]/g, ''));
    }

    const formData = new FormData();

    if (id) formData.append('action', 'add_pizza');
    if (drinkKey) formData.append('action', 'add_drink');

    if (id) formData.append('id', id);
    if (drinkKey) formData.append('drink_key', drinkKey);

    formData.append('name', name);
    formData.append('price', price);
    if (img) formData.append('img', img);

    fetch('../Presenter/index_actions.php', {
    method: 'POST',
    body: formData
    })
    .then(r => r.json())
    .then(data => showNotification(data.message));
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