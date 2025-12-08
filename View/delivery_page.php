<?php
$page_title = 'FAST PIZZA — Доставка';
require __DIR__ . '/header.php';
require_once __DIR__ . '/config.php';
?>

<style>
    /* Додаткові стилі для вирівнювання по макету */
    .layout-stack {
        display: flex;
        flex-direction: column;
        gap: 15px;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .info-box {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e0e0e0;
    }

    .map-labels {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .cost-footer {
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        text-align: left;
        font-size: 1.2rem;
        border: 1px solid #ddd;
    }

    .cost-value {
        font-weight: 700;
        color: #333;
        font-size: 1.4rem;
    }
    
    /* Адаптація існуючих класів під нову структуру */
    .delivery-title { margin-bottom: 0; }
    .tariff-text { font-size: 1.1rem; color: #555; }
</style>

<main class="delivery-container">
    <div class="layout-stack">
        
        <div class="info-box">
            <h1 class="delivery-title">Самовивіз - безкоштовно</h1>
        </div>

        <div class="info-box">
            <div class="tariff-text">
                Доставка — <strong>5 грн/км</strong>
            </div>
        </div>

        <div>
            <div class="map-labels">
                <span>Оберіть адресу:</span>
                <span class="info-badge" style="margin:0;">в межах м. Черкаси</span>
            </div>
            
            <div class="map-container">
                <div id="deliveryMap" style="height: 400px; width: 100%; border-radius: 8px;"></div>
            </div>
        </div>

        <div class="delivery-controls">
            <div class="control-group">
                <label class="control-label">Оберіть заклад для самовивозу:</label>
                <select class="custom-select" id="restaurantSelect" onchange="selectRestaurant()">
                    <option value="">-- Оберіть адресу --</option>
                    <option value="49.4383,32.0594">бульвар Шевченка, 60, Черкаси</option>
                    <option value="49.4450,32.0606">бульвар Шевченка, 150, Черкаси</option>
                    <option value="49.4520,32.0618">бульвар Шевченка, 210, Черкаси</option>
                </select>
            </div>

            <div class="control-group">
                <label class="control-label">Або введіть адресу доставки:</label>
                <input type="text" id="addressInput" class="address-input" placeholder="Введіть адресу українською">
                <div class="map-info" id="mapInfo" style="margin-top: 5px; font-size: 0.9em; color: #666;">
                    Клікніть на карту або перетягніть маркер для вибору адреси
                </div>
            </div>
        </div>

        <div class="cost-footer">
            Вартість доставки: <span id="deliveryCost" class="cost-value">0 грн</span>
        </div>

    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places&language=uk&region=UA&callback=initDeliveryMap" async defer></script>

<script>
let map, marker, selectedAddress = null, autocomplete = null;
let restaurantLocation = null;
let deliveryLocation = null;
let directionsService = null;
let directionsRenderer = null;

// Ініціалізація карти
function initDeliveryMap() {
    map = new google.maps.Map(document.getElementById('deliveryMap'), {
        center: { lat: 49.44499, lng: 32.06057 },
        zoom: 14,
        disableDefaultUI: true,
        mapTypeControl: false
    });

    marker = new google.maps.Marker({
        map: map,
        draggable: true,
        title: "Перетягніть для вибору адреси"
    });

    // Ініціалізація сервісів маршрутизації
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: map,
        suppressMarkers: false, 
        polylineOptions: {
            strokeColor: '#FF6B35',
            strokeWeight: 5,
            strokeOpacity: 0.8
        }
    });

    // Клік на карту
    map.addListener("click", (e) => {
        setDeliveryPosition(e.latLng);
    });

    // Перетягування маркера
    marker.addListener("dragend", () => {
        setDeliveryPosition(marker.getPosition());
    });

    // Автокомпліт для адреси
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
        setDeliveryPosition(place.geometry.location);
    });
}

// Встановлення позиції доставки
function setDeliveryPosition(latlng) {
    marker.setPosition(latlng);
    deliveryLocation = latlng;

    const geocoder = new google.maps.Geocoder();
    geocoder.geocode(
        { location: latlng, region: "UA", language: "uk" },
        (results, status) => {
            if (status === "OK" && results[0]) {
                const parsed = extractAddress(results[0]);
                
                const cityNormalized = parsed.city.toLowerCase().trim();
                if (cityNormalized !== "черкаси" && cityNormalized !== "м. черкаси") {
                    document.getElementById("mapInfo").textContent = "Доставка доступна тільки в межах м. Черкаси";
                    deliveryLocation = null;
                    return;
                }

                selectedAddress = parsed;
                document.getElementById("mapInfo").textContent = "Вибрано: " + parsed.fullAddress;
                
                // Автоматична побудова маршруту при виборі точки
                if (restaurantLocation) {
                    calculateRoute();
                }
            } else {
                document.getElementById("mapInfo").textContent = "Не вдалося визначити адресу";
                deliveryLocation = null;
            }
        }
    );
}

// Парсинг адреси
function extractAddress(place) {
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

    const full = (street || "") + (number ? ", " + number : "") + (city ? ", " + city : "");

    return {
        valid: street !== "" && city !== "",
        street,
        number,
        city,
        fullAddress: full.trim()
    };
}

// Вибір ресторану
function selectRestaurant() {
    const select = document.getElementById("restaurantSelect");
    const value = select.value;
    
    if (!value) {
        restaurantLocation = null;
        if (directionsRenderer) {
            directionsRenderer.setDirections({routes: []});
        }
        return;
    }

    const [lat, lng] = value.split(',').map(parseFloat);
    restaurantLocation = { lat, lng };

    map.setCenter(restaurantLocation);
    map.setZoom(15);

    document.getElementById("mapInfo").textContent = "Обрано заклад: " + select.options[select.selectedIndex].text;
    
    // Автоматична побудова маршруту при виборі ресторану
    if (deliveryLocation) {
        calculateRoute();
    }
}

// Обрахунок і відображення маршруту
function calculateRoute() {
    if (!restaurantLocation) {
        alert("Будь ласка, оберіть заклад для самовивозу");
        return;
    }

    if (!deliveryLocation) {
        alert("Будь ласка, оберіть адресу доставки на карті");
        return;
    }

    const request = {
        origin: restaurantLocation,
        destination: deliveryLocation,
        travelMode: google.maps.TravelMode.DRIVING,
        region: 'UA'
    };

    directionsService.route(request, (result, status) => {
        if (status === google.maps.DirectionsStatus.OK) {
            directionsRenderer.setDirections(result);
            marker.setVisible(false);
            
            const route = result.routes[0];
            const leg = route.legs[0];
            const distance = leg.distance.value / 1000; 
            const duration = leg.duration.text;
            const cost = Math.ceil(distance * 5); 
            
            // Оновлюємо вартість в нижньому блоці
            document.getElementById("deliveryCost").textContent = cost + " грн";
            
            document.getElementById("mapInfo").innerHTML = 
                `<strong>Маршрут побудовано</strong><br>` +
                `Відстань: ${distance.toFixed(2)} км<br>` +
                `Час в дорозі: ${duration}`;
            
        } else {
            alert("Не вдалося побудувати маршрут: " + status);
        }
    });
}
</script>

<?php require __DIR__ . '/footer.php'; ?>