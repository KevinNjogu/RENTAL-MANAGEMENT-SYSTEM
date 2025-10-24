<?php
require __DIR__ . '/admin_auth.php';

// Database connection (add this if not already in admin_auth.php)
if (!isset($pdo)) {
    require __DIR__ . '/../includes/db.php'; // Adjust path as needed
}

// Handle message deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --gray: #dddfeb;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            padding: 0;
            color: var(--dark);
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray);
        }
        
        .page-title {
            font-size: 28px;
            color: var(--primary);
            margin: 0;
        }
        
        .message-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .message-table th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .message-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray);
        }
        
        .message-table tr:last-child td {
            border-bottom: none;
        }
        
        .message-table tr:hover {
            background-color: #f5f7ff;
        }
        
        .message-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .inline-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .inline-btn.view {
            background-color: var(--primary);
            color: white;
        }
        
        .inline-btn.delete-btn {
            background-color: var(--danger);
            color: white;
        }
        
        .inline-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .empty-message {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            color: var(--dark);
        }
        
        .email-link {
            color: var(--primary);
            text-decoration: none;
        }
        
        .email-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">Messages</h1>
        </div>
        
        <div class="message-list">
            <?php if (empty($messages)): ?>
                <div class="empty-message">
                    <i class="fas fa-envelope-open-text" style="font-size: 48px; color: var(--gray); margin-bottom: 15px;"></i>
                    <h3>No messages found</h3>
                    <p>There are currently no messages in the system.</p>
                </div>
            <?php else: ?>
                <table class="message-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Message Preview</th>
                            <th>Date Received</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?= $message['id'] ?></td>
                                <td><?= htmlspecialchars($message['name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($message['email']) ?>" class="email-link"><?= htmlspecialchars($message['email']) ?></a></td>
                                <td><?= htmlspecialchars($message['phone'] ?? 'N/A') ?></td>
                                <td class="message-preview" title="<?= htmlspecialchars($message['message']) ?>">
                                    <?= substr(htmlspecialchars($message['message']), 0, 50) ?>...
                                </td>
                                <td><?= date('M j, Y h:i A', strtotime($message['created_at'])) ?></td>
                                <td>
                                    <a href="view_message.php?id=<?= $message['id'] ?>" class="inline-btn view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="messages.php?action=delete&id=<?= $message['id'] ?>" class="inline-btn delete-btn" onclick="return confirm('Are you sure you want to delete this message?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>