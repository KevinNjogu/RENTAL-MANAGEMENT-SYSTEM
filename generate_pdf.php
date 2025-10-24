<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if PDO is available
if (!extension_loaded('pdo')) {
    die("PDO extension is not loaded. Please enable it in your PHP configuration.");
}

// Load DomPDF library
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Validate property_id
if (empty($_GET['property_id'])) {
    die("No property specified. Example: generate_pdf.php?property_id=123");
}

$propertyId = filter_input(INPUT_GET, 'property_id', FILTER_VALIDATE_INT);

if (!$propertyId || $propertyId < 1) {
    die("Invalid Property ID. Must be a positive number.");
}

// Database connection
$host = 'localhost';
$dbname = 'myhome_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode using string if constant not available
    $pdo->setAttribute(PDO::ATTR_ERRMODE, 2); // 2 = PDO::ERRMODE_EXCEPTION

    // 2. Fetch property data
    $stmt = $pdo->prepare("
        SELECT p.title, p.location, p.price, p.description, 
               u.name as agent_name, u.phone as agent_phone
        FROM properties p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        die("Property not found");
    }

    // Price formatting function
    function formatPrice($price) {
        return 'KSh ' . number_format($price, 2);
    }

    // 3. Generate HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($property['title']) . ' - Property Details</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
            h1 { color: #2a6496; border-bottom: 2px solid #2a6496; padding-bottom: 10px; }
            .price { font-size: 24px; color: #4CAF50; font-weight: bold; }
            .section { margin-bottom: 20px; }
            .agent-info { background: #f5f5f5; padding: 15px; border-radius: 5px; }
            .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <h1>' . htmlspecialchars($property['title']) . '</h1>
        
        <div class="section">
            <p><strong>Location:</strong> ' . htmlspecialchars($property['location']) . '</p>
            <p class="price">' . formatPrice($property['price']) . ' per month</p>
        </div>
        
        <div class="section">
            <h3>Description</h3>
            <p>' . nl2br(htmlspecialchars($property['description'] ?? 'No description available')) . '</p>
        </div>';
        
    // Add agent info if available
    if (!empty($property['agent_name'])) {
        $html .= '
        <div class="section agent-info">
            <h3>Contact Agent</h3>
            <p><strong>Name:</strong> ' . htmlspecialchars($property['agent_name']) . '</p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($property['agent_phone']) . '</p>
        </div>';
    }
    
    $html .= '
        <div class="footer">
            <p>Generated on ' . date('F j, Y') . ' by MyHome Properties</p>
        </div>
    </body>
    </html>';

    // 4. Configure and generate PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 5. Output PDF
    $dompdf->stream("property_{$propertyId}_details.pdf", [
        "Attachment" => true,
        "compress" => true
    ]);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("PDF generation failed: " . $e->getMessage());
}
?>