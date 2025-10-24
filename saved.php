<?php
require 'includes/auth.php';
require 'includes/header.php';
require 'includes/db.php';

// Handle removing saved property
if (isset($_POST['remove_saved'])) {
    $property_id = (int)$_POST['property_id'];
    
    $stmt = $pdo->prepare("DELETE FROM saved_properties WHERE property_id = ? AND user_id = ?");
    $stmt->execute([$property_id, $_SESSION['user_id']]);
    
    $_SESSION['success_message'] = 'Property removed from saved list';
    header("Location: saved.php");
    exit();
}

// Get user's saved properties
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS owner_name 
    FROM properties p
    JOIN saved_properties s ON p.id = s.property_id
    JOIN users u ON p.user_id = u.id
    WHERE s.user_id = ?
    ORDER BY s.saved_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$saved_properties = $stmt->fetchAll();
?>

<section class="saved-properties">
    <h1 class="heading">your saved properties</h1>
    
    <div class="box-container">
        <?php if (empty($saved_properties)): ?>
            <p class="empty">no properties saved yet! <a href="listings.php" class="btn">browse properties</a></p>
        <?php else: ?>
            <?php foreach ($saved_properties as $property): ?>
                <div class="box">
                    <div class="admin">
                        <h3><?= strtoupper(substr($property['owner_name'], 0, 1)) ?></h3>
                        <div>
                            <p><?= htmlspecialchars($property['owner_name']) ?></p>
                            <span><?= date('M j, Y', strtotime($property['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="thumb">
                        <p class="total-images"><i class="far fa-image"></i><span>
                            <?php 
                            $img_stmt = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ?");
                            $img_stmt->execute([$property['id']]);
                            echo $img_stmt->fetchColumn();
                            ?>
                        </span></p>
                        <p class="type"><span><?= htmlspecialchars($property['type']) ?></span><span><?= htmlspecialchars($property['offer_type']) ?></span></p>
                        <img src="images/<?= htmlspecialchars($property['main_image']) ?>" alt="">
                    </div>
                    <h3 class="name"><?= htmlspecialchars($property['title']) ?></h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($property['location']) ?></span></p>
                    <div class="flex">
                        <p><i class="fas fa-bed"></i><span><?= $property['bedrooms'] ?></span></p>
                        <p><i class="fas fa-bath"></i><span><?= $property['bathrooms'] ?></span></p>
                        <p><i class="fas fa-maximize"></i><span><?= $property['area'] ?>sqft</span></p>
                    </div>
                    <div class="flex-btn">
                        <a href="view_property.php?id=<?= $property['id'] ?>" class="btn">view property</a>
                        <form action="" method="post">
                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                            <button type="submit" name="remove_saved" class="delete-btn">remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>