<?php

$url = "https://6920637531e684d7bfccd5fa.mockapi.io/api/pizzav1/Product";

$response = file_get_contents($url);

if ($response === false) {
    die("Помилка отримання даних з MockAPI");
}

$data = json_decode($response, true);

$products = [];
$drinks = [];

foreach ($data as $item) {
    if (isset($item["isPizza"]) && $item["isPizza"] === true) {
        $products[] = $item;
    } else {
        $drinks[] = $item;
    }
}
return null;