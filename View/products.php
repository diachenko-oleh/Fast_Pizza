<?php

$url = "https://6920637531e684d7bfccd5fa.mockapi.io/api/pizzav1/Product";

$context = stream_context_create(['http' => ['timeout' => 5]]);
$response = @file_get_contents($url, false, $context);

$products = [];
$drinks = [];

if ($response !== false) {
    $data = json_decode($response, true);
    if (is_array($data)) {
        foreach ($data as $item) {
            $isPizza = false;
            if (isset($item['isPizza'])) {
                $isPizza = filter_var($item['isPizza'], FILTER_VALIDATE_BOOLEAN);
            }

            if ($isPizza) {
                $p = [];
                $p['id'] = isset($item['id']) ? (int)$item['id'] : null;
                $p['name'] = $item['name'] ?? $item['title'] ?? 'Піца';
                if (isset($item['price'])) $p['price'] = $item['price'];
                elseif (isset($item['cost'])) $p['price'] = $item['cost'];
                else $p['price'] = 0;
                if (!empty($item['img'])) $p['img'] = $item['img'];
                elseif (!empty($item['image'])) $p['img'] = $item['image'];
                else $p['img'] = 'https://via.placeholder.com/300x200?text=Pizza';
                $products[] = $p;
            } else {
                $d = [];
                $d['key'] = $item['key'] ?? (isset($item['id']) ? 'drink_' . $item['id'] : uniqid('drink_'));
                $d['name'] = $item['name'] ?? $item['title'] ?? 'Напій';
                if (isset($item['price'])) $d['price'] = $item['price'];
                elseif (isset($item['cost'])) $d['price'] = $item['cost'];
                else $d['price'] = 0;
                $drinks[] = $d;
            }
        }
    }
}
?>