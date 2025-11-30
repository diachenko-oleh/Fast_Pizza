<?php
session_start();
require __DIR__ . '/../Model/products.php';
require __DIR__ . '/../Model/auth.php';

// Якщо користувач авторизований, отримаємо його дані для попереднього заповнення форми
$client = get_current_user_client();

$page_title = 'FAST PIZZA — Кошик';
require __DIR__ . '/header.php';

require __DIR__ . '/../Presenter/cart_actions.php';
?>

    <main class="container cart-container">
      <h2 class="mb-4">Замовлення:</h2>

      <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
          <p>Кошик порожній. Поверніться до <a href="menu_page.php">меню</a>, щоб додати товари.</p>
        </div>
      <?php else: ?>
        <div class="cart-content">
          <div class="cart-items">
            <table class="cart-table">
              <tbody>
                <?php $total = 0; foreach ($_SESSION['cart'] as $key => $item):
                  $subtotal = $item['price'] * $item['qty'];
                  $total += $subtotal;
                ?>
                  <tr class="cart-item">
                    <td class="item-image">
                      <?php
                        $isDrinkNoImage = empty($item['img']) && !empty($item['type']) && $item['type'] === 'drink';
                      ?>
                      <div class="item-thumb<?php echo $isDrinkNoImage ? ' item-thumb--empty' : ''; ?>">
                        <?php if (!empty($item['img'])): ?>
                          <img src="<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                        <?php else: ?>
                          <?php if ($isDrinkNoImage): ?>
                          <?php else: ?>
                            <img src="images/pizza.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="item-info">
                      <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                      <div class="item-price"><?php echo $item['price']; ?> грн</div>
                    </td>
                    <td class="item-controls">
                      <div class="qty-control">
                        <button type="button" class="qty-btn minus" data-key="<?php echo urlencode($key); ?>" onclick="changeQty(this, 'dec')">−</button>
                        <input type="text" class="qty-input" value="<?php echo $item['qty']; ?>" readonly>
                        <button type="button" class="qty-btn plus" data-key="<?php echo urlencode($key); ?>" onclick="changeQty(this, 'inc')">+</button>
                      </div>
                    </td>
                    <td class="item-total">
                      <strong><?php echo $subtotal; ?> грн</strong>
                    </td>
                    <td class="item-remove">
                      <button type="button" class="remove-btn" data-key="<?php echo urlencode($key); ?>" onclick="removeItem(this)" title="Видалити">✕</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="order-form">
            <form method="POST" action="cart.php" id="orderForm">
              <div class="form-group">
                <label>Ведіть ваше ім'я</label>
                <input type="text" name="name" class="form-input" placeholder="Ім'я" value="<?php echo htmlspecialchars($client['full_name'] ?? ''); ?>" required>
              </div>

              <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" class="form-input" placeholder="+380 __ ___ __ __" pattern="\+38[0-9]{9,10}" inputmode="tel" title="Формат: +38XXXXXXXXXX" value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>" required>
              </div>

              <div class="form-section">
                <label class="form-label">Оплата:</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input type="radio" name="payment" value="cash" required>
                    <span>готівкою</span>
                  </label>
                  <label class="radio-label">
                    <input type="radio" name="payment" value="card" required>
                    <span>оплата на карту</span>
                  </label>
                </div>
              </div>

              <div class="form-section">
                <label class="form-label">Дата та час доставки:</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input type="radio" name="delivery_time" value="soon" required>
                    <span>якнайшвидше</span>
                  </label>
                  <label class="radio-label">
                    <input type="radio" name="delivery_time" value="scheduled" required>
                    <span>в указаний час</span>
                  </label>
                </div>
              </div>

              <div class="form-section">
                <label class="form-label">Спосіб отримання замовлення:</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input type="radio" name="delivery_method" value="self" required>
                    <span>самовивіз</span>
                  </label>
                  <label class="radio-label">
                    <input type="radio" name="delivery_method" value="delivery" required>
                    <span>доставка</span>
                  </label>
                </div>
              </div>

              <div class="form-group">
                <label>Адреса закладу:</label>
                <input type="text" name="address" class="form-input" placeholder="оберіть закладу" required>
              </div>

              <div class="form-group comments">
                <label>Коментарій:</label>
                <textarea name="comments" class="form-textarea" placeholder="коментарій"></textarea>
              </div>

              <div class="form-total">
                <strong><?php echo $total; ?> грн</strong>
              </div>

              <button type="submit" class="submit-btn">Замовити</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </main>

    <script>
      function changeQty(btn, action) {
        const key = btn.getAttribute('data-key');
        const row = btn.closest('.cart-item');
        const input = row.querySelector('.qty-input');
        let newQty = parseInt(input.value);

        if (action === 'inc') {
          newQty++;
        } else if (action === 'dec' && newQty > 1) {
          newQty--;
        }

        input.value = newQty;
        
        const priceText = row.querySelector('.item-price').textContent;
        const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
        const subtotal = price * newQty;
        row.querySelector('.item-total strong').textContent = subtotal + ' грн';
        
        updateTotalPrice();
        
        saveCartToLocalStorage();

        fetch('cart.php?qty=' + encodeURIComponent(key) + '&action=' + action, {
          method: 'GET',
          credentials: 'same-origin'
        })
        .catch(err => {
          console.error('Ошибка при синхронизации с сервером:', err);
        });
      }

      function removeItem(btn) {
        const key = btn.getAttribute('data-key');
        const row = btn.closest('.cart-item');
      
        row.remove();
        
        updateTotalPrice();
        
        saveCartToLocalStorage();
        
        const cartTable = document.querySelector('.cart-table tbody');
        if (cartTable.children.length === 0) {
          location.reload();
        }
        
        fetch('cart.php?remove=' + encodeURIComponent(key), {
          method: 'GET',
          credentials: 'same-origin'
        })
        .catch(err => {
          console.error('Ошибка при синхронизации с сервером:', err);
        });
      }

      function updateTotalPrice() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(row => {
          const totalText = row.querySelector('.item-total strong').textContent;
          const subtotal = parseFloat(totalText.replace(/[^0-9.]/g, ''));
          total += subtotal;
        });
        const totalElement = document.querySelector('.form-total strong');
        if (totalElement) {
          totalElement.textContent = total + ' грн';
        }
      }

      function saveCartToLocalStorage() {
        const cart = {};
        document.querySelectorAll('.cart-item').forEach(row => {
          const button = row.querySelector('.qty-btn');
          const key = button.getAttribute('data-key');
          const qty = parseInt(row.querySelector('.qty-input').value);
          const priceText = row.querySelector('.item-price').textContent;
          const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
          const name = row.querySelector('.item-name').textContent;
          
          cart[key] = { name, price, qty };
        });
        localStorage.setItem('cart', JSON.stringify(cart));
      }

      document.getElementById('orderForm')?.addEventListener('submit', function(e) {
        const name = document.querySelector('input[name="name"]').value.trim();
        const payment = document.querySelector('input[name="payment"]:checked');
        const deliveryTime = document.querySelector('input[name="delivery_time"]:checked');
        const deliveryMethod = document.querySelector('input[name="delivery_method"]:checked');
        
        if (!name) {
          e.preventDefault();
          alert('Будь ласка, введіть ваше ім\'я');
          return false;
        }
        
        if (!payment || !deliveryTime || !deliveryMethod) {
          e.preventDefault();
          alert('Будь ласка, виберіть всі обов\'язкові пункти');
          return false;
        }
      });
    </script>

    <?php require __DIR__ . '/footer.php'; ?>