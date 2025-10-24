<?php
require 'includes/header.php';
require 'includes/db.php';

// Get filter parameters from URL
$type_filter = $_GET['type'] ?? null;
$offer_filter = $_GET['offer_type'] ?? null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;

// Build base SQL query
$sql = "SELECT p.*, u.name AS owner_name, u.phone AS owner_phone, u.email AS owner_email 
        FROM properties p 
        JOIN users u ON p.user_id = u.id 
        WHERE 1=1"; // Removed approval check

$params = [];
$where = [];

// Apply filters
if ($type_filter) {
    $where[] = "p.type = ?";
    $params[] = $type_filter;
}

if ($offer_filter) {
    $where[] = "p.offer_type = ?";
    $params[] = $offer_filter;
}

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM ($sql) AS total";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_properties = $count_stmt->fetchColumn();

// Calculate pagination
$total_pages = max(1, ceil($total_properties / $per_page));
$offset = ($page - 1) * $per_page;

// Append pagination directly into SQL string
$sql .= " ORDER BY p.created_at DESC LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Listings</title>
    <style>
    
        .listings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Arial', sans-serif;
        }
        
        .filter-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: #DA2C32;
            color: white;
            border-color: #DA2C32;
        }
        
        .property-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .property-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .property-header {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .property-title {
            margin: 0;
            color: #333;
            font-size: 1.4rem;
        }
        
        .property-meta {
            display: flex;
            gap: 15px;
            margin-top: 5px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .property-body {
            display: flex;
            flex-wrap: wrap;
        }
        
        .property-image {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .property-image img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        
        .image-count {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .property-details {
            flex: 2;
            min-width: 300px;
            padding: 15px;
        }
        
        .detail-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f5f5f5;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .property-price {
            font-size: 1.3rem;
            margin: 15px 0;
            color: #DA2C32;
            font-weight: bold;
        }
        
        .price-period {
            font-size: 0.9rem;
            color: #666;
        }
        
        .property-description {
            margin: 15px 0;
            line-height: 1.5;
            color: #444;
        }
        
        .property-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }
        
        .owner-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .owner-avatar {
            width: 40px;
            height: 40px;
            background: #DA2C32;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .owner-name {
            font-weight: bold;
        }
        
        .property-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-view {
            background: #DA2C32;
            color: white;
        }
        
        .btn-view:hover {
            background: #b82228;
        }
        
        .btn-pdf {
            background: #333;
            color: white;
        }
        
        .btn-pdf:hover {
            background: #111;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .page-link {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .page-link.active {
            background: #DA2C32;
            color: white;
            border-color: #DA2C32;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #DA2C32;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 20px;
        }
        </style>
</head>
<body>
    <div class="listings-container">
        <h1 class="heading">
            <?php 
            if ($type_filter && $offer_filter) {
                echo ucfirst($offer_filter) . " " . ucfirst($type_filter) . " Properties";
            } elseif ($type_filter) {
                echo "All " . ucfirst($type_filter) . " Properties";
            } elseif ($offer_filter) {
                echo "Properties for " . ucfirst($offer_filter);
            } else {
                echo "All Properties";
            }
            ?>
        </h1>

        <div class="filter-bar">
            <a href="listings.php" class="filter-btn <?= !$type_filter && !$offer_filter ? 'active' : '' ?>">All Properties</a>
            <a href="listings.php?offer_type=sale" class="filter-btn <?= $offer_filter === 'sale' ? 'active' : '' ?>">For Sale</a>
            <a href="listings.php?offer_type=rent" class="filter-btn <?= $offer_filter === 'rent' ? 'active' : '' ?>">For Rent</a>
            <a href="listings.php?type=house" class="filter-btn <?= $type_filter === 'house' ? 'active' : '' ?>">Houses</a>
            <a href="listings.php?type=flat" class="filter-btn <?= $type_filter === 'flat' ? 'active' : '' ?>">Flats</a>
            <a href="listings.php?type=shop" class="filter-btn <?= $type_filter === 'shop' ? 'active' : '' ?>">Shops</a>
        </div>

        <div class="property-list">
            <?php if (empty($properties)): ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <p>No properties found matching your criteria</p>
                    <a href="listings.php" class="filter-btn">Browse all properties</a>
                </div>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                    <div class="property-header">
                            <h2 class="property-title"><?= htmlspecialchars($property['title']) ?></h2>
                            <div class="property-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></span>
                                <span><i class="fas fa-calendar-alt"></i> Posted: <?= date('M d, Y', strtotime($property['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="property-body">
                            <div class="property-image">
                                <img src="assets/images/properties/<?= htmlspecialchars($property['main_image'] ?? 'default-house.jpg') ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                                <div class="image-count">
                                    <i class="far fa-image"></i> 
                                    <?php 
                                        $img_stmt = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ?");
                                        $img_stmt->execute([$property['id']]);
                                        echo $img_stmt->fetchColumn() + 1;
                                    ?>
                                </div>
                            </div>
                            
                            <div class="property-details">
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <i class="fas fa-home"></i>
                                        <span><?= ucfirst(htmlspecialchars($property['type'])) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?= ucfirst(htmlspecialchars($property['offer_type'])) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-bed"></i>
                                        <span><?= $property['bedrooms'] ?> Bedrooms</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-bath"></i>
                                        <span><?= $property['bathrooms'] ?> Bathrooms</span>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <i class="fas fa-ruler-combined"></i>
                                        <span><?= $property['area'] ?> sqft</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?= ucfirst(htmlspecialchars($property['status'])) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-couch"></i>
                                        <span><?= ucfirst(htmlspecialchars($property['furnished'])) ?></span>
                                    </div>
                                </div>
                                
                                <div class="property-price">
                                    <span>Price:</span>
                                    <strong>Ksh <?= number_format($property['price']) ?></strong>
                                    <?php if ($property['offer_type'] == 'rent'): ?>
                                        <span class="price-period">per month</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-description">
                                    <p><?= nl2br(htmlspecialchars(substr($property['description'], 0, 250))) ?>...</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="property-footer">
                            <div class="owner-info">
                                <div class="owner-avatar">
                                    <?= strtoupper(substr($property['owner_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span>Posted by</span>
                                    <div class="owner-name"><?= htmlspecialchars($property['owner_name']) ?></div>
                                </div>
                            </div>
                            
                            <div class="property-actions">
                                <a href="view_property.php?id=<?= $property['id'] ?>" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="generate_pdf.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">
                                        Download Agent Data
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="listings.php?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="listings.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="listings.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="listings.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="listings.php?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="page-link">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php require 'includes/footer.php'; ?>