<?php
// Redirect with message
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION["{$type}_message"] = $message;
    }
    header("Location: $url");
    exit();
}

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format price with currency
function formatPrice($price) {
    return 'Ksh ' . number_format($price, 2);
}

// Get property type label
function getPropertyType($type) {
    $types = [
        'house' => 'House',
        'flat' => 'Flat',
        'shop' => 'Shop'
    ];
    return $types[$type] ?? ucfirst($type);
}

// Check if property is saved by user
function isSaved($pdo, $property_id, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM saved_properties WHERE property_id = ? AND user_id = ?");
    $stmt->execute([$property_id, $user_id]);
    return $stmt->fetch() ? true : false;
}
?>