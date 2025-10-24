<?php
session_start();
require 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['pass'];
    $confirm_password = $_POST['c_pass'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords don't match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashed_password]);
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Registration failed. Email may already exist.";
        }
    }
}

require 'includes/header.php';
?>

<section class="form-container">
   <form action="" method="post">
      <h3>create an account!</h3>
      <?php if(!empty($error)): ?>
         <p class="error-message"><?php echo $error; ?></p>
      <?php endif; ?>
      <?php if(!empty($success)): ?>
         <p class="success-message"><?php echo $success; ?></p>
      <?php endif; ?>
      <input type="text" name="name" required maxlength="50" placeholder="enter your name" class="box">
      <input type="email" name="email" required maxlength="50" placeholder="enter your email" class="box">
      <input type="tel" name="phone" required maxlength="15" placeholder="enter your phone number" class="box">
      <input type="password" name="pass" required maxlength="20" placeholder="enter your password" class="box">
      <input type="password" name="c_pass" required maxlength="20" placeholder="confirm your password" class="box">
      <input type="submit" value="register now" name="submit" class="btn">
      <p>already have an account? <a href="login.php">login now</a></p>
   </form>
</section>

<?php require 'includes/footer.php'; ?>