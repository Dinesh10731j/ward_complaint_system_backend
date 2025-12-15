<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once "../config/headers.php";
require_once "../config/db.php";   // Database connection
require_once "../vendor/autoload.php"; // Composer autoload
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
$jwtSecretKey = $_ENV['JWT_SECRET'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

// Get JSON input
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

// Fetch user from database
$stmt = $conn->prepare("SELECT id, username, email, role, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user exists and password is correct
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    exit;
}

//default role if DB role is null
$userRole = $user['role'] ?? 'citizen';

// Generate JWT
$issuedAt   = time();
$expiration = $issuedAt + 3600; // 1 hour

$payload = [
    "iss" => "http://localhost/ward_complain/api",
    "aud" => "http://localhost/ward_complain/api",
    "iat" => $issuedAt,
    "exp" => $expiration,
    "data" => [
        "id"       => $user['id'],
        "username" => $user['username'],
        "email"    => $user['email'],
        "role"     => $userRole
    ]
];

$jwt = JWT::encode($payload, $jwtSecretKey, 'HS256');

// Return response
echo json_encode([
    'status' => 'success',
    'message' => 'Login successful',
    'token' => $jwt,
    'user'  => [
        'id'       => $user['id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $userRole
    ]
]);

$stmt->close();
$conn->close();
