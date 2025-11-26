<?php
require __DIR__ . '/db.php';

function get_all_products() {
    global $pdo;
    $sql = "SELECT id, name, price, isPizza FROM products ORDER BY id";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_product($id, $name, $price, $isPizza) {
    global $pdo;
    $sql = "UPDATE products SET name = :name, price = :price, isPizza = :ispizza WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':name' => $name,
        ':price' => $price,
        ':ispizza' => $isPizza,
        ':id' => $id
    ]);
}

function update_products_bulk(array $items) {
    global $pdo;

    $sql = "UPDATE products SET name = :name, price = :price, isPizza = :ispizza WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    try {
        $pdo->beginTransaction();
        foreach ($items as $it) {
            
            $id = isset($it['id']) ? intval($it['id']) : 0;
            $name = isset($it['name']) ? $it['name'] : '';
            $price = isset($it['price']) ? floatval($it['price']) : 0;
            $isPizza = !empty($it['isPizza']) ? 1 : 0;

            if ($id <= 0) continue;

            $ok = $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':ispizza' => $isPizza,
                ':id' => $id
            ]);
            if ($ok === false) {
                throw new Exception('Update failed for id: ' . $id);
            }
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function create_product($name, $price, $isPizza) {
    global $pdo;
    $sql = "INSERT INTO products (name, price, isPizza) VALUES (:name, :price, :ispizza) RETURNING id";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':name' => $name,
        ':price' => $price,
        ':ispizza' => $isPizza
    ]);

    if ($ok) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id'] ?? true;
    }

    return false;
}

function delete_product($id) {
    global $pdo;
    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([':id' => $id]);
}
