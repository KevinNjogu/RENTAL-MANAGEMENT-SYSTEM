<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
require 'includes/db.php';

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's properties
$properties_stmt = $pdo->prepare("SELECT * FROM properties WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$properties_stmt->execute([$_SESSION['user_id']]);
$properties = $properties_stmt->fetchAll();

// Include header
require 'includes/header.php';
?>

<section class="dashboard">
    <h1 class="heading">dashboard</h1>
    
    <div class="box-container">
        <div class="box">
            <p>welcome, <span><?php echo htmlspecialchars($user['name']); ?></span>!</p>
            <p>your email : <span><?php echo htmlspecialchars($user['email']); ?></span></p>
            <p>joined on : <span><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span></p>
            <a href="update_profile.php" class="btn">update profile</a>
        </div>
        
        <div class="box">
            <h3>quick actions</h3>
            <a href="add_property.php" class="inline-btn">add property</a>
            <a href="my_properties.php" class="inline-btn">my properties</a>
        </div>
    </div>
    
    <div class="my-properties">
        <h3>Your Recent Properties</h3>
        <?php if(empty($properties)): ?>
            <p class="empty">You haven't listed any properties yet. <a href="add_property.php" class="inline-btn">Add your first property</a></p>
        <?php else: ?>
            <div class="property-grid">
                <?php foreach($properties as $property): ?>
                    <div class="property-item">
                        <div class="thumb">
                            <img src="assets/images/properties/<?= htmlspecialchars($property['main_image']) ?>" alt="">
                        </div>
                        <div class="details">
                            <h4><?= htmlspecialchars($property['title']) ?></h4>
                            <p class="price">Ksh <?= number_format($property['price']) ?></p>
                            <p class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($property['location']) ?>
                            </p>
                            <div class="flex">
                                <p><i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> beds</p>
                                <p><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> baths</p>
                            </div>
                            <div class="action-buttons">
                                <a href="view_property.php?id=<?= $property['id'] ?>" class="inline-btn small">view</a>
                                <a href="delete_property.php?id=<?= $property['id'] ?>" class="inline-btn small delete-btn" onclick="return confirm('Are you sure you want to delete this property?');">delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="view-all">
                <a href="my_properties.php" class="inline-btn">view my properties</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
