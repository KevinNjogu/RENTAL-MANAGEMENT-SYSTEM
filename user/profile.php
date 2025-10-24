<?php
require '../includes/header.php';
require '../includes/auth.php';
require '../includes/db.php';

$errors = [];
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    // Update basic info
    $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $_SESSION['user_id']]);
    
    // Password change
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $success = 'Profile and password updated successfully';
        }
    } else {
        $success = 'Profile updated successfully';
    }
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>

<section class="edit-profile">
    <h1 class="heading">update profile</h1>
    
    <?php if(!empty($errors)): ?>
        <div class="error-message">
            <?php foreach($errors as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="success-message">
            <p><?= $success ?></p>
        </div>
    <?php endif; ?>
    
    <form action="" method="post">
        <div class="box">
            <p>name <span>*</span></p>
            <input type="text" name="name" required maxlength="50" 
                   value="<?= htmlspecialchars($user['name']) ?>" class="input">
        </div>
        <div class="box">
            <p>email</p>
            <input type="email" name="email" class="input" 
                   value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>
        <div class="box">
            <p>phone number</p>
            <input type="text" name="phone" maxlength="15" 
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="input">
        </div>
        <div class="box">
            <p>current password (leave blank to keep unchanged)</p>
            <input type="password" name="current_password" maxlength="20" class="input">
        </div>
        <div class="box">
            <p>new password (leave blank to keep unchanged)</p>
            <input type="password" name="new_password" maxlength="20" class="input">
        </div>
        <div class="box">
            <p>confirm new password</p>
            <input type="password" name="confirm_password" maxlength="20" class="input">
        </div>
        <input type="submit" value="update profile" name="submit" class="btn">
    </form>
</section>

<?php require '../includes/footer.php'; ?>