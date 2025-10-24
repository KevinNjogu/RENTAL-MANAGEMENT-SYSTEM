<?php
$page_title = 'Contact Us';
require 'includes/header.php';
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $number = trim($_POST['number']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($number) || empty($message)) {
        $_SESSION['error_message'] = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Invalid email format';
    } else {
        try {
            // Save to database
            $stmt = $pdo->prepare("INSERT INTO messages (name, email, number, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $number, $message]);
            
            $_SESSION['success_message'] = 'Your message has been sent successfully!';
            header("Location: contact.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error sending message. Please try again later.';
        }
    }
}
?>

<section class="contact">
    <div class="row">
        <div class="image">
            <img src="assets/images/contact-img.svg" alt="">
        </div>
        <form action="" method="POST">
            <h3>get in touch</h3>
            <input type="text" name="name" required maxlength="50" placeholder="enter your name" class="box" 
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            <input type="email" name="email" required maxlength="50" placeholder="enter your email" class="box"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <input type="text" name="number" required maxlength="20" placeholder="enter your number" class="box"
                   value="<?= htmlspecialchars($_POST['number'] ?? '') ?>">
            <textarea name="message" placeholder="enter your message" required maxlength="1000" cols="30" rows="10" 
                      class="box"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            <input type="submit" value="send message" name="send" class="btn">
        </form>
    </div>
</section>

<section class="faq" id="faq">
    <h1 class="heading">FAQ</h1>
    <div class="box-container">
        <div class="box active">
            <h3><span>how to cancel booking?</span><i class="fas fa-angle-down"></i></h3>
            <p>Contact us at least 48 hours before your scheduled viewing to cancel or reschedule.</p>
        </div>
        <div class="box active">
            <h3><span>when will I get the possession?</span><i class="fas fa-angle-down"></i></h3>
            <p>Possession dates vary by property and agreement terms, typically 30-60 days after contract signing.</p>
        </div>
        <div class="box">
            <h3><span>where can I pay the rent?</span><i class="fas fa-angle-down"></i></h3>
            <p>Rent payments can be made through our secure online portal or at our office.</p>
        </div>
        <div class="box">
            <h3><span>how to contact with the buyers?</span><i class="fas fa-angle-down"></i></h3>
            <p>Our agents will facilitate all communications between buyers and sellers.</p>
        </div>
        <div class="box">
            <h3><span>why my listing not showing up?</span><i class="fas fa-angle-down"></i></h3>
            <p>New listings undergo a verification process and typically appear within 24 hours.</p>
        </div>
        <div class="box">
            <h3><span>how to promote my listing?</span><i class="fas fa-angle-down"></i></h3>
            <p>We offer premium listing options that increase visibility across our platforms.</p>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>