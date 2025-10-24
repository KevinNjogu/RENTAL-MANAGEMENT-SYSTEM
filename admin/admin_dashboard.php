<?php
require('../includes/admin_auth.php');
require('../includes/db.php');

// Get stats
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM properties) as total_properties,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM messages) as total_messages,
        (SELECT COUNT(*) FROM properties WHERE featured = 1) as featured_properties
")->fetch();

// Get recent activities
$activities = $pdo->query("
    (SELECT 'property' as type, id, title, created_at FROM properties ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'user' as type, id, name as title, created_at FROM users ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'message' as type, id, CONCAT('Message from ', name) as title, created_at FROM messages ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 10
")->fetchAll();

// Get recent messages
$recent_messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Get chart data separately to avoid complex query issues
$property_types = $pdo->query("SELECT type, COUNT(*) as count FROM properties GROUP BY type")->fetchAll();
$user_registrations = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
$message_trends = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as day, COUNT(*) as count FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY day ORDER BY day")->fetchAll();

// Price distribution with simpler query
$price_distribution = $pdo->query("
    SELECT 
        CASE 
            WHEN price < 100000 THEN 'Under $100k'
            WHEN price BETWEEN 100000 AND 250000 THEN '$100k-$250k'
            WHEN price BETWEEN 250001 AND 500000 THEN '$250k-$500k'
            WHEN price BETWEEN 500001 AND 1000000 THEN '$500k-$1M'
            ELSE 'Over $1M'
        END as price_range,
        COUNT(*) as count
    FROM properties
    GROUP BY price_range
    ORDER BY MIN(price)
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            height: 100%;
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

        .stat-card {
            position: relative;
            overflow: hidden;
            border-left: 4px solid;
            padding: 1.5rem;
            height: 100%;
            background-color: var(--white);
        }

        .stat-card.primary {
            border-left-color: var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--secondary);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 3rem;
            opacity: 0.2;
            color: var(--dark);
        }

        .chart-container {
            position: relative;
            height: 250px;
        }

        .activity-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
            border-left: 2px solid var(--gray-200);
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-item:before {
            content: "";
            position: absolute;
            left: -7px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--gray-200);
        }

        .activity-item.property:before {
            background-color: var(--primary);
        }

        .activity-item.user:before {
            background-color: var(--secondary);
        }

        .activity-item.message:before {
            background-color: var(--info);
        }

        .activity-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .activity-type {
            font-size: 0.75rem;
            color: var(--gray-600);
            display: inline-block;
            margin-top: 0.25rem;
        }

        .message-preview {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .message-preview:last-child {
            border-bottom: none;
        }

        .message-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }

        .message-content {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-time {
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .btn {
            border-radius: 0.35rem;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #3a5bc7;
            border-color: #3a5bc7;
        }

        .badge {
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 0.25rem;
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
            <a class="nav-link active" href="admin_dashboard.php">
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
            <a class="nav-link" href="users.php">
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
                <h1 class="page-title">Dashboard Overview</h1>
                <a href="generate_report.php" class="btn btn-primary">
                    <i class="fas fa-download me-2"></i> Generate Report
                </a>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card primary h-100">
                        <div class="card-body">
                            <div class="stat-title">Total Properties</div>
                            <div class="stat-value"><?= $stats['total_properties'] ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-home"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="stat-title">Total Users</div>
                            <div class="stat-value"><?= $stats['total_users'] ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card info h-100">
                        <div class="card-body">
                            <div class="stat-title">Featured Properties</div>
                            <div class="stat-value"><?= $stats['featured_properties'] ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card warning h-100">
                        <div class="card-body">
                            <div class="stat-title">Messages</div>
                            <div class="stat-value"><?= $stats['total_messages'] ?></div>
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Property Type Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="propertyTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">User Registration Trends</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="userRegistrationChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Message Trends (7 Days)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="messageTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Price Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="priceDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Activities and Messages -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Recent Activities</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item <?= $activity['type'] ?>">
                                    <h6 class="activity-title"><?= htmlspecialchars($activity['title']) ?></h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="activity-type">
                                            <span class="badge 
                                                <?= $activity['type'] == 'property' ? 'badge-primary' : '' ?>
                                                <?= $activity['type'] == 'user' ? 'badge-success' : '' ?>
                                                <?= $activity['type'] == 'message' ? 'badge-info' : '' ?>">
                                                <?= ucfirst($activity['type']) ?>
                                            </span>
                                        </span>
                                        <small class="activity-time"><?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Recent Messages</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_messages as $message): ?>
                                <div class="message-preview">
                                    <h6 class="message-title"><?= htmlspecialchars($message['name']) ?></h6>
                                    <p class="message-content"><?= substr(htmlspecialchars($message['message']), 0, 50) ?>...</p>
                                    <small class="message-time"><?= date('M d, Y h:i A', strtotime($message['created_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                            <a href="messages.php" class="btn btn-primary btn-block mt-3">
                                <i class="fas fa-envelope me-2"></i> View All Messages
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Property Type Chart
        const typeCtx = document.getElementById('propertyTypeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($property_types, 'type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($property_types, 'count')) ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                },
                cutout: '70%',
            }
        });

        // User Registration Chart
        const userCtx = document.getElementById('userRegistrationChart').getContext('2d');
        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($user_registrations, 'month')) ?>,
                datasets: [{
                    label: 'Registrations',
                    data: <?= json_encode(array_column($user_registrations, 'count')) ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: '#4e73df',
                    borderWidth: 2,
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Message Trend Chart
        const msgCtx = document.getElementById('messageTrendChart').getContext('2d');
        new Chart(msgCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($message_trends, 'day')) ?>,
                datasets: [{
                    label: 'Messages',
                    data: <?= json_encode(array_column($message_trends, 'count')) ?>,
                    backgroundColor: '#36b9cc',
                    borderColor: '#2c9faf',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Price Distribution Chart
        const priceCtx = document.getElementById('priceDistributionChart').getContext('2d');
        new Chart(priceCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($price_distribution, 'price_range')) ?>,
                datasets: [{
                    label: 'Properties',
                    data: <?= json_encode(array_column($price_distribution, 'count')) ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
    </script>
</body>
</html>