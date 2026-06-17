<?php
// my_games.php - Page for users to manage their saved games
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle game deletion if requested
if (isset($_POST['delete_game']) && isset($_POST['game_id'])) {
    $game_id = $_POST['game_id'];
    
    // Verify the game belongs to the current user before deleting
    $stmt = $pdo->prepare('DELETE FROM games WHERE id = ? AND user_id = ?');
    $stmt->execute([$game_id, $user_id]);
    
    // Set a message to display
    $delete_message = 'Game deleted successfully!';
}

// Fetch all games for the current user
$stmt = $pdo->prepare('SELECT id, game_title, template_type, created_at, updated_at FROM games WHERE user_id = ? ORDER BY updated_at DESC');
$stmt->execute([$user_id]);
$games = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Games - CodePlay</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .game-card {
      transition: transform 0.3s;
      margin-bottom: 20px;
    }
    .game-card:hover {
      transform: scale(1.02);
    }
    .logo {
      height: 40px;
      width: auto;
    }
    #bg-video {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: -1;
    }
  </style>
</head>
<body>
  <video autoplay loop muted playsinline id="bg-video">
    <source src="nature.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>
  
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="CG.php">
        <img src="logo.png" alt="CodePlay Logo" class="logo me-2">
        CodePlay
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="CG.php">Game Templates</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="my_games.php">My Games</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  
  <div class="container mt-5">
    <h2 class="text-center mb-4">My Saved Games</h2>
    
    <?php if (isset($delete_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $delete_message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (empty($games)): ?>
      <div class="alert alert-info text-center">
        <p>You haven't saved any games yet. <a href="CG.php" class="alert-link">Start building a game now!</a></p>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($games as $game): ?>
          <div class="col-md-4">
            <div class="card game-card">
              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($game['game_title']); ?></h5>
                <p class="card-text">
                  <small class="text-muted">Template: <?php echo ucwords(str_replace('_', ' ', $game['template_type'])); ?></small><br>
                  <small class="text-muted">Last updated: <?php echo date('M j, Y g:i A', strtotime($game['updated_at'])); ?></small>
                </p>
                <div class="d-flex justify-content-between">
                  <a href="editor.php?game_id=<?php echo $game['id']; ?>" class="btn btn-primary">Edit</a>
                  <form method="POST" onsubmit="return confirm('Are you sure you want to delete this game?');">
                    <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                    <button type="submit" name="delete_game" class="btn btn-danger">Delete</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  
  <footer class="bg-dark text-white text-center py-3 mt-5">
    <p>© 2025 CodePlay. All Rights Reserved.</p>
  </footer>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>