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
                        <button type="button" class="qty-btn minus" data-key="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>" onclick="changeQty(this, 'dec')">−</button>
                        <input type="text" class="qty-input" value="<?php echo $item['qty']; ?>" readonly>
                        <button type="button" class="qty-btn plus" data-key="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>" onclick="changeQty(this, 'inc')">+</button>
                      </div>
                    </td>
                    <td class="item-total">
                      <strong><?php echo $subtotal; ?> грн</strong>
                    </td>
                    <td class="item-remove">
                      <button type="button" class="remove-btn" data-key="<?php echo htmlspecialchars($key, ENT_QUOTES); ?>" onclick="removeItem(this)" title="Видалити">✕</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="order-form">
            <form method="POST" action="" id="orderForm">
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
                    <input type="radio" name="delivery_method" value="self" required onchange="toggleDeliveryUI()">
                    <span>самовивіз</span>
                  </label>
                  <label class="radio-label">
                    <input type="radio" name="delivery_method" value="delivery" required onchange="toggleDeliveryUI()">
                    <span>доставка</span>
                  </label>
                </div>
              </div>

              <div class="form-group" id="selfPickupSection" style="display: none;">
                <label for="addressSelect" class="form-label">Оберіть адресу закладу:</label>
                <select id="addressSelect" name="address" class="form-control" required>
                  <option value="бульвар Шевченка, 60, Черкаси">бульвар Шевченка, 60, Черкаси</option>
                  <option value="бульвар Шевченка, 150, Черкаси">бульвар Шевченка, 150, Черкаси</option>
                  <option value="бульвар Шевченка, 210, Черкаси">бульвар Шевченка, 210, Черкаси</option>
                </select>
              </div>

              <!-- Доставка: кнопка для відкриття модального вікна з картою -->
              <div class="form-group" id="deliverySection" style="display: none;">
                <label class="form-label">Виберіть адресу доставки:</label>

                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapModal">
                  Відкрити карту
                </button>

                <!-- тут буде збережено адресу -->
                <input type="hidden" id="deliveryAddress" name="address" required>

                <!-- показ обраної адреси -->
                <div id="selectedAddressDisplay" style="margin-top: 10px; font-weight: bold; color: #666;"></div>
              </div>

              <!-- Модальне вікно з картою -->
              <div class="modal fade" id="mapModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">

                    <div class="modal-header">
                      <h5 class="modal-title">Виберіть місце на карті</h5>
                      <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                      <input id="addressInput" 
                             type="text" 
                             class="form-control mb-3" 
                             placeholder="Введіть адресу українською">

                      <div id="map" style="width: 100%; height: 400px;"></div>

                      <div id="mapInfo" class="mt-2" style="font-size: 16px;">
                        Вибране місце: ще не вибрано
                      </div>
                    </div>

                    <div class="modal-footer">
                      <button id="confirmAddressBtn" type="button" class="btn btn-primary" disabled>
                        Підтвердити адресу
                      </button>
                    </div>

                  </div>
                </div>
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

<style>
.pac-container {
  z-index: 9999 !important;
}
</style>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDDe3iJJ1yjlG_VbcjmNpy32wDH6rMteJ0&libraries=places&language=uk&region=UA&callback=initMap" async defer></script>

<script>
let map, marker, selectedAddress = null;
let autocomplete = null;

// ---------------------------
// Ініціалізація карти
// ---------------------------
function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: 49.44499, lng: 32.06057 },
    zoom: 15,
    disableDefaultUI: true
  });

  marker = new google.maps.Marker({
    map,
    draggable: true
  });

  map.addListener("click", (e) => setPositionFromCoords(e.latLng));
  marker.addListener("dragend", () => setPositionFromCoords(marker.getPosition()));
}

// ---------------------------
// Ініціалізація автокомпліта
// ---------------------------
document.getElementById("mapModal").addEventListener("shown.bs.modal", () => {
  setTimeout(() => google.maps.event.trigger(map, "resize"), 100);

  if (!autocomplete) {
    autocomplete = new google.maps.places.Autocomplete(
      document.getElementById("addressInput"),
      {
        componentRestrictions: { country: "ua" },
        fields: ["formatted_address", "geometry", "address_components"]
      }
    );

    autocomplete.addListener("place_changed", () => {
      const place = autocomplete.getPlace();
      if (!place.geometry) return;

      map.setCenter(place.geometry.location);
      map.setZoom(16);
      marker.setPosition(place.geometry.location);

      const parsed = extractUkrainianAddress(place);

      if (!parsed.valid) {
        document.getElementById("mapInfo").textContent = "❌ Оберіть конкретний будинок";
        selectedAddress = null;
        document.getElementById("confirmAddressBtn").disabled = true;
        return;
      }

      selectedAddress = parsed;
      document.getElementById("mapInfo").textContent = "Вибране місце: " + parsed.fullAddress;
      document.getElementById("confirmAddressBtn").disabled = false;
    });
  }
});

