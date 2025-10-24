<?php
require '../includes/header.php';
require '../includes/auth.php';
require '../includes/db.php';

// Handle property deletion
if (isset($_POST['delete'])) {
    $property_id = (int)$_POST['property_id'];
    
    // Verify property belongs to user
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ?");
    $stmt->execute([$property_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        $delete_stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $delete_stmt->execute([$property_id]);
        $_SESSION['success_message'] = 'Property deleted successfully';
        header("Location: properties.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Property not found or you dont have permission';
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
            <p class="empty">no properties listed yet! <a href="../add_property.php" class="btn">add new property</a></p>
        <?php else: ?>
            <?php foreach($properties as $property): ?>
                <div class="box">
                    <div class="thumb">
                        <img src="../images/<?= htmlspecialchars($property['main_image']) ?>" alt="">
                        <div class="status <?= $property['approved'] ? 'approved' : 'pending' ?>">
                            <?= $property['approved'] ? 'Approved' : 'Pending Approval' ?>
                        </div>
                        <?php if($property['featured']): ?>
                            <div class="featured">Featured</div>
                        <?php endif; ?>
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
                        <a href="../view_property.php?id=<?= $property['id'] ?>" class="btn">view property</a>
                        <a href="../edit_property.php?id=<?= $property['id'] ?>" class="btn">edit</a>
                        <form action="" method="post">
                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                            <button type="submit" name="delete" class="delete-btn" 
                                    onclick="return confirm('Delete this property?');">delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require '../includes/footer.php'; ?>