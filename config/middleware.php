<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// Load JWT secret from .env
$jwtSecretKey = $_ENV['JWT_SECRET'];

function authenticate() {
    global $jwtSecretKey; //  $jwtSecretKey accessible inside function

    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Authorization header missing"]);
        exit;
    }

    $arr = explode(" ", $authHeader);

    if (count($arr) != 2 || $arr[0] !== "Bearer") {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid Authorization header"]);
        exit;
    }

    $jwt = $arr[1];

    try {
        $decoded = JWT::decode($jwt, new Key($jwtSecretKey, 'HS256'));
        return (array) $decoded; // return as array

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
        exit;
    }
}
