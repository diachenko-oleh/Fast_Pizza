<?php
session_start();
require __DIR__ . '/../Model/products.php';

$page_title = 'FAST PIZZA — Кошик';
require __DIR__ . '/header.php';

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

if (!empty($_GET['add'])) {
  $id = intval($_GET['add']);
  $p = find_product($products, $id);
  if ($p) {
    $k = 'pizza_' . $id;
    if (!isset($_SESSION['cart'][$k])) {
      $_SESSION['cart'][$k] = ['type'=>'pizza','id'=>$id,'name'=>$p['name'],'price'=>$p['price'],'img'=>$p['img'],'qty'=>0];
    }
    $_SESSION['cart'][$k]['qty']++;
  }
}

if (!empty($_GET['add_drink'])) {
  $dkey = $_GET['add_drink'];
  $d = find_by_key($drinks, $dkey);
  if ($d) {
    $k = 'drink_' . $d['key'];
    if (!isset($_SESSION['cart'][$k])) {
      $_SESSION['cart'][$k] = ['type'=>'drink','key'=>$d['key'],'name'=>$d['name'],'price'=>$d['price'],'img'=>'images/drink.jpg','qty'=>0];
    }
    $_SESSION['cart'][$k]['qty']++;
  }
}

if (!empty($_GET['qty'])) {
  $key = $_GET['qty'];
  $action = $_GET['action'] ?? '';
  if (isset($_SESSION['cart'][$key])) {
    if ($action === 'inc') {
      $_SESSION['cart'][$key]['qty']++;
    } elseif ($action === 'dec' && $_SESSION['cart'][$key]['qty'] > 1) {
      $_SESSION['cart'][$key]['qty']--;
    }
  }
}

if (!empty($_GET['remove'])) {
  $rem = $_GET['remove'];
  if (isset($_SESSION['cart'][$rem])) unset($_SESSION['cart'][$rem]);
}

if (!empty($_GET['clear'])) {
  $_SESSION['cart'] = [];
}
?>

    <main class="container cart-container">
      <h2 class="mb-4">Замовлення:</h2>

      <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
          <p>Кошик порожній. Поверніться до <a href="index.php">меню</a>, щоб додати товари.</p>
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
                      <div class="item-thumb">
                        <?php if (!empty($item['img'])): ?>
                          <img src="<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                        <?php else: ?>
                          <span style="color:#aaa;font-size:12px;">Зображення</span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="item-info">
                      <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                      <div class="item-price"><?php echo $item['price']; ?> грн</div>
                    </td>
                    <td class="item-controls">
                      <div class="qty-control">
                        <a href="cart.php?qty=<?php echo urlencode($key); ?>&action=dec" class="qty-btn minus">−</a>
                        <input type="text" class="qty-input" value="<?php echo $item['qty']; ?>" readonly>
                        <a href="cart.php?qty=<?php echo urlencode($key); ?>&action=inc" class="qty-btn plus">+</a>
                      </div>
                    </td>
                    <td class="item-total">
                      <strong><?php echo $subtotal; ?> грн</strong>
                    </td>
                    <td class="item-remove">
                      <a href="cart.php?remove=<?php echo urlencode($key); ?>" class="remove-btn" title="Видалити">✕</a>
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
                <input type="text" name="name" class="form-input" placeholder="Ім'я" required>
              </div>

              <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" class="form-input" placeholder="+380 __ ___ __ __" required>
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