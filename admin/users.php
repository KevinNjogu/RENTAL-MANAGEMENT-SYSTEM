<?php
require('../includes/admin_auth.php');
require('../includes/db.php');

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    switch ($_GET['action']) {
        case 'make_admin':
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = 'User promoted to admin';
            break;
            
        case 'remove_admin':
            $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = 'Admin privileges removed';
            break;
            
        case 'delete':
            // Don't allow deleting yourself
            if ($_SESSION['user_id'] != $user_id) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success_message'] = 'User deleted successfully';
            } else {
                $_SESSION['error_message'] = 'You cannot delete your own account';
            }
            break;
    }
    
    header("Location: users.php");
    exit();
}

// Sorting and filtering logic
$sort = $_GET['sort'] ?? 'newest';
$filter = $_GET['filter'] ?? 'all';

$order_by = 'created_at DESC';
switch ($sort) {
    case 'oldest':
        $order_by = 'created_at ASC';
        break;
    case 'name_asc':
        $order_by = 'name ASC';
        break;
    case 'name_desc':
        $order_by = 'name DESC';
        break;
}

$where = '';
switch ($filter) {
    case 'admins':
        $where = 'WHERE role = "admin"';
        break;
    case 'users':
        $where = 'WHERE role = "user"';
        break;
}

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY $order_by");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users | MyHome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4e73df;
            --primary-light: rgba(78, 115, 223, 0.1);
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --info: #36b9cc;
            --dark: #2e3a4d;
            --light: #f8f9fc;
            --white: #ffffff;
            --gray-100: #f8f9fc;
            --gray-200: #e3e6f0;
            --gray-600: #858796;
            --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(180deg, var(--primary) 0%, #224abe 100%);
            box-shadow: var(--shadow);
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2rem;
            font-weight: 800;
            text-decoration: none;
            padding: 1.5rem 1rem;
        }

        .sidebar-brand-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }

        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 1rem;
        }

        .sidebar-heading {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 0 1.5rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .nav-link i {
            margin-right: 0.5rem;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            border-left: 3px solid var(--white);
        }

        .content-wrapper {
            margin-left: 250px;
            min-height: 100vh;
            transition: var(--transition);
        }

        .topbar {
            height: 70px;
            background-color: var(--white);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 1.5rem;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 0.75rem;
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.2);
        }

        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 1.5rem;
            font-weight: 700;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background-color: var(--gray-100);
            color: var(--dark);
            font-weight: 700;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: var(--gray-100);
        }

        .badge {
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }

        .badge-primary {
            background-color: var(--primary);
        }

        .badge-success {
            background-color: var(--secondary);
        }

        .badge-info {
            background-color: var(--info);
        }

        .badge-warning {
            background-color: var(--warning);
        }

        .badge-danger {
            background-color: var(--danger);
        }

        .btn {
            border-radius: 0.35rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #3a5bc7;
            border-color: #3a5bc7;
        }

        .btn-success {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }

        .btn-success:hover {
            background-color: #17a673;
            border-color: #17a673;
        }

        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #d62c1a;
            border-color: #d62c1a;
        }

        .filter-buttons .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .filter-buttons .btn.active {
            background-color: var(--primary);
            color: white;
        }

        .sort-dropdown .dropdown-toggle::after {
            display: none;
        }

        .sort-dropdown .dropdown-item.active {
            background-color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-200);
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .user-avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-left: 0;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-title {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a class="sidebar-brand" href="admin_dashboard.php">
            <div class="sidebar-brand-icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="sidebar-brand-text">MyHome Admin</div>
        </a>
        
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">Core</div>
        
        <li class="nav-item">
            <a class="nav-link" href="admin_dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">Management</div>
        
        <li class="nav-item">
            <a class="nav-link" href="properties.php">
                <i class="fas fa-home"></i>
                <span>Properties</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link active" href="users.php">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
            </a>
        </li>
        
        <hr class="sidebar-divider">
        
        <div class="sidebar-heading">Actions</div>
        
        <li class="nav-item">
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </div>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Topbar -->
        <nav class="topbar">
            <div class="dropdown">
                <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="me-2 d-none d-md-inline text-gray-600"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">User Management</h1>
                <div class="dropdown sort-dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-sort me-2"></i>
                        <?php 
                            switch($sort) {
                                case 'newest': echo 'Newest First'; break;
                                case 'oldest': echo 'Oldest First'; break;
                                case 'name_asc': echo 'Name (A-Z)'; break;
                                case 'name_desc': echo 'Name (Z-A)'; break;
                                default: echo 'Sort'; break;
                            }
                        ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item <?= $sort == 'newest' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=newest">Newest First</a></li>
                        <li><a class="dropdown-item <?= $sort == 'oldest' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=oldest">Oldest First</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= $sort == 'name_asc' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=name_asc">Name (A-Z)</a></li>
                        <li><a class="dropdown-item <?= $sort == 'name_desc' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=name_desc">Name (Z-A)</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Filter Buttons -->
            <div class="filter-buttons mb-4">
                <a href="?filter=all&sort=<?= $sort ?>" class="btn <?= $filter == 'all' ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i>All Users
                </a>
                <a href="?filter=admins&sort=<?= $sort ?>" class="btn <?= $filter == 'admins' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield me-2"></i>Admins
                </a>
                <a href="?filter=users&sort=<?= $sort ?>" class="btn <?= $filter == 'users' ? 'active' : '' ?>">
                    <i class="fas fa-user me-2"></i>Regular Users
                </a>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">User List</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <h4>No Users Found</h4>
                            <p>There are currently no users matching your criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar-sm">
                                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                                        <small class="text-muted">ID: <?= $user['id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge <?= $user['role'] == 'admin' ? 'badge-success' : 'badge-primary' ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <?php if ($user['role'] === 'user'): ?>
                                                    <a href="users.php?action=make_admin&id=<?= $user['id'] ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-user-shield me-1"></i>Make Admin
                                                    </a>
                                                <?php else: ?>
                                                    <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                                        <a href="users.php?action=remove_admin&id=<?= $user['id'] ?>" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-user me-1"></i>Remove Admin
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                                    <a href="users.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>