<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

require_once '../config/db.php';  
require_once '../config/cloudinary.php';

use Cloudinary\Api\Upload\UploadApi;

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$ward = trim($_POST['ward'] ?? '');
$complaint = trim($_POST['complaint'] ?? '');
$imageUrl = null;

// Validate
if (empty($name) || empty($ward) || empty($complaint)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Handle file upload
if (!empty($_FILES['image']['tmp_name'])) {
    try {
        $upload = (new UploadApi())->upload($_FILES['image']['tmp_name']);
        $imageUrl = $upload['secure_url']; // store in DB
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Image upload failed: ' . $e->getMessage()]);
        exit;
    }
}

// Default status = pending
$status = 'pending';

// Insert complaint
$stmt = $conn->prepare("INSERT INTO complaints (name, ward, complaint, imageUrl, status) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sssss", $name, $ward, $complaint, $imageUrl, $status);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Complaint submitted',
        'complaint_id' => $stmt->insert_id,
        'imageUrl' => $imageUrl
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit complaint']);
}

$stmt->close();
$conn->close();
