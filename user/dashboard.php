<?php
require '../includes/header.php';
require '../includes/auth.php';
require '../includes/db.php';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Count user's properties
$properties_stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE user_id = ?");
$properties_stmt->execute([$_SESSION['user_id']]);
$property_count = $properties_stmt->fetchColumn();

// Count saved properties
$saved_stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_properties WHERE user_id = ?");
$saved_stmt->execute([$_SESSION['user_id']]);
$saved_count = $saved_stmt->fetchColumn();
?>

<section class="user-dashboard">
    <h1 class="heading">your dashboard</h1>
    
    <div class="box-container">
        <div class="box">
            <p>welcome back, <span><?= htmlspecialchars($user['name']) ?></span>!</p>
            <p>email : <span><?= htmlspecialchars($user['email']) ?></span></p>
            <p>member since : <span><?= date('M j, Y', strtotime($user['created_at'])) ?></span></p>
            <a href="profile.php" class="btn">update profile</a>
        </div>
        
        <div class="box">
            <h3><?= $property_count ?></h3>
            <p>properties listed</p>
            <a href="properties.php" class="btn">view properties</a>
            <a href="../add_property.php" class="btn">add new</a>
        </div>
        
        <div class="box">
            <h3><?= $saved_count ?></h3>
            <p>properties saved</p>
            <a href="saved.php" class="btn">view saved</a>
        </div>
    </div>
</section>

<?php require '../includes/footer.php'; ?>