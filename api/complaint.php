<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/db.php';  
require_once '../config/cloudinary.php';

use Cloudinary\Api\Upload\UploadApi;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed']);
    exit;
}

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection not established']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$ward = trim($_POST['ward'] ?? '');
$complaint = trim($_POST['complaint'] ?? '');
$imageUrl = null;

// Validate required fields
if (empty($name) || empty($ward) || empty($complaint)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Handle image upload to Cloudinary
if (!empty($_FILES['image']['tmp_name'])) {
    try {
        $upload = (new UploadApi())->upload($_FILES['image']['tmp_name']);
        $imageUrl = $upload['secure_url'];
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Image upload failed: ' . $e->getMessage()]);
        exit;
    }
}

// Default status
$status = 'pending';

// Insert into database
$stmt = $conn->prepare("INSERT INTO complaints (name, ward, complaint, imageUrl, status) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $ward, $complaint, $imageUrl, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Complaint submitted successfully',
        'imageUrl' => $imageUrl
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit complaint']);
}

$stmt->close();
$conn->close();
