<?php
session_start();
include'config.php'; // Connect to the database

// Admin details to be added
$username = "Admin"; 
$password = "Admin123"; 

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into the admins table
$stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hashed_password);

if ($stmt->execute()) {
    echo "Admin added successfully!";
} else {
    echo "Error adding admin: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