// ---------------------------
// Отримання адреси по координатам
// ---------------------------
function setPositionFromCoords(latlng) {
  marker.setPosition(latlng);

  const geocoder = new google.maps.Geocoder();
  geocoder.geocode(
    { location: latlng, region: "UA", language: "uk" },
    (results, status) => {
      const info = document.getElementById("mapInfo");
      const confirmBtn = document.getElementById("confirmAddressBtn");

      if (status === "OK" && results[0]) {
        const parsed = extractUkrainianAddress(results[0]);

        if (!parsed.valid) {
          info.textContent = "Оберіть конкретний будинок, а не область";
          selectedAddress = null;
          confirmBtn.disabled = true;
          return;
        }
        
        const cityNormalized = parsed.city.toLowerCase().trim();
        if (cityNormalized !== "черкаси" && cityNormalized !== "м. черкаси") {
          selectedAddress = null;
          info.textContent = "Доставка доступна тільки в межах м. Черкаси.";
          confirmBtn.disabled = true;
          return;
        }

        selectedAddress = parsed;
        info.textContent = "Вибране місце: " + parsed.fullAddress;
        confirmBtn.disabled = false;
      } else {
        selectedAddress = null;
        info.textContent = "Не вдалося визначити адресу";
        confirmBtn.disabled = true;
      }
    }
  );
}

// ---------------------------
// Парсер української адреси + ВАЛІДАЦІЯ
// ---------------------------
function extractUkrainianAddress(place) {
  if (!place.address_components)
    return { valid: false, fullAddress: place.formatted_address };

  let street = "";
  let number = "";
  let city = "";

  for (const comp of place.address_components) {
    if (comp.types.includes("route")) street = comp.long_name;
    if (comp.types.includes("street_number")) number = comp.long_name;
    if (comp.types.includes("locality")) city = comp.long_name;
  }

  const full =
    (street || "") +
    (number ? ", " + number : "") +
    (city ? ", " + city : "");

  const isValid = street !== "" && number !== "" && city !== "";

  return {
    valid: isValid,
    street,
    number,
    city,
    fullAddress: full.trim()
  };
}

// ---------------------------
// Підтвердження адреси
// ---------------------------
document.getElementById("confirmAddressBtn").addEventListener("click", () => {
  if (!selectedAddress || !selectedAddress.valid) return;

  document.getElementById("deliveryAddress").value = JSON.stringify({
    street: selectedAddress.street,
    house_number: selectedAddress.number,
    city: selectedAddress.city
  });

  document.getElementById("selectedAddressDisplay").textContent =
    selectedAddress.fullAddress;

  bootstrap.Modal.getInstance(document.getElementById("mapModal")).hide();
});

// ---------------------------
// Фікс ресайзу карти
// ---------------------------
document.getElementById("mapModal").addEventListener("shown.bs.modal", () => {
  setTimeout(() => {
    google.maps.event.trigger(map, "resize");
    map.setCenter({ lat: 49.44499, lng: 32.06057 });
  }, 200);
});

