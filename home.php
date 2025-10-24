<?php
session_start();
require 'includes/db.php';
require 'includes/header.php';
?>

<!-- home section starts  -->
<div class="home">
   <section class="center">
      <div class="welcome-message">
         <h3 class="welcome-title">Your Dream Home Awaits</h3>
         <p class="welcome-text">
            <span class="highlight">Welcome to Kenya's premier real estate platform</span> where we turn your property dreams into reality. 
            Whether you're searching for a <span class="highlight">luxurious villa</span>, a <span class="highlight">cozy apartment</span>, 
            or prime <span class="highlight">commercial space</span>, we've curated the finest selection of properties across the country. 
            Our team of experts ensures every listing meets the highest standards of quality and value.
         </p>
         <p class="welcome-text">
            Start your journey today and discover properties that match your lifestyle and aspirations. 
            From <span class="highlight">breathtaking views</span> to <span class="highlight">prime locations</span>, 
            we have something for everyone.
         </p>
         <a href="listings.php" class="btn explore-btn">Explore Our Properties</a>
      </div>
   </section>
</div>

<!-- services section starts  -->
<section class="services">
   <h1 class="heading">our services</h1>
   <div class="box-container">
      <div class="box">
         <img src="assets/images/icon-1.png" alt="">
         <h3>buy house</h3>
         <p>Discover your dream home from our selection of premium properties across the city's most desirable neighborhoods.</p>
      </div>
      <div class="box">
         <img src="assets/images/icon-2.png" alt="">
         <h3>rent house</h3>
         <p>Find your perfect rental with flexible terms, modern amenities, and locations that put you where you want to be.</p>
      </div>
      <div class="box">
         <img src="assets/images/icon-3.png" alt="">
         <h3>sell house</h3>
         <p>Maximize your property's value with our expert marketing strategies and extensive buyer network for faster sales.</p>
      </div>
      <div class="box">
         <img src="assets/images/icon-4.png" alt="">
         <h3>flats and buildings</h3>
         <p>Explore contemporary apartments and commercial spaces designed for modern living and business success.</p>
      </div>
      <div class="box">
         <img src="assets/images/icon-5.png" alt="">
         <h3>shops and malls</h3>
         <p>Prime retail spaces in high-traffic locations to grow your business and reach your ideal customers.</p>
      </div>
      <div class="box">
         <img src="assets/images/icon-6.png" alt="">
         <h3>24/7 service</h3>
         <p>Dedicated support around the clock to answer your questions and assist with all your real estate needs.</p>
      </div>
   </div>
</section>

<!-- listings section starts  -->
<section class="listings">
   <h1 class="heading">featured properties</h1>
   <div class="box-container">
      <?php
      // Fetch latest 6 properties without approval check
      $stmt = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC LIMIT 6");
      while ($property = $stmt->fetch()):
      ?>
      <div class="box">
         <div class="admin">
            <?php 
            $owner_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $owner_stmt->execute([$property['user_id']]);
            $owner = $owner_stmt->fetch();
            $initials = strtoupper(substr($owner['name'], 0, 1));
            ?>
            <h3><?php echo $initials; ?></h3>
            <div>
               <p><?php echo htmlspecialchars($owner['name']); ?></p>
               <span><?php echo date('d-m-Y', strtotime($property['created_at'])); ?></span>
            </div>
         </div>
         <div class="thumb">
            <p class="total-images"><i class="far fa-image"></i><span><?php 
               $img_stmt = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ?");
               $img_stmt->execute([$property['id']]);
               echo $img_stmt->fetchColumn();
            ?></span></p>
            <p class="type"><span><?php echo htmlspecialchars($property['type']); ?></span><span><?php echo htmlspecialchars($property['offer_type']); ?></span></p>
            <img src="assets/images/properties/<?php echo htmlspecialchars($property['main_image']); ?>" alt="">
         </div>
         <h3 class="name"><?php echo htmlspecialchars($property['title']); ?></h3>
         <p class="location"><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($property['location']); ?></span></p>
         <div class="flex">
            <p><i class="fas fa-bed"></i><span><?php echo $property['bedrooms']; ?></span></p>
            <p><i class="fas fa-bath"></i><span><?php echo $property['bathrooms']; ?></span></p>
            <p><i class="fas fa-maximize"></i><span><?php echo $property['area']; ?>sqft</span></p>
         </div>
         <div class="price">Ksh <?php echo number_format($property['price']); ?></div>
         <a href="view_property.php?id=<?= $property['id'] ?>" class="btn">view details</a>
      </div>
      <?php endwhile; ?>
   </div>
   <div style="margin-top: 2rem; text-align:center;">
      <a href="listings.php" class="inline-btn">view all properties</a>
   </div>
</section>

<?php require 'includes/footer.php'; ?>
