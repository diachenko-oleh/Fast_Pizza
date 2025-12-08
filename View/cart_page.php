<?php

session_start();
require_once __DIR__ . '/../Model/products.php';
require_once __DIR__ . '/../Model/auth.php';
require_once __DIR__ . '/config.php';

// Якщо користувач авторизований, отримаємо його дані для попереднього заповнення форми
$client = get_current_user_client();

$page_title = 'FAST PIZZA — Кошик';
require_once __DIR__ . '/header.php';

require_once __DIR__ . '/../Presenter/cart_actions.php';
?>

<style>
.payment-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.payment-modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 40px;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideDown 0.3s;
}

@keyframes slideDown {
    from { 
        transform: translateY(-50px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

.payment-close {
    color: #aaa;
    float: right;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}

.payment-close:hover {
    color: #000;
}

.payment-header {
    text-align: center;
    margin-bottom: 30px;
}

.payment-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 10px;
}

.payment-amount {
    font-size: 36px;
    font-weight: 700;
    color: #27ae60;
    margin: 20px 0;
}

.card-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
}

.card-form-group {
    margin-bottom: 20px;
}

.card-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.card-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 2px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s;
    font-family: 'Courier New', monospace;
}

.card-input:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.card-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 15px;
}

.payment-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.payment-btn {
    flex: 1;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-btn-confirm {
    background: #27ae60;
    color: white;
}

.payment-btn-confirm:hover {
    background: #229954;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.payment-btn-cancel {
    background: #e74c3c;
    color: white;
}

.payment-btn-cancel:hover {
    background: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.payment-instruction {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
    margin: 20px 0;
}

.payment-instruction p {
    margin: 5px 0;
    color: #856404;
}
</style>

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

              <div class="form-group" id="deliverySection" style="display: none;">
                <label class="form-label">Виберіть адресу доставки:</label>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapModal">
                  Відкрити карту
                </button>
                <input type="hidden" id="deliveryAddress" name="address" required>
                <div id="selectedAddressDisplay" style="margin-top: 10px; font-weight: bold; color: #666;"></div>
              </div>

              <div class="modal fade" id="mapModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Виберіть місце на карті</h5>
                      <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input id="addressInput" type="text" class="form-control mb-3" placeholder="Введіть адресу українською">
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
                <strong id="totalAmount"><?php echo $total; ?> грн</strong>
              </div>

              <button type="button" class="submit-btn" onclick="handleOrder()">Замовити</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </main>

<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?> &libraries=places&language=uk&region=UA&callback=initMap" async defer></script>

<script>
let map, marker, selectedAddress = null;
const clientBillingId = '<?php echo addslashes($client['billing_id'] ?? ''); ?>';
let autocomplete = null;
let deliveryCost = 0;
const baseTotal = <?php echo $total; ?>; // Базова сума замовлення

// Координати пунктів видачі
const pickupPoints = [
  { address: "бульвар Шевченка, 60, Черкаси", lat: 49.4445, lng: 32.0606 },
  { address: "бульвар Шевченка, 150, Черкаси", lat: 49.4425, lng: 32.0580 },
  { address: "бульвар Шевченка, 210, Черкаси", lat: 49.4405, lng: 32.0555 }
];

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

document.getElementById("mapModal")?.addEventListener("shown.bs.modal", () => {
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
        document.getElementById("mapInfo").textContent = "Оберіть конкретний будинок";
        selectedAddress = null;
        document.getElementById("confirmAddressBtn").disabled = true;
        return;
      }

      selectedAddress = parsed;
      
      // Перевірка міста
      const cityNormalized = parsed.city.toLowerCase().trim();
      if (cityNormalized !== "черкаси" && cityNormalized !== "м. черкаси") {
        selectedAddress = null;
        document.getElementById("mapInfo").textContent = "Доставка доступна тільки в межах м. Черкаси.";
        document.getElementById("confirmAddressBtn").disabled = true;
        return;
      }
      
      // Розрахунок вартості доставки
      calculateDeliveryCost(place.geometry.location);
    });
  }
});

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
        // Розрахунок вартості доставки
        calculateDeliveryCost(latlng);
      } else {
        selectedAddress = null;
        info.textContent = "Не вдалося визначити адресу";
        confirmBtn.disabled = true;
      }
    }
  );
}

