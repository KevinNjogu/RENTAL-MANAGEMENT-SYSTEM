<?php
require 'includes/auth.php';
require 'includes/header.php';
require 'includes/db.php';

// Handle property deletion
if (isset($_GET['delete'])) {
    $property_id = (int)$_GET['delete'];
    
    // Verify property belongs to user
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
    $stmt->execute([$property_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        // Delete property images first
        $images_stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
        $images_stmt->execute([$property_id]);
        $images = $images_stmt->fetchAll();
        
        foreach ($images as $image) {
            if (file_exists('images/properties/'.$image['image_path'])) {
                unlink('images/properties/'.$image['image_path']);
            }
        }
        
        // Delete from database
        $delete_stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $delete_stmt->execute([$property_id]);
        
        $pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$property_id]);
        $pdo->prepare("DELETE FROM saved_properties WHERE property_id = ?")->execute([$property_id]);
        
        $_SESSION['success_message'] = 'Property deleted successfully';
        header("Location: my_properties.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Property not found or you don\'t have permission';
        header("Location: my_properties.php");
        exit();
    }
}

// Get user's properties
$stmt = $pdo->prepare("SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$properties = $stmt->fetchAll();
?>

<section class="user-properties">
    <h1 class="heading">your listed properties</h1>
    
    <div class="box-container">
        <?php if(empty($properties)): ?>
            <p class="empty">no properties listed yet! <a href="add_property.php" class="btn">add new property</a></p>
        <?php else: ?>
            <?php foreach($properties as $property): ?>
                <div class="box">
                    <div class="thumb">
                        <img src="assets/images/properties/<?= htmlspecialchars($property['main_image']) ?>" alt="">
                        <div class="status <?= $property['featured'] ? 'featured' : '' ?>">
                            <?= $property['featured'] ? 'Featured' : 'Your Listing' ?>
                        </div>
                    </div>
                    <h3 class="name"><?= htmlspecialchars($property['title']) ?></h3>
                    <p class="price">Ksh <?= number_format($property['price']) ?></p>
                    <p class="location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($property['location']) ?></span>
                    </p>
                    <div class="flex">
                        <p><i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> beds</p>
                        <p><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> baths</p>
                        <p><i class="fas fa-maximize"></i> <?= $property['area'] ?> sqft</p>
                    </div>
                    <div class="flex-btn">
                        <a href="view_property.php?id=<?= $property['id'] ?>" class="btn">view property</a>
                        <a href="my_properties.php?delete=<?= $property['id'] ?>" class="delete-btn" onclick="return confirm('Delete this property?');">delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>