<?php
require_once "../config/headers.php";
require_once "../config/db.php";

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Ensure DB connection
if (!isset($conn)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection not established'
    ]);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

// Get inputs
$username = trim($data['username'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm  = $data['confirm_password'] ?? '';

// Validate inputs
if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email address'
    ]);
    exit;
}

if ($password !== $confirm) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Passwords do not match'
    ]);
    exit;
}

// Check if user already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$check->bind_param("ss", $email, $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    http_response_code(409);
    echo json_encode([
        'status' => 'error',
        'message' => 'User already exists'
    ]);
    exit;
}
$check->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$stmt = $conn->prepare(
    "INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES (?, ?, ?, 'citizen')"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'User registered successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed',
        'error' => $stmt->error ?: $conn->error
    ]);
}

$stmt->close();
$conn->close();
