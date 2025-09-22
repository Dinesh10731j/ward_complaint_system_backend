<?php
// backend/db.php

$host = 'localhost';     
$dbname = 'ward_complain';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Confirm connection
echo "Connected successfully";
?>
