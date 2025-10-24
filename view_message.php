<?php
require 'includes/admin_auth.php';
require '../includes/header.php';
require '../includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: messages.php');
    exit();
}

$message_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$message_id]);
$message = $stmt->fetch();

if (!$message) {
    header('Location: messages.php');
    exit();
}
?>

<section class="view-message">
    <h1 class="heading">message details</h1>
    
    <div class="message-details">
        <div class="detail-row">
            <h3>From:</h3>
            <p><?= htmlspecialchars($message['name']) ?></p>
        </div>
        <div class="detail-row">
            <h3>Email:</h3>
            <p><a href="mailto:<?= htmlspecialchars($message['email']) ?>"><?= htmlspecialchars($message['email']) ?></a></p>
        </div>
        <div class="detail-row">
            <h3>Phone:</h3>
            <p><a href="tel:<?= htmlspecialchars($message['number']) ?>"><?= htmlspecialchars($message['number']) ?></a></p>
        </div>
        <div class="detail-row">
            <h3>Date:</h3>
            <p><?= date('F j, Y \a\t g:i a', strtotime($message['created_at'])) ?></p>
        </div>
        <div class="message-content">
            <h3>Message:</h3>
            <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
        </div>
        <div class="action-buttons">
            <a href="messages.php" class="inline-btn">back to messages</a>
            <a href="messages.php?delete=<?= $message['id'] ?>" class="inline-btn delete-btn" onclick="return confirm('Delete this message?');">delete message</a>
        </div>
    </div>
</section>

<?php require '../includes/footer.php'; ?>