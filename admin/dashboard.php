<?php
require '../includes/admin_auth.php';
$page_title = 'Admin Dashboard';
require '../includes/header.php';
?>

<section class="admin-dashboard">
    <h1 class="heading">admin dashboard</h1>
    
    <div class="box-container">
        <div class="box">
            <h3>welcome!</h3>
            <p><?= htmlspecialchars($_SESSION['user_name']) ?></p>
            <p>you are logged in as admin</p>
        </div>
        
        <div class="box">
            <h3>quick actions</h3>
            <a href="properties.php" class="inline-btn">manage properties</a>
            <a href="users.php" class="inline-btn">manage users</a>
            <a href="messages.php" class="inline-btn">view messages</a>
        </div>
    </div>
</section>

<?php require '../includes/footer.php'; ?>