// Розрахунок вартості доставки
function calculateDeliveryCost(destinationLatLng) {
  const service = new google.maps.DistanceMatrixService();
  const origins = pickupPoints.map(point => new google.maps.LatLng(point.lat, point.lng));
  
  service.getDistanceMatrix(
    {
      origins: origins,
      destinations: [destinationLatLng],
      travelMode: 'DRIVING',
      unitSystem: google.maps.UnitSystem.METRIC,
    },
    (response, status) => {
      if (status === 'OK') {
        let shortestDistance = Infinity;
        let closestPoint = null;

        response.rows.forEach((row, index) => {
          const element = row.elements[0];
          if (element.status === 'OK') {
            const distanceInMeters = element.distance.value;
            if (distanceInMeters < shortestDistance) {
              shortestDistance = distanceInMeters;
              closestPoint = pickupPoints[index];
            }
          }
        });

        if (closestPoint) {
          const distanceInKm = (shortestDistance / 1000).toFixed(2);
          deliveryCost = Math.round(distanceInKm * 10); // 10 грн/км
          
          const info = document.getElementById("mapInfo");
          info.innerHTML = `
            <strong>Вибране місце:</strong> ${selectedAddress.fullAddress}<br>
            <strong>Найближчий пункт:</strong> ${closestPoint.address}<br>
            <strong>Відстань:</strong> ${distanceInKm} км<br>
            <strong>Вартість доставки:</strong> ${deliveryCost} грн
          `;
          
          document.getElementById("confirmAddressBtn").disabled = false;
        }
      } else {
        console.error('Distance Matrix request failed:', status);
        document.getElementById("mapInfo").textContent = "Не вдалося розрахувати відстань";
        document.getElementById("confirmAddressBtn").disabled = true;
      }
    }
  );
}

// Оновлення загальної суми з доставкою
function updateTotalWithDelivery() {
  const totalAmount = baseTotal + deliveryCost;
  const totalElement = document.getElementById("totalAmount");
  if (totalElement) {
    totalElement.textContent = totalAmount + " грн";
  }
  
  // Додаємо приховане поле для передачі вартості доставки
  let deliveryCostInput = document.getElementById("deliveryCostInput");
  if (!deliveryCostInput) {
    deliveryCostInput = document.createElement("input");
    deliveryCostInput.type = "hidden";
    deliveryCostInput.id = "deliveryCostInput";
    deliveryCostInput.name = "delivery_cost";
    document.getElementById("orderForm").appendChild(deliveryCostInput);
  }
  deliveryCostInput.value = deliveryCost;
}

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

document.getElementById("confirmAddressBtn")?.addEventListener("click", () => {
  if (!selectedAddress || !selectedAddress.valid) return;

  document.getElementById("deliveryAddress").value = JSON.stringify({
    street: selectedAddress.street,
    house_number: selectedAddress.number,
    city: selectedAddress.city,
    delivery_cost: deliveryCost
  });

  document.getElementById("selectedAddressDisplay").innerHTML = `
    ${selectedAddress.fullAddress}<br>
    <span style="color: #28a745;">Вартість доставки: ${deliveryCost} грн</span>
  `;

  // Оновлюємо загальну суму після підтвердження адреси
  updateTotalWithDelivery();

  bootstrap.Modal.getInstance(document.getElementById("mapModal")).hide();
});

document.getElementById("mapModal")?.addEventListener("shown.bs.modal", () => {
  setTimeout(() => {
    google.maps.event.trigger(map, "resize");
    map.setCenter({ lat: 49.44499, lng: 32.06057 });
  }, 200);
});

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
    console.error('Помилка синхронізації:', err);
  });
}

