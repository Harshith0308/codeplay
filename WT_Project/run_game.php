<?php
// run_game.php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    $action = isset($_POST['action']) ? $_POST['action'] : 'run';
    
    // If the action is to save the game
    if ($action === 'save' && isset($_POST['game_title']) && isset($_POST['template_type'])) {
        $user_id = $_SESSION['user_id'];
        $game_title = trim($_POST['game_title']);
        $template_type = $_POST['template_type'];
        
        // Check if we're editing an existing game (game_id is provided)
        if (isset($_POST['game_id']) && !empty($_POST['game_id'])) {
            $game_id = $_POST['game_id'];
            
            // Update the existing game, ensuring it belongs to the current user
            $stmt = $pdo->prepare('UPDATE games SET game_title = ?, code = ?, template_type = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$game_title, $code, $template_type, $game_id, $user_id]);
            $message = 'Game updated successfully!';
        } else {
            // Check if a game with this title already exists for this user
            $stmt = $pdo->prepare('SELECT id FROM games WHERE user_id = ? AND game_title = ?');
            $stmt->execute([$user_id, $game_title]);
            $existing_game = $stmt->fetch();
            
            if ($existing_game) {
                // Update existing game
                $stmt = $pdo->prepare('UPDATE games SET code = ?, template_type = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$code, $template_type, $existing_game['id']]);
                $message = 'Game updated successfully!';
            } else {
                // Insert new game
                $stmt = $pdo->prepare('INSERT INTO games (user_id, game_title, template_type, code, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
                $stmt->execute([$user_id, $game_title, $template_type, $code]);
                $message = 'Game saved successfully!';
            }
        }
    }
} else {
    $code = '<p>No code provided.</p>';
}

// Check if we have a success message to display
$display_message = isset($message) ? $message : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Display success message if available -->
    <?php if (!empty($display_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $display_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Display the user-provided game code -->
    <div id="game-container">
        <?php echo $code; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
