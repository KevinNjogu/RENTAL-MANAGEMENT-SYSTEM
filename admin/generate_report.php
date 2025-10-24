<?php
require('../includes/admin_auth.php');
require('../includes/db.php');

// Set headers for download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="property_report_'.date('Y-m-d').'.xls"');

// Get all properties data
$stmt = $pdo->query("SELECT 
                    p.id, p.title, p.price, p.location, p.type, 
                    p.bedrooms, p.bathrooms, p.area, p.featured,
                    u.name AS owner_name, u.email AS owner_email,
                    p.created_at
                    FROM properties p
                    JOIN users u ON p.user_id = u.id
                    ORDER BY p.created_at DESC");
$properties = $stmt->fetchAll();

// Get all users data
$users_stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $users_stmt->fetchAll();

// Start Excel file content
echo "<html>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
echo "<table border=\"1\">";
echo "<caption>PROPERTY REPORT - ".date('F j, Y')."</caption>";

// Properties Sheet
echo "<tr><th colspan=\"11\" style=\"background-color: #4e73df; color: white;\">PROPERTIES</th></tr>";
echo "<tr>
        <th>ID</th>
        <th>Title</th>
        <th>Price</th>
        <th>Location</th>
        <th>Type</th>
        <th>Bedrooms</th>
        <th>Bathrooms</th>
        <th>Area (sqft)</th>
        <th>Featured</th>
        <th>Owner</th>
        <th>Date Added</th>
      </tr>";

foreach ($properties as $property) {
    echo "<tr>";
    echo "<td>".$property['id']."</td>";
    echo "<td>".htmlspecialchars($property['title'])."</td>";
    echo "<td>".number_format($property['price'])."</td>";
    echo "<td>".htmlspecialchars($property['location'])."</td>";
    echo "<td>".htmlspecialchars($property['type'])."</td>";
    echo "<td>".$property['bedrooms']."</td>";
    echo "<td>".$property['bathrooms']."</td>";
    echo "<td>".$property['area']."</td>";
    echo "<td>".($property['featured'] ? 'Yes' : 'No')."</td>";
    echo "<td>".htmlspecialchars($property['owner_name'])." (".htmlspecialchars($property['owner_email']).")</td>";
    echo "<td>".date('M j, Y', strtotime($property['created_at']))."</td>";
    echo "</tr>";
}

// Users Sheet
echo "<tr><th colspan=\"5\" style=\"background-color: #1cc88a; color: white; margin-top: 20px;\">USERS</th></tr>";
echo "<tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Date Joined</th>
      </tr>";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>".$user['id']."</td>";
    echo "<td>".htmlspecialchars($user['name'])."</td>";
    echo "<td>".htmlspecialchars($user['email'])."</td>";
    echo "<td>".ucfirst($user['role'])."</td>";
    echo "<td>".date('M j, Y', strtotime($user['created_at']))."</td>";
    echo "</tr>";
}

echo "</table>";
echo "</html>";
exit();