function removeItem(btn) {
  const key = btn.getAttribute('data-key');
  const row = btn.closest('.cart-item');

  row.remove();
  updateTotalPrice();
  saveCartToLocalStorage();

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
    if (!data.success) {
      console.error('Server error (remove):', data);
      if (row && !document.querySelector('.cart-table tbody').contains(row)) {
        document.querySelector('.cart-table tbody').appendChild(row);
        updateTotalPrice();
        saveCartToLocalStorage();
      }
    }
  })
  .catch(err => {
    console.error('Помилка синхронізації:', err);
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
    totalElement.textContent = (total + deliveryCost) + ' грн';
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

function toggleDeliveryUI() {
  const method = document.querySelector('input[name="delivery_method"]:checked')?.value;
  const selfPickupSection = document.getElementById('selfPickupSection');
  const deliverySection = document.getElementById('deliverySection');
  const addressSelect = document.getElementById('addressSelect');
  const deliveryAddress = document.getElementById('deliveryAddress');

  if (method === 'self') {
    selfPickupSection.style.display = 'block';
    deliverySection.style.display = 'none';
    addressSelect.required = true;
    addressSelect.disabled = false;
    deliveryAddress.required = false;
    deliveryAddress.disabled = true;
    deliveryAddress.removeAttribute('required');
    
    // Скидаємо вартість доставки при самовивозі
    deliveryCost = 0;
    updateTotalPrice();
  } else if (method === 'delivery') {
    selfPickupSection.style.display = 'none';
    deliverySection.style.display = 'block';
    addressSelect.required = false;
    addressSelect.disabled = true;
    addressSelect.removeAttribute('required');
    deliveryAddress.required = true;
    deliveryAddress.disabled = false;
  }
}

// Обробка замовлення
function handleOrder() {
  const form = document.getElementById('orderForm');
  
  // Перевірка валідації форми
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  
  const payment = document.querySelector('input[name="payment"]:checked')?.value;
  
  if (!payment) {
    alert('Будь ласка, оберіть спосіб оплати');
    return;
  }
  
  if (payment === 'cash') {
    // Готівка - відразу відправляємо форму
    form.submit();
  } else if (payment === 'card') {
    // If the logged-in client has a stored Stripe customer id (billing_id),
    // open the existing-customer payment flow in a popup by POSTing to
    // `create_payment_existing.php` with `customer_id` and `amount`.
    if (clientBillingId && clientBillingId.length > 0) {
      // Ensure payment radio is set to card so server knows to attempt card flow
      var paymentRadio = document.querySelector('input[name="payment"][value="card"]');
      if (paymentRadio) paymentRadio.checked = true;

      // Read numeric total (remove currency text)
      const totalText = document.getElementById('totalAmount').textContent || '0';
      const totalValue = parseFloat(totalText.replace(/[^0-9\.]/g, '')) || 0;
      const amount = Math.round(totalValue);

      // Open popup first to avoid popup blockers
      const wnd = window.open('', 'stripe_existing_payment', 'width=600,height=600');

      // Create a form to POST to create_payment_existing.php
      const payForm = document.createElement('form');
      payForm.method = 'POST';
      payForm.action = '../create_payment_existing.php';
      payForm.target = 'stripe_existing_payment';

      const inputCustomer = document.createElement('input');
      inputCustomer.type = 'hidden';
      inputCustomer.name = 'customer_id';
      inputCustomer.value = clientBillingId;
      payForm.appendChild(inputCustomer);

      const inputAmount = document.createElement('input');
      inputAmount.type = 'hidden';
      inputAmount.name = 'amount';
      inputAmount.value = amount;
      payForm.appendChild(inputAmount);

      document.body.appendChild(payForm);
      payForm.submit();
      setTimeout(() => { document.body.removeChild(payForm); }, 1500);
      return;
    }

    // Otherwise show the card modal as a fallback (user will enter card details)
    const totalText = document.getElementById('totalAmount').textContent;
    document.getElementById('paymentAmount').textContent = totalText;
    document.getElementById('paymentModal').style.display = 'block';
  }
}

// Закриття модального вікна оплати
function closePaymentModal() {
  document.getElementById('paymentModal').style.display = 'none';
}

// Форматування номера картки
function formatCardNumber(input) {
  let value = input.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
  let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
  input.value = formattedValue;
}

// Форматування місяць/рік
function formatExpiry(input) {
  let value = input.value.replace(/\D/g, '');
  if (value.length >= 2) {
    value = value.slice(0, 2) + '/' + value.slice(2, 4);
  }
  input.value = value;
}

// Обробка оплати карткою
function handleCardPayment(event) {
  event.preventDefault();
  
  const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
  const expiry = document.getElementById('cardExpiry').value;
  const cvv = document.getElementById('cardCVV').value;
  const zip = document.getElementById('cardZip').value;
  
  // Валідація номера картки (16 цифр)
  if (cardNumber.length !== 16) {
    alert('Введіть коректний номер картки (16 цифр)');
    return false;
  }
  
  // Валідація expiry (MM/YY)
  if (!/^\d{2}\/\d{2}$/.test(expiry)) {
    alert('Введіть коректну дату у форматі MM/YY');
    return false;
  }
  
  // Валідація CVV (3 цифри)
  if (cvv.length !== 3) {
    alert('Введіть коректний CVV код (3 цифри)');
    return false;
  }
  
  // Валідація індексу (5 цифр)
  if (zip.length !== 5) {
    alert('Введіть коректний поштовий індекс (5 цифр)');
    return false;
  }
  
  // Якщо все ок - закриваємо модальне вікно і відправляємо форму
  closePaymentModal();
  document.getElementById('orderForm').submit();
  
  return false;
}

// Закриття модального вікна при кліку поза ним
window.onclick = function(event) {
  const modal = document.getElementById('paymentModal');
  if (event.target == modal) {
    closePaymentModal();
  }
}
</script>

<?php require __DIR__ . '/footer.php'; ?>