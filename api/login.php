<?php
header("Content-Type: application/json");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require '../vendor/autoload.php'; // JWT library
require_once '../config/db.php';  // $conn is the MySQLi connection object
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
$jwtSecretKey = $_ENV['JWT_SECRET'];
$secret_key = $jwtSecretKey;  

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

$email = strtolower(trim($data['email'] ?? ''));
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    exit;
}

// ✅ Generate JWT Token
$issuedAt   = time();
$expiration = $issuedAt + (60 * 60); // 1 hour expiration

$payload = [
    "iss" => "http://localhost/ward_complain/api", // Issuer
    "aud" => "http://localhost/ward_complain/api", // Audience
    "iat" => $issuedAt,              
    "exp" => $expiration,           
    "data" => [
        "id"       => $user['id'],
        "username" => $user['username'],
        "email"    => $user['email']
    ]
];

$jwt = JWT::encode($payload, $secret_key, 'HS256');

// ✅ Send token in response
echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'token' => $jwt
]);

$stmt->close();
$conn->close();
