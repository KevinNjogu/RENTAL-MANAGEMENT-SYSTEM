<?php
$page_title = 'Search Results';
require 'includes/header.php';
require 'includes/db.php';

// Get search parameters from POST
$location = $_POST['location'] ?? '';
$type = $_POST['type'] ?? '';
$bedrooms = $_POST['bedrooms'] ?? '';
$min_price = $_POST['minimum'] ?? '';
$max_price = $_POST['maximum'] ?? '';

// Build search query
$where = [];
$params = [];

if (!empty($location)) {
    $where[] = "location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($type)) {
    $where[] = "type = ?";
    $params[] = $type;
}

if (!empty($bedrooms)) {
    $where[] = "bedrooms >= ?";
    $params[] = $bedrooms;
}

if (!empty($min_price)) {
    $where[] = "price >= ?";
    $params[] = $min_price;
}

if (!empty($max_price)) {
    $where[] = "price <= ?";
    $params[] = $max_price;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";
$where_clause .= " AND approved = 1"; // Only show approved properties

// Search properties
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS owner_name 
    FROM properties p 
    JOIN users u ON p.user_id = u.id 
    $where_clause 
    ORDER BY created_at DESC
");
$stmt->execute($params);
$properties = $stmt->fetchAll();
?>

<section class="listings">
    <h1 class="heading">search results</h1>
    
    <div class="search-query">
        <p>Showing results for: 
            <?php 
            $filters = [];
            if ($location) $filters[] = "Location: $location";
            if ($type) $filters[] = "Type: " . ucfirst($type);
            if ($bedrooms) $filters[] = "Bedrooms: $bedrooms+";
            if ($min_price) $filters[] = "Min price: Ksh " . number_format($min_price);
            if ($max_price) $filters[] = "Max price: Ksh " . number_format($max_price);
            
            echo implode(", ", $filters);
            ?>
        </p>
        <a href="home.php" class="inline-btn">new search</a>
    </div>
    
    <div class="box-container">
        <?php if (empty($properties)): ?>
            <p class="empty">no properties found matching your search!</p>
        <?php else: ?>
            <?php foreach ($properties as $property): ?>
                <div class="box">
                    <div class="admin">
                        <h3><?= strtoupper(substr($property['owner_name'], 0, 1)) ?></h3>
                        <div>
                            <p><?= htmlspecialchars($property['owner_name']) ?></p>
                            <span><?= date('d-m-Y', strtotime($property['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="thumb">
                        <p class="total-images"><i class="far fa-image"></i><span>
                            <?php 
                            $img_stmt = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ?");
                            $img_stmt->execute([$property['id']]);
                            echo $img_stmt->fetchColumn();
                            ?>
                        </span></p>
                        <p class="type"><span><?= htmlspecialchars($property['type']) ?></span><span><?= htmlspecialchars($property['offer_type']) ?></span></p>
                        <form action="saved.php" method="post" class="save">
                            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                            <button type="submit" name="save" class="far fa-heart"></button>
                        </form>
                        <img src="assets/images/<?= htmlspecialchars($property['main_image']) ?>" alt="">
                    </div>
                    <h3 class="name"><?= htmlspecialchars($property['title']) ?></h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($property['location']) ?></span></p>
                    <div class="flex">
                        <p><i class="fas fa-bed"></i><span><?= $property['bedrooms'] ?></span></p>
                        <p><i class="fas fa-bath"></i><span><?= $property['bathrooms'] ?></span></p>
                        <p><i class="fas fa-maximize"></i><span><?= $property['area'] ?>sqft</span></p>
                    </div>
                    <a href="view_property.php?id=<?= $property['id'] ?>" class="btn">view property</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>