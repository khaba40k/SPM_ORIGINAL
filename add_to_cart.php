<?php
session_start();

// Перевірка, чи доступні cookies
$cookies_enabled = count($_COOKIE) > 0;

// Отримуємо ID товару з GET або POST
$type = $_POST['service_ID'] ?? null;
$subtype = $_POST['type_ID'] ?? 1;
$color = $_POST['color'] ?? null;

if (!$type) {
    die(var_dump($_POST));
}

// Формуємо унікальний ключ товару
$itemKey = "{$type}_{$subtype}_{$color}";

// Отримуємо поточну корзину
$cart = [];

if ($cookies_enabled && isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true) ?? [];
} elseif (isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
}

// Додаємо товар (інкрементуємо кількість)
if (isset($cart[$itemKey])) {
    $cart[$itemKey]['quantity'] += 1;
} else {
    $cart[$itemKey] = [
        'service_ID' => $type,
        'type_ID' => $subtype,
        'color' => $color,
        'quantity' => 1
    ];
}

// Зберігаємо оновлену корзину
if ($cookies_enabled) {
    setcookie('cart', json_encode($cart), time() + 3600, '/'); // 1 година
} else {
    $_SESSION['cart'] = $cart;
}

echo "Товар успішно додано до корзини!";
?>
