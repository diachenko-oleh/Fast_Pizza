<?php
require_once __DIR__ . '/db.php';

/**
 * Отримати всі продукти
 */
function get_all_products() {
    global $pdo;
    $sql = "SELECT id, name, price, isPizza FROM products ORDER BY id";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Оновити один продукт
 */
function update_product($id, $name, $price, $isPizza) {
    global $pdo;
    
    $sql = "UPDATE products 
            SET name = :name, price = :price, isPizza = :ispizza 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute([
        ':name' => trim($name),
        ':price' => floatval($price),
        ':ispizza' => (bool)$isPizza,
        ':id' => (int)$id
    ]);
}

/**
 * Масове оновлення продуктів
 */
function update_products_bulk(array $items) {
    global $pdo;

    if (empty($items)) {
        return false;
    }

    $sql = "UPDATE products 
            SET name = :name, price = :price, isPizza = :ispizza 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);

    try {
        $pdo->beginTransaction();
        
        foreach ($items as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            
            if ($id <= 0) {
                continue;
            }

            $name = isset($item['name']) ? trim($item['name']) : '';
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $isPizza = !empty($item['isPizza']);

            $success = $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':ispizza' => $isPizza,
                ':id' => $id
            ]);
            
            if (!$success) {
                throw new Exception('Помилка оновлення продукту ID: ' . $id);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Створити новий продукт
 */
function create_product($name, $price, $isPizza) {
    global $pdo;
    
    $sql = "INSERT INTO products (name, price, isPizza) 
            VALUES (:name, :price, :ispizza) 
            RETURNING id";
    
    $stmt = $pdo->prepare($sql);
    
    $success = $stmt->execute([
        ':name' => trim($name),
        ':price' => floatval($price),
        ':ispizza' => (bool)$isPizza
    ]);

    if ($success) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id'] ?? true;
    }

    return false;
}

/**
 * Видалити продукт
 */
function delete_product($id) {
    global $pdo;
    
    try {
        // Перевіряємо чи не використовується продукт в замовленнях
        $checkSql = "SELECT COUNT(*) FROM orders WHERE product_id = :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':id' => (int)$id]);
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            // Продукт використовується в замовленнях - не видаляємо
            return false;
        }
        
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Отримати продукт за ID
 */
function get_product_by_id($id) {
    global $pdo;
    
    $sql = "SELECT id, name, price, isPizza FROM products WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (int)$id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>