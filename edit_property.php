<?php
require '../includes/db.php';
require '../includes/auth.php';

// Check if property ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Property ID not specified';
    header('Location: dashboard.php');
    exit();
}

$property_id = (int)$_GET['id'];

// Verify the property belongs to the current user or user is admin
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND (user_id = ? OR (SELECT role FROM users WHERE id = ?) = 'admin')");
$stmt->execute([$property_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['error_message'] = 'Property not found or you dont have permission to edit it';
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input data
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $type = $_POST['type'];
    $offer_type = $_POST['offer_type'];
    $price = (float)$_POST['price'];
    $bedrooms = (int)$_POST['bedrooms'];
    $bathrooms = (int)$_POST['bathrooms'];
    $area = (float)$_POST['area'];
    $location = htmlspecialchars($_POST['location']);
    $status = $_POST['status'];
    $furnished = $_POST['furnished'];
    
    // Update property in database
    $stmt = $pdo->prepare("UPDATE properties SET 
        title = ?, 
        description = ?, 
        type = ?, 
        offer_type = ?, 
        price = ?, 
        bedrooms = ?, 
        bathrooms = ?, 
        area = ?, 
        location = ?, 
        status = ?, 
        furnished = ?,
        approved = 0
        WHERE id = ?");
    
    if ($stmt->execute([
        $title, $description, $type, $offer_type, $price, 
        $bedrooms, $bathrooms, $area, $location, $status, 
        $furnished, $property_id
    ])) {
        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $image_dir = '../images/properties/';
            if (!file_exists($image_dir)) {
                mkdir($image_dir, 0777, true);
            }
            
            // Delete existing images if needed
            if (isset($_POST['delete_images'])) {
                $delete_stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ?");
                $delete_stmt->execute([$property_id]);
                $images_to_delete = $delete_stmt->fetchAll();
                
                foreach ($images_to_delete as $image) {
                    if (file_exists($image_dir . $image['image_path'])) {
                        unlink($image_dir . $image['image_path']);
                    }
                }
                
                $pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$property_id]);
            }
            
            // Upload new images
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_name = uniqid() . '.' . $file_ext;
                $upload_path = $image_dir . $new_name;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)")
                        ->execute([$property_id, 'properties/' . $new_name]);
                }
            }
            
            // Set main image if specified
            if (!empty($_POST['main_image'])) {
                $pdo->prepare("UPDATE properties SET main_image = ? WHERE id = ?")
                    ->execute([$_POST['main_image'], $property_id]);
            }
        }
        
        $_SESSION['success_message'] = 'Property updated successfully! It will need to be reapproved by admin.';
        header('Location: view_property.php?id=' . $property_id);
        exit();
    } else {
        $_SESSION['error_message'] = 'Failed to update property';
    }
}

// Get property images
$images_stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
$images_stmt->execute([$property_id]);
$property_images = $images_stmt->fetchAll();

// Set page title
$page_title = 'Edit Property: ' . htmlspecialchars($property['title']);
require '../includes/header.php';
?>

<section class="edit-property">
    <h1 class="heading">edit property</h1>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="flex">
            <div class="box">
                <h3>basic info</h3>
                <p>property title <span>*</span></p>
                <input type="text" name="title" required maxlength="100" placeholder="enter property title" class="input" value="<?= htmlspecialchars($property['title']) ?>">
                
                <p>property description <span>*</span></p>
                <textarea name="description" required maxlength="2000" placeholder="enter property description" class="input"><?= htmlspecialchars($property['description']) ?></textarea>
                
                <p>property type <span>*</span></p>
                <select name="type" class="input" required>
                    <option value="flat" <?= $property['type'] == 'flat' ? 'selected' : '' ?>>flat</option>
                    <option value="house" <?= $property['type'] == 'house' ? 'selected' : '' ?>>house</option>
                    <option value="shop" <?= $property['type'] == 'shop' ? 'selected' : '' ?>>shop</option>
                </select>
                
                <p>offer type <span>*</span></p>
                <select name="offer_type" class="input" required>
                    <option value="sale" <?= $property['offer_type'] == 'sale' ? 'selected' : '' ?>>for sale</option>
                    <option value="rent" <?= $property['offer_type'] == 'rent' ? 'selected' : '' ?>>for rent</option>
                </select>
                
                <p>property price <span>*</span></p>
                <input type="number" name="price" required min="0" step="0.01" placeholder="enter property price" class="input" value="<?= htmlspecialchars($property['price']) ?>">
            </div>
            
            <div class="box">
                <h3>property details</h3>
                <p>how many bedrooms <span>*</span></p>
                <select name="bedrooms" class="input" required>
                    <?php for ($i = 1; $i <= 9; $i++): ?>
                        <option value="<?= $i ?>" <?= $property['bedrooms'] == $i ? 'selected' : '' ?>><?= $i ?> bedroom<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
                
                <p>how many bathrooms <span>*</span></p>
                <select name="bathrooms" class="input" required>
                    <?php for ($i = 1; $i <= 9; $i++): ?>
                        <option value="<?= $i ?>" <?= $property['bathrooms'] == $i ? 'selected' : '' ?>><?= $i ?> bathroom<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
                
                <p>carpet area <span>*</span></p>
                <input type="number" name="area" required min="0" step="0.1" placeholder="enter property area (sqft)" class="input" value="<?= htmlspecialchars($property['area']) ?>">
                
                <p>property location <span>*</span></p>
                <input type="text" name="location" required maxlength="100" placeholder="enter property location" class="input" value="<?= htmlspecialchars($property['location']) ?>">
                
                <p>property status <span>*</span></p>
                <select name="status" class="input" required>
                    <option value="ready to move" <?= $property['status'] == 'ready to move' ? 'selected' : '' ?>>ready to move</option>
                    <option value="under construction" <?= $property['status'] == 'under construction' ? 'selected' : '' ?>>under construction</option>
                </select>
                
                <p>furnished status <span>*</span></p>
                <select name="furnished" class="input" required>
                    <option value="furnished" <?= $property['furnished'] == 'furnished' ? 'selected' : '' ?>>furnished</option>
                    <option value="unfurnished" <?= $property['furnished'] == 'unfurnished' ? 'selected' : '' ?>>unfurnished</option>
                    <option value="semi-furnished" <?= $property['furnished'] == 'semi-furnished' ? 'selected' : '' ?>>semi-furnished</option>
                </select>
            </div>
        </div>
        
        <div class="box">
            <h3>property images</h3>
            <p>current images:</p>
            <div class="current-images">
                <?php if (!empty($property_images)): ?>
                    <?php foreach ($property_images as $image): ?>
                        <div class="image-box">
                            <img src="../images/<?= htmlspecialchars($image['image_path']) ?>" alt="Property Image">
                            <div class="checkbox">
                                <input type="radio" name="main_image" value="<?= htmlspecialchars($image['image_path']) ?>" <?= $property['main_image'] == $image['image_path'] ? 'checked' : '' ?>>
                                <label>Set as main</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty">no images uploaded yet!</p>
                <?php endif; ?>
            </div>
            
            <div class="checkbox-container">
                <input type="checkbox" name="delete_images" id="delete_images">
                <label for="delete_images">Delete all current images and upload new ones</label>
            </div>
            
            <p>upload new images (max 10):</p>
            <input type="file" name="images[]" class="input" multiple accept="image/*">
            <p class="hint">max 5MB per image, only .jpg, .jpeg, .png allowed</p>
        </div>
        
        <input type="submit" value="update property" name="submit" class="btn">
    </form>
</section>

<?php require '../includes/footer.php'; ?>