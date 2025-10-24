<?php
require('../includes/admin_auth.php');
require('../includes/db.php');

// Price formatting function
function formatPrice($price) {
    return '$' . number_format($price);
}

// Handle property actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    
    switch ($_GET['action']) {
        case 'feature':
            $stmt = $pdo->prepare("UPDATE properties SET featured = NOT featured WHERE id = ?");
            $stmt->execute([$property_id]);
            $_SESSION['success_message'] = 'Property feature status updated';
            break;
            
        case 'delete':
            // First get the image to delete it from server
            $stmt = $pdo->prepare("SELECT main_image FROM properties WHERE id = ?");
            $stmt->execute([$property_id]);
            $image = $stmt->fetchColumn();
            
            if ($image && file_exists("../assets/images/properties/$image")) {
                unlink("../assets/images/properties/$image");
            }
            
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->execute([$property_id]);
            $_SESSION['success_message'] = 'Property deleted successfully';
            break;
    }
    
    header("Location: properties.php");
    exit();
}

// Get filter if any
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

$where = '';
if ($filter === 'featured') {
    $where = 'WHERE p.featured = 1';
}

$order_by = 'p.created_at DESC';
switch ($sort) {
    case 'oldest':
        $order_by = 'p.created_at ASC';
        break;
    case 'price_asc':
        $order_by = 'p.price ASC';
        break;
    case 'price_desc':
        $order_by = 'p.price DESC';
        break;
    case 'title_asc':
        $order_by = 'p.title ASC';
        break;
    case 'title_desc':
        $order_by = 'p.title DESC';
        break;
}

// Get all properties
$stmt = $pdo->prepare("SELECT p.*, u.name AS owner_name 
                      FROM properties p 
                      JOIN users u ON p.user_id = u.id 
                      $where 
                      ORDER BY $order_by");
$stmt->execute();
$properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Properties | MyHome</title>
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

        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .property-card {
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: var(--transition);
            background-color: var(--white);
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .property-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .property-card:hover .property-image img {
            transform: scale(1.05);
        }

        .featured-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: var(--warning);
            color: var(--dark);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .property-details {
            padding: 1.25rem;
        }

        .property-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .property-location {
            display: flex;
            align-items: center;
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .property-location i {
            margin-right: 0.5rem;
        }

        .property-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .property-specs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .property-spec {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .property-spec i {
            margin-right: 0.25rem;
            color: var(--primary);
        }

        .property-owner {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .property-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            border-radius: 0.35rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            flex: 1;
            text-align: center;
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

        .btn-warning {
            background-color: var(--warning);
            border-color: var(--warning);
            color: var(--dark);
        }

        .btn-warning:hover {
            background-color: #e0b123;
            border-color: #e0b123;
            color: var(--dark);
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
            grid-column: 1 / -1;
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
            
            .property-grid {
                grid-template-columns: 1fr;
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
            <a class="nav-link active" href="properties.php">
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
                <h1 class="page-title">Property Management</h1>
                <div class="dropdown sort-dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-sort me-2"></i>
                        <?php 
                            switch($sort) {
                                case 'newest': echo 'Newest First'; break;
                                case 'oldest': echo 'Oldest First'; break;
                                case 'price_asc': echo 'Price (Low-High)'; break;
                                case 'price_desc': echo 'Price (High-Low)'; break;
                                case 'title_asc': echo 'Title (A-Z)'; break;
                                case 'title_desc': echo 'Title (Z-A)'; break;
                                default: echo 'Sort'; break;
                            }
                        ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                        <li><a class="dropdown-item <?= $sort == 'newest' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=newest">Newest First</a></li>
                        <li><a class="dropdown-item <?= $sort == 'oldest' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=oldest">Oldest First</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= $sort == 'price_asc' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=price_asc">Price (Low-High)</a></li>
                        <li><a class="dropdown-item <?= $sort == 'price_desc' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=price_desc">Price (High-Low)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= $sort == 'title_asc' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=title_asc">Title (A-Z)</a></li>
                        <li><a class="dropdown-item <?= $sort == 'title_desc' ? 'active' : '' ?>" href="?filter=<?= $filter ?>&sort=title_desc">Title (Z-A)</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Filter Buttons -->
            <div class="filter-buttons mb-4">
                <a href="?filter=all&sort=<?= $sort ?>" class="btn <?= $filter == 'all' ? 'active' : '' ?>">
                    <i class="fas fa-home me-2"></i>All Properties
                </a>
                <a href="?filter=featured&sort=<?= $sort ?>" class="btn <?= $filter == 'featured' ? 'active' : '' ?>">
                    <i class="fas fa-star me-2"></i>Featured Properties
                </a>
            </div>
            
            <!-- Properties Grid -->
            <div class="card">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Property List</h5>
                </div>
                <div class="card-body">
                    <div class="property-grid">
                        <?php if (empty($properties)): ?>
                            <div class="empty-state">
                                <i class="fas fa-home"></i>
                                <h4>No Properties Found</h4>
                                <p>There are currently no properties matching your criteria.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($properties as $property): ?>
                                <div class="property-card">
                                    <div class="property-image">
                                        <img src="../assets/images/properties/<?= htmlspecialchars($property['main_image']) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                                        <?php if ($property['featured']): ?>
                                            <div class="featured-badge">
                                                <i class="fas fa-star"></i> Featured
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="property-details">
                                        <h3 class="property-title"><?= htmlspecialchars($property['title']) ?></h3>
                                        <div class="property-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($property['location']) ?>
                                        </div>
                                        <div class="property-price"><?= formatPrice($property['price']) ?></div>
                                        
                                        <div class="property-specs">
                                            <div class="property-spec">
                                                <i class="fas fa-bed"></i>
                                                <?= $property['bedrooms'] ?> beds
                                            </div>
                                            <div class="property-spec">
                                                <i class="fas fa-bath"></i>
                                                <?= $property['bathrooms'] ?> baths
                                            </div>
                                            <div class="property-spec">
                                                <i class="fas fa-ruler-combined"></i>
                                                <?= $property['area'] ?> sqft
                                            </div>
                                        </div>
                                        
                                        <div class="property-owner">
                                            <i class="fas fa-user"></i> Posted by: <?= htmlspecialchars($property['owner_name']) ?>
                                        </div>
                                        
                                        <div class="property-actions">
                                            <a href="properties.php?action=feature&id=<?= $property['id'] ?>" class="btn <?= $property['featured'] ? 'btn-warning' : 'btn-success' ?>">
                                                <i class="fas fa-star"></i> <?= $property['featured'] ? 'Unfeature' : 'Feature' ?>
                                            </a>
                                            <a href="../view_property.php?id=<?= $property['id'] ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="properties.php?action=delete&id=<?= $property['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this property?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>