// ЗМІНА КІЛЬКОСТІ ТОВАРУ В КОШИКУ
function changeQty(btn, action) {
  const key = btn.getAttribute('data-key');
  const row = btn.closest('.cart-item');
  const input = row.querySelector('.qty-input');
  let newQty = parseInt(input.value);

  // Змінюємо кількість
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

  fetch('../Presenter/cart_actions.php?qty=' + encodeURIComponent(key) + '&action=' + action, {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
  .then(r => r.json())
  .then(data => {
    console.log('qty response', data);
    if (!data.success) console.error('Server error (qty update):', data);
  })
  .catch(err => {
    console.error('Ошибка при синхронизации с сервером:', err);
  });
}

// ВИДАЛЕННЯ ТОВАРУ З КОШИКА
function removeItem(btn) {
  const key = btn.getAttribute('data-key');
  const row = btn.closest('.cart-item');

  row.remove();

  updateTotalPrice();
  saveCartToLocalStorage();

  // Синхронізація з сервером
  fetch('../Presenter/cart_actions.php?remove=' + encodeURIComponent(key), {
    method: 'GET',
    credentials: 'same-origin',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
  .then(r => r.json())
  .then(data => {
    console.log('remove response', data);
    if (!data.success) {
      console.error('Server error (remove):', data);
      // Відновлюємо товар у разі помилки
      if (row && !document.querySelector('.cart-table tbody').contains(row)) {
        document.querySelector('.cart-table tbody').appendChild(row);
        updateTotalPrice();
        saveCartToLocalStorage();
      }
    }
  })
  .catch(err => {
    console.error('Ошибка при синхронизации с сервером:', err);
  });
}

// ОНОВЛЕННЯ ЗАГАЛЬНОЇ СУМИ ЗАМОВЛЕННЯ
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

// ЗБЕРЕЖЕННЯ КОШИКА В LOCALSTORAGE
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
<?php
require __DIR__ . '/../Model/db.php'; // подключение PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['cart'])) {
        echo "<script>alert('Кошик порожній!');</script>";
        exit;
    }

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $payment = $_POST['payment'];
    $delivery_method = $_POST['delivery_method'];
    $delivery_time = $_POST['delivery_time'];
    $comments = $_POST['comments'] ?? '';

    // ---------------------------------------
    // 1. Если клиент авторизован — берем ID
    // ---------------------------------------
    $client = get_current_user_client();
    if (!$client) {
        echo "<script>alert('Авторизуйтесь перед замовленням');</script>";
        exit;
    }
    $client_id = $client['id'];

    // ---------------------------------------
    // 2. Адреса (самовывоз / доставка)
    // ---------------------------------------
    $address_id = null;

    if ($delivery_method === "self") {
        // адреса самовывоза — обычная строка
        $addr = explode(",", $_POST['address']);
        $street = trim($addr[0]);
        $house = trim($addr[1] ?? '');
        $city = "Черкаси";

        $q = $pdo->prepare("INSERT INTO addresses(street, house_number, city) VALUES (?,?,?) RETURNING id");
        $q->execute([$street, $house, $city]);
        $address_id = $q->fetchColumn();

    } else {
        // доставка — JSON
        $json = json_decode($_POST['address'], true);

        $q = $pdo->prepare("INSERT INTO addresses(street, house_number, city) VALUES (?,?,?) RETURNING id");
        $q->execute([$json['street'], $json['house_number'], $json['city']]);
        $address_id = $q->fetchColumn();
    }

    // ---------------------------------------
    // 3. Создаем новый чек (receipt)
    // ---------------------------------------
    // курьера пока ставим 1
    $courier_id = 1;

    $q = $pdo->prepare("
        INSERT INTO receipt (client_id, address_id, date_time, courier_id)
        VALUES (?, ?, NOW(), ?)
        RETURNING id
    ");
    $q->execute([$client_id, $address_id, $courier_id]);
    $receipt_id = $q->fetchColumn();

    // ---------------------------------------
    // 4. Создаем записи orders
    // ---------------------------------------
    foreach ($_SESSION['cart'] as $item) {
        $product_id = $item['id'];  // ОБЯЗАТЕЛЬНО ДОБАВЬ id продукта в корзину!
        $qty = (int)$item['qty'];

        $q = $pdo->prepare("
            INSERT INTO orders (receipt_id, product_id, quantity)
            VALUES (?, ?, ?)
        ");
        $q->execute([$receipt_id, $product_id, $qty]);
    }

    // ---------------------------------------
    // 5. Очищаем корзину
    // ---------------------------------------
    $_SESSION['cart'] = [];

    echo "<script>alert('Замовлення успішно оформлено!'); window.location='menu_page.php';</script>";
}
?>

function toggleDeliveryUI() {
  const method = document.querySelector('input[name="delivery_method"]:checked')?.value;
  const selfPickupSection = document.getElementById('selfPickupSection');
  const deliverySection = document.getElementById('deliverySection');
  const addressSelect = document.getElementById('addressSelect');
  const deliveryAddress = document.getElementById('deliveryAddress');

  if (method === 'self') {
    // Показуємо вибір адреси закладу
    selfPickupSection.style.display = 'block';
    deliverySection.style.display = 'none';
    addressSelect.required = true;
    deliveryAddress.required = false;
  } else if (method === 'delivery') {
    // Показуємо вибір адреси доставки на карті
    selfPickupSection.style.display = 'none';
    deliverySection.style.display = 'block';
    addressSelect.required = false;
    deliveryAddress.required = true;
  }
}

// ВСТАНОВЛЕННЯ АДРЕСИ ДОСТАВКИ
function setDeliveryAddress(address) {
  document.getElementById('deliveryAddress').value = address;
  document.getElementById('selectedAddressDisplay').textContent = 'Адреса: ' + address;
  document.getElementById('modalAddressDisplay').textContent = 'Вибрана адреса: ' + address;
}

//Перевіряє заповнення всіх обов'язкових полів форми замовлення
document.getElementById('orderForm')?.addEventListener('submit', function(e) {
  const name = document.querySelector('input[name="name"]').value.trim();
  const payment = document.querySelector('input[name="payment"]:checked');
  const deliveryTime = document.querySelector('input[name="delivery_time"]:checked');
  const deliveryMethod = document.querySelector('input[name="delivery_method"]:checked');
  const method = deliveryMethod?.value;
  
  // Перевірка імені
  if (!name) {
    e.preventDefault();
    alert('Будь ласка, введіть ваше ім\'я');
    return false;
  }
  
  // Перевірка вибору всіх радіо-кнопок
  if (!payment || !deliveryTime || !deliveryMethod) {
    e.preventDefault();
    alert('Будь ласка, виберіть всі обов\'язкові пункти');
    return false;
  }

  // Перевірка адреси залежно від способу отримання
  if (method === 'self') {
    const addressSelect = document.getElementById('addressSelect');
    if (!addressSelect.value) {
      e.preventDefault();
      alert('Будь ласка, оберіть адресу закладу');
      return false;
    }
  } else if (method === 'delivery') {
    const deliveryAddress = document.getElementById('deliveryAddress');
    if (!deliveryAddress.value) {
      e.preventDefault();
      alert('Будь ласка, оберіть адресу доставки на карті');
      return false;
    }
  }
});
</script>

<?php require __DIR__ . '/footer.php'; ?>