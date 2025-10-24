<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = isset($page_title) ? $page_title : 'MyHome - Real Estate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
   
<header class="header">
    <nav class="navbar nav-1">
        <section class="flex">
            <a href="home.php" class="logo"><i class="fas fa-house"></i>MyHome</a>
            <ul>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="add_property.php">post property<i class="fas fa-paper-plane"></i></a></li>
                <?php else: ?>
                    <li><a href="login.php">post property<i class="fas fa-paper-plane"></i></a></li>
                <?php endif; ?>
            </ul>
        </section>
    </nav>

    <nav class="navbar nav-2">
        <section class="flex">
            <div id="menu-btn" class="fas fa-bars"></div>
            <div class="menu">
                <ul>
                    <li><a href="#">Help<i class="fas fa-angle-down"></i></a>
                        <ul>
                            <li><a href="about.php">about us</a></li>
                            <li><a href="contact.php">contact us</a></li>
                            <li><a href="contact.php#faq">FAQ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>

            <ul>
                <li><a href="#">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    <?php else: ?>
                        account <i class="fas fa-angle-down"></i>
                    <?php endif; ?>
                </a>
                    <ul>
                        <?php if(isset($_SESSION['user_id'])): ?>
                           <li><a href="dashboard.php" class="inline-btn">Dashboard</a></li>
                            <li><a href="logout.php">logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">login</a></li>
                            <li><a href="register.php">register</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </section>
    </nav>
</header>

<!-- Display success/error messages if they exist -->
<?php if(isset($_SESSION['success_message'])): ?>
    <div class="message success">
        <p><?php echo $_SESSION['success_message']; ?></p>
        <?php unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
    <div class="message error">
        <p><?php echo $_SESSION['error_message']; ?></p>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<main class="main-content">