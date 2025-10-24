<?php
require 'includes/auth.php';
require 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file upload first
    $main_image = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'assets/images/properties/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['main_image']['name']);
        $target_path = $upload_dir . $file_name;
        
        $check = getimagesize($_FILES['main_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $target_path)) {
                $main_image = $file_name;
            } else {
                $error = 'Sorry, there was an error uploading your file.';
            }
        } else {
            $error = 'File is not an image.';
        }
    }

    if (empty($error)) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $type = $_POST['type'];
        $offer_type = $_POST['offer_type'];
        $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $bedrooms = (int)$_POST['bedrooms'];
        $bathrooms = (int)$_POST['bathrooms'];
        $area = filter_var($_POST['area'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $location = $_POST['location'];
        $status = $_POST['status'];
        $furnished = $_POST['furnished'];
        
        if (empty($title) || empty($price) || empty($area)) {
            $error = 'Title, price and area are required fields';
        } elseif (!is_numeric($price) || $price <= 0) {
            $error = 'Price must be a valid positive number';
        } elseif (!is_numeric($area) || $area <= 0) {
            $error = 'Area must be a valid positive number';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO properties 
                    (user_id, title, description, type, offer_type, price, bedrooms, 
                    bathrooms, area, location, status, furnished, main_image, approved) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $title,
                    $description,
                    $type,
                    $offer_type,
                    $price,
                    $bedrooms,
                    $bathrooms,
                    $area,
                    $location,
                    $status,
                    $furnished,
                    $main_image
                ]);
                
                $property_id = $pdo->lastInsertId();
                
                if (!empty($_FILES['additional_images']['name'][0])) {
                    $upload_dir = 'assets/images/properties/';
                    
                    foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['additional_images']['error'][$key] == UPLOAD_ERR_OK) {
                            $file_name = time() . '_' . $key . '_' . basename($_FILES['additional_images']['name'][$key]);
                            $target_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                $stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                                $stmt->execute([$property_id, $file_name]);
                            }
                        }
                    }
                }
                
                $success = 'Property added successfully!';
                header("Location: view_property.php?id=$property_id");
                exit();
                
            } catch (PDOException $e) {
                $error = 'Error adding property: ' . $e->getMessage();
            }
        }
    }
}
?>

<section class="form-container">
    <form action="add_property.php" method="post" enctype="multipart/form-data" onsubmit="return validatePropertyForm()">
        <h3>add new property</h3>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="flex">
            <div class="box">
                <p>property title <span>*</span></p>
                <input type="text" name="title" required maxlength="100" placeholder="enter property title" class="input">
            </div>
            
            <div class="box">
                <p>property type <span>*</span></p>
                <select name="type" class="input" required>
                    <option value="house">house</option>
                    <option value="flat">flat</option>
                    <option value="shop">shop</option>
                </select>
            </div>
        </div>
        
        <div class="flex">
            <div class="box">
                <p>offer type <span>*</span></p>
                <select name="offer_type" class="input" required>
                    <option value="sale">sale</option>
                    <option value="rent">rent</option>
                </select>
            </div>
            
            <div class="box">
                <p>price <span>*</span></p>
                <input type="text" name="price" required pattern="[0-9]+([\.,][0-9]+)?" title="Enter a valid price (e.g. 250000 or 250,000.00)" placeholder="enter price" class="input" id="priceInput">
            </div>
        </div>
        
        <div class="flex">
            <div class="box">
                <p>area (sqft) <span>*</span></p>
                <input type="text" name="area" required pattern="[0-9]+([\.,][0-9]+)?" title="Enter a valid area (e.g. 1500 or 1,500.50)" placeholder="enter area in sqft" class="input" id="areaInput">
            </div>
            
            <div class="box">
                <p>bedrooms<span>*</span></p>
                <input type="text" name="bedrooms" required pattern="[0-9]+([\.,][0-9]+)?" title="Enter number of bedrooms" placeholder="enter No. of bedrooms" class="input">
            </div>
    
            <div class="box">
                <p>bathrooms<span>*</span></p>
                <input type="text" name="bathrooms" required pattern="[0-9]+([\.,][0-9]+)?" title="Enter number of bathrooms" placeholder="enter No. of bathrooms" class="input">
            </div>

            <div class="box">
                <p>location</p>
                <input type="text" name="location" placeholder="enter property location" class="input">
            </div>
        </div>
        
        <div class="flex">
            <div class="box">
                <p>status</p>
                <select name="status" class="input">
                    <option value="ready to move">ready to move</option>
                    <option value="under construction">under construction</option>
                </select>
            </div>
            
            <div class="box">
                <p>furnished</p>
                <select name="furnished" class="input">
                    <option value="furnished">furnished</option>
                    <option value="unfurnished">unfurnished</option>
                    <option value="semi-furnished">semi-furnished</option>
                </select>
            </div>
        </div>
        
        <div class="box">
            <p>main image <span>*</span></p>
            <input type="file" name="main_image" accept="image/*" required class="input">
        </div>
        
        <div class="box">
            <p>additional images (max 5)</p>
            <input type="file" name="additional_images[]" accept="image/*" multiple class="input" max="5">
        </div>
        
        <div class="box">
            <p>description</p>
            <textarea name="description" class="input" placeholder="enter property description" maxlength="1000"></textarea>
        </div>
        
        <input type="submit" value="add property" name="submit" class="btn">
    </form>
</section>

<script>
function validatePropertyForm() {
    const priceInput = document.getElementById('priceInput');
    const areaInput = document.getElementById('areaInput');
    
    // Remove commas for validation
    const price = parseFloat(priceInput.value.replace(/,/g, ''));
    const area = parseFloat(areaInput.value.replace(/,/g, ''));
    
    if (isNaN(price) || price <= 0) {
        alert('Price must be a valid positive number');
        priceInput.focus();
        return false;
    }
    
    if (isNaN(area) || area <= 0) {
        alert('Area must be a valid positive number');
        areaInput.focus();
        return false;
    }
    
    // Format the numbers with commas as thousands separators
    priceInput.value = price.toLocaleString();
    areaInput.value = area.toLocaleString();
    
    return true;
}

// Allow only numbers, comma, and dot in price and area fields
document.getElementById('priceInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9,.]/g, '');
});

document.getElementById('areaInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9,.]/g, '');
});
</script>

<?php require 'includes/footer.php'; ?>