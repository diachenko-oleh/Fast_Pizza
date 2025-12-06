<?php
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$page_title = 'FAST PIZZA — Меню';

require_once __DIR__ . '/../Model/products.php';
require_once __DIR__ . '/header.php';
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
                <button 
                    class="add-button" 
                    data-id="<?php echo $p['id']; ?>"
                    data-name="<?php echo htmlspecialchars($p['name']); ?>"
                    data-price="<?php echo $p['price']; ?>"
                    data-img="<?php echo htmlspecialchars($p['img'] ?? 'images/pizza.jpg'); ?>"
                    data-type="pizza"
                    onclick="addToCart(this)">
                    Додати
                </button>
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
                    <button 
                        class="add-button"
                        data-id="<?php echo $d['id']; ?>"
                        data-name="<?php echo htmlspecialchars($d['name']); ?>"
                        data-price="<?php echo $d['price']; ?>"
                        data-type="drink"
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
    const id = button.dataset.id;
    const name = button.dataset.name;
    const price = button.dataset.price;
    const type = button.dataset.type;
    const img = button.dataset.img || '';

    const formData = new FormData();
    formData.append('action', type === 'pizza' ? 'add_pizza' : 'add_drink');
    formData.append('id', id);
    formData.append('name', name);
    formData.append('price', price);
    
    if (img) {
        formData.append('img', img);
    }

    button.disabled = true;
    button.textContent = 'Додається...';

    fetch('../Presenter/menu_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            updateCartCounter(data.cart_count);
        } else {
            showNotification(data.message || 'Помилка додавання', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Помилка з\'єднання з сервером', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = 'Додати';
    });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.add('removing');
        setTimeout(() => notification.remove(), 500);
    }, 2500);
}

function updateCartCounter(count) {
    const counter = document.querySelector('.cart-counter');
    if (counter && count > 0) {
        counter.textContent = count;
        counter.style.display = 'inline-block';
    }
}
</script>

<?php require __DIR__ . '/footer.php'; ?>