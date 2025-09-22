<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // allow all origins
header("Access-Control-Allow-Methods:POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$message = trim($data['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Name, email, and message are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

// ✅ Check if email already exists
$check = $conn->prepare("SELECT id FROM contact WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'This email already exists in our system']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Insert new contact record
$stmt = $conn->prepare("INSERT INTO contact (name, email,  message) VALUES (?, ?, ?)");
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sss", $name, $email, $message);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Message sent successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
}

$stmt->close();
$conn->close();
?>
