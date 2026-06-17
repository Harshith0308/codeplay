<?php
// login.php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        die('Please fill all required fields.');
    }
    
    // Retrieve the user record from the database
    $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Verify the password using password_verify()
    if ($user && password_verify($password, $user['password'])) {
        // Login successful: save user details in session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        
        // Redirect to the main game template page
        header('Location: CG.php');
        exit;
    } else {
        die('Invalid email or password.');
    }
} else {
    die('Invalid request method.');
}
?>
