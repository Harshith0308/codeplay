<?php
// signup.php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Check if any field is empty
    if (empty($name) || empty($email) || empty($password)) {
        die('Please fill all required fields.');
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die('A user with this email already exists.');
    }
    
    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert the new user into the database
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    if ($stmt->execute([$name, $email, $hashed_password])) {
        // Redirect to the login page after successful signup
        header('Location: login.html');
        exit;
    } else {
        die('Error occurred. Please try again.');
    }
} else {
    die('Invalid request method.');
}
?>
