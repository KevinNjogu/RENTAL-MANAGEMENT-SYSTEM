<?php
session_start();
require 'includes/db.php';

// Check for success message from registration
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success = '';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['pass'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email']; // Store email in session
        
        if ($user['role'] === 'admin') {
            header('Location: admin/admin_dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}

require 'includes/header.php';
?>

<section class="form-container">
   <form action="" method="post">
      <h3>welcome back!</h3>
      <?php if(!empty($error)): ?>
         <p class="error-message"><?php echo $error; ?></p>
      <?php endif; ?>
      <?php if(!empty($success)): ?>
         <p class="success-message"><?php echo $success; ?></p>
      <?php endif; ?>
      <input type="email" name="email" required maxlength="50" placeholder="enter your email" class="box" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      <input type="password" name="pass" required maxlength="20" placeholder="enter your password" class="box">
      <p>don't have an account? <a href="register.php">register new</a></p>
      <input type="submit" value="login now" name="submit" class="btn">
   </form>
</section>

<?php require 'includes/footer.php'; ?>