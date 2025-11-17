<?php
session_start();
require __DIR__ . '/products.php';

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
      $_SESSION['cart'][$k] = ['type'=>'pizza','id'=>$id,'name'=>$p['name'],'price'=>$p['price'],'qty'=>0];
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
      $_SESSION['cart'][$k] = ['type'=>'drink','key'=>$d['key'],'name'=>$d['name'],'price'=>$d['price'],'qty'=>0];
    }
    $_SESSION['cart'][$k]['qty']++;
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

    <main class="container">
      <h1>Кошик</h1>

      <?php if (empty($_SESSION['cart'])): ?>
        <p>Кошик порожній. Поверніться до <a href="index.php">меню</a>, щоб додати товари.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Товар</th>
                <th>Тип</th>
                <th>Ціна</th>
                <th>Кількість</th>
                <th>Сума</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php $total = 0; foreach ($_SESSION['cart'] as $key => $item):
                $subtotal = $item['price'] * $item['qty'];
                $total += $subtotal;
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['name']); ?></td>
                  <td><?php echo htmlspecialchars($item['type']); ?></td>
                  <td><?php echo $item['price']; ?> грн</td>
                  <td><?php echo $item['qty']; ?></td>
                  <td><?php echo $subtotal; ?> грн</td>
                  <td><a class="btn btn-sm btn-outline-danger" href="cart.php?remove=<?php echo urlencode($key); ?>">Видалити</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4" class="text-end"><strong>Разом:</strong></td>
                <td><strong><?php echo $total; ?> грн</strong></td>
                <td><a class="btn btn-sm btn-outline-secondary" href="cart.php?clear=1">Очистити</a></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </main>

    <?php require __DIR__ . '/footer.php'; ?>
