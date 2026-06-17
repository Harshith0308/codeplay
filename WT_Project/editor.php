<?php
// Start session and check if user is logged in
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.html');
    exit;
}

// Initialize variables for form fields
$game_title = '';
$template_type = '';
$code = '';
$game_id = '';

// Check if a template type was passed from the template selection page
if (isset($_GET['template_type'])) {
    $template_type = $_GET['template_type'];
}

// Check if we're editing an existing game
if (isset($_GET['game_id'])) {
    $game_id = $_GET['game_id'];
    $user_id = $_SESSION['user_id'];
    
    // Fetch the game data, ensuring it belongs to the current user
    $stmt = $pdo->prepare('SELECT game_title, template_type, code FROM games WHERE id = ? AND user_id = ?');
    $stmt->execute([$game_id, $user_id]);
    $game = $stmt->fetch();
    
    if ($game) {
        // Populate form fields with existing game data
        $game_title = $game['game_title'];
        $template_type = $game['template_type'];
        $code = $game['code'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Game Editor - CodePlay</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    /* Full-screen background video */
    #bg-video {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: -1;
    }
    /* Editor container with responsive flex layout */
    .editor-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 80px;
    }
    .code-editor, .preview-area {
      flex: 1 1 45%;
      padding: 15px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 5px;
    }
    /* Consistent textarea styling */
    textarea {
      width: 100%;
      height: 400px;
      padding: 10px;
      font-family: monospace;
      border: 1px solid #ccc;
      border-radius: 5px;
      resize: vertical;
    }
    h5 {
      margin-bottom: 10px;
    }
    .btn-run {
      display: block;
      width: 100%;
      margin-top: 15px;
    }
    footer {
      margin-top: 40px;
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
        <img src="LOGO.png" alt="CodePlay Logo" class="logo me-2" style="height: 40px;">
        CodePlay
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="CG.php">Templates</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="my_games.php">My Games</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  
  <div class="container mt-4">
    <h3 class="text-center">Game Editor</h3>
    <div class="editor-container">
      <div class="code-editor">
        <h5>Write Your Code:</h5>
        <form action="run_game.php" method="POST">
          <div class="mb-3">
            <label for="game_title" class="form-label">Game Title</label>
            <input type="text" class="form-control" id="game_title" name="game_title" placeholder="Enter a title for your game" value="<?php echo htmlspecialchars($game_title); ?>" required>
          </div>
          <div class="mb-3">
            <label for="template_type" class="form-label">Template Type</label>
            <select class="form-select" id="template_type" name="template_type" required>
              <option value="">Select a template</option>
              <option value="maze_runner" <?php echo ($template_type == 'maze_runner') ? 'selected' : ''; ?>>Maze Runner</option>
              <option value="jump_adventure" <?php echo ($template_type == 'jump_adventure') ? 'selected' : ''; ?>>Jump Adventure</option>
              <option value="catch_the_fruit" <?php echo ($template_type == 'catch_the_fruit') ? 'selected' : ''; ?>>Catch the Fruit</option>
            </select>
          </div>
          <textarea name="code" id="code" placeholder="Write JavaScript/HTML code here..." required><?php echo htmlspecialchars($code); ?></textarea>
          <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
          <?php if($game_id): ?>
          <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
          <?php endif; ?>
          <div class="d-flex justify-content-between mt-3">
            <button type="submit" name="action" value="run" class="btn btn-success">Run Code</button>
            <button type="submit" name="action" value="save" class="btn btn-primary">Save Game</button>
          </div>
        </form>
      </div>
      <div class="preview-area">
        <h5>Game Preview:</h5>
        <iframe id="output"></iframe>
      </div>
    </div>
  </div>
  
  <footer class="bg-dark text-white text-center py-2">
    <p>© 2025 CodePlay. All Rights Reserved.</p>
  </footer>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
