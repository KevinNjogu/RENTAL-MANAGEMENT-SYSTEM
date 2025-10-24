<?php
require 'includes/db.php';
require 'includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: listings.php');
    exit();
}

$property_id = (int)$_GET['id'];

// Get property details
$stmt = $pdo->prepare("SELECT p.*, u.name AS owner_name, u.phone AS owner_phone 
                      FROM properties p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.id = ? AND p.approved = 1");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['error_message'] = 'Property not found or not approved yet';
    header('Location: listings.php');
    exit();
}

// Get additional images
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
$stmt->execute([$property_id]);
$additional_images = $stmt->fetchAll();
?>

<section class="view-property">
    <div class="details">
        <div class="thumb">
            <div class="big-image">
                <img src="assets/images/properties/<?php echo htmlspecialchars($property['main_image']); ?>" alt="">
            </div>
            <?php if (!empty($additional_images)): ?>
                <div class="small-images">
                    <img src="assets/images/properties/<?php echo htmlspecialchars($property['main_image']); ?>" alt="">
                    <?php foreach ($additional_images as $image): ?>
                        <img src="assets/images/properties/<?php echo htmlspecialchars($image['image_path']); ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <h3 class="name"><?php echo htmlspecialchars($property['title']); ?></h3>
        <p class="location"><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($property['location']); ?></span></p>
        
        <div class="info">
            <p><i class="fas fa-tag"></i><span><?php echo 'Ksh ' . number_format($property['price']); ?></span></p>
            <p><i class="fas fa-user"></i><span><?php echo htmlspecialchars($property['owner_name']); ?></span></p>
            <p><i class="fas fa-phone"></i><a href="tel:<?php echo htmlspecialchars($property['owner_phone']); ?>"><?php echo htmlspecialchars($property['owner_phone']); ?></a></p>
            <p><i class="fas fa-building"></i><span><?php echo htmlspecialchars($property['type']); ?></span></p>
            <p><i class="fas fa-house"></i><span><?php echo htmlspecialchars($property['offer_type']); ?></span></p>
            <p><i class="fas fa-calendar"></i><span><?php echo date('d-m-Y', strtotime($property['created_at'])); ?></span></p>
        </div>
        
        <h3 class="title">details</h3>
        <div class="flex">
            <div class="box">
                <p><i>rooms :</i><span><?php echo $property['bedrooms']; ?> BHK</span></p>
                <p><i>deposit amount :</i><span><?php echo $property['offer_type'] == 'rent' ? '1 month' : '0'; ?></span></p>
                <p><i>status :</i><span><?php echo htmlspecialchars($property['status']); ?></span></p>
                <p><i>bedroom :</i><span><?php echo $property['bedrooms']; ?></span></p>
                <p><i>bathroom :</i><span><?php echo $property['bathrooms']; ?></span></p>
                <p><i>balcony :</i><span>1</span></p>
            </div>
            <div class="box">
                <p><i>carpet area :</i><span><?php echo $property['area']; ?>sqft</span></p>
                <p><i>age :</i><span>3 years</span></p>
                <p><i>room floor :</i><span>3</span></p>
                <p><i>total floors :</i><span>22</span></p>
                <p><i>furnished :</i><span><?php echo htmlspecialchars($property['furnished']); ?></span></p>
                <p><i>loan :</i><span>available</span></p>
            </div>
        </div>
        
        <h3 class="title">amenities</h3>
        <div class="flex">
            <div class="box">
                <p><i class="fas fa-check"></i><span>lifts</span></p>
                <p><i class="fas fa-check"></i><span>security guards</span></p>
                <p><i class="fas fa-times"></i><span>play ground</span></p>
                <p><i class="fas fa-check"></i><span>gardens</span></p>
                <p><i class="fas fa-check"></i><span>water supply</span></p>
                <p><i class="fas fa-check"></i><span>power backup</span></p>
            </div>
            <div class="box">
                <p><i class="fas fa-check"></i><span>parking area</span></p>
                <p><i class="fas fa-times"></i><span>gym</span></p>
                <p><i class="fas fa-check"></i><span>shopping mall</span></p>
                <p><i class="fas fa-check"></i><span>hospital</span></p>
                <p><i class="fas fa-check"></i><span>schools</span></p>
                <p><i class="fas fa-check"></i><span>market area</span></p>
            </div>
        </div>
        
        <h3 class="title">description</h3>
        <p class="description"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
    </div>
</section>

<script>
// Image gallery functionality
document.querySelectorAll('.view-property .thumb .small-images img').forEach(images => {
    images.onclick = () => {
        document.querySelector('.view-property .thumb .big-image img').src = images.getAttribute('src');
    };
});
</script>

<?php require 'includes/footer.php'; ?>
