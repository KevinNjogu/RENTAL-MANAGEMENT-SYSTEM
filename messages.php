<?php
require 'includes/admin_auth.php';
require '../includes/header.php';
require '../includes/db.php';

// Handle message deletion
if (isset($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    
    $_SESSION['success_message'] = 'Message deleted successfully';
    header("Location: messages.php");
    exit();
}

// Get all messages
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();
?>

<section class="admin-messages">
    <h1 class="heading">contact messages</h1>
    
    <div class="message-list">
        <?php if (empty($messages)): ?>
            <p class="empty">no messages found!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                        <tr>
                            <td><?= $message['id'] ?></td>
                            <td><?= htmlspecialchars($message['name']) ?></td>
                            <td><?= htmlspecialchars($message['email']) ?></td>
                            <td><?= htmlspecialchars($message['number']) ?></td>
                            <td><?= substr(htmlspecialchars($message['message']), 0, 50) ?>...</td>
                            <td><?= date('M j, Y', strtotime($message['created_at'])) ?></td>
                            <td class="action-buttons">
                                <a href="view_message.php?id=<?= $message['id'] ?>" class="inline-btn">view</a>
                                <a href="messages.php?delete=<?= $message['id'] ?>" class="inline-btn delete-btn" onclick="return confirm('Delete this message?');">delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require '../includes/footer.php'; ?>