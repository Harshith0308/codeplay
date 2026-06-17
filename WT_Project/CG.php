<?php
// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CodePlay - Build Your Game</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .game-card {
      transition: transform 0.3s;
      margin-bottom: 30px;
    }
    .game-card:hover {
      transform: scale(1.05);
    }
    .logo {
      height: 40px;
      width: auto;
    }
    /* Set a fixed height for the images and ensure they cover the container */
    .card-img-top {
      height: 250px; /* Adjust height as needed */
      object-fit: cover;
    }
    /* Optional: Limit card title height for consistency */
    .card-title {
      min-height: 3rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="CG.html">
        <img src="logo.png" alt="CodePlay Logo" class="logo me-2">
        CodePlay
      </a>
    </div>
  </nav>
  
  <div class="container mt-5">
    <h2 class="text-center mb-4">Choose Your Game Template</h2>
    <div class="row">
      <!-- Maze Runner Template -->
      <div class="col-md-4">
        <div class="card game-card">
          <img src="Maze_Runner.jpg" class="card-img-top" alt="Maze Runner">
          <div class="card-body text-center">
            <h5 class="card-title">Maze Runner</h5>
            <a href="editor.php?template_type=maze_runner" class="btn btn-primary">Start Building</a>
          </div>
        </div>
      </div>
      <!-- Jump Adventure Template -->
      <div class="col-md-4">
        <div class="card game-card">
          <img src="Jump_Adventure.jpg" class="card-img-top" alt="Jump Adventure">
          <div class="card-body text-center">
            <h5 class="card-title">Jump Adventure</h5>
            <a href="editor.php?template_type=jump_adventure" class="btn btn-primary">Start Building</a>
          </div>
        </div>
      </div>
      <!-- Catch the Fruit Template -->
      <div class="col-md-4">
        <div class="card game-card">
          <img src="Catch_the_Fruit.jpg" class="card-img-top" alt="Catch the Fruit">
          <div class="card-body text-center">
            <h5 class="card-title">Catch the Fruit</h5>
            <a href="editor.php?template_type=catch_the_fruit" class="btn btn-primary">Start Building</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <footer class="bg-dark text-white text-center py-3 mt-5">
    <p>© 2025 CodePlay. All Rights Reserved.</p>
  </footer>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
