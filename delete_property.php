<?php
require_once(__DIR__ . '/includes/db.php');
require_once(__DIR__ . '/includes/auth.php');



if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $property_id = $_GET['id'];

    // Validate property ID
    if (!is_numeric($property_id)) {
        header("HTTP/1.1 400 Bad Request");
        exit("Invalid property ID.");
    }

    try {
        // First, delete any images associated with the property
        $stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
        $stmt->execute([$property_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($images as $image) {
            if (file_exists(__DIR__ . '/../' . $image['image_path'])) {
                unlink(__DIR__ . '/../' . $image['image_path']);
            }
        }

        // Delete from property_images table
        $stmt = $pdo->prepare("DELETE FROM property_images WHERE property_id = ?");
        $stmt->execute([$property_id]);

        // Finally, delete the property
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);

        // Redirect back to the referring page
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'manage_properties.php'; // Default to 'manage_properties.php' if no referrer
        header("Location: $redirect_url?deleted=1");
        exit();
    } catch (PDOException $e) {
        die("Error deleting property: " . $e->getMessage());
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid request.");
}
?>
