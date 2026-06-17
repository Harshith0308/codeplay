<?php
// index.php - Single file web application for CodePlay
session_start();
require_once 'config.php';

// Define the current page/view based on URL parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Authentication check for protected pages
$protected_pages = ['dashboard', 'editor', 'my_games', 'run_game'];
if (in_array($page, $protected_pages) && !isset($_SESSION['user_id'])) {
    $page = 'login';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch($page) {
        case 'login':
            handleLogin();
            break;
        case 'signup':
            handleSignup();
            break;
        case 'editor':
            handleEditorSubmission();
            break;
        case 'delete_game':
            handleGameDeletion();
            $page = 'my_games';
            break;
    }
}

// Common header HTML
function outputHeader($title) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>' . $title . ' - CodePlay</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body {
          background-color: #f8f9fa;
          font-family: \'Helvetica Neue\', Arial, sans-serif;
          min-height: 100vh;
          margin: 0;
          display: flex;
          flex-direction: column;
        }
        .navbar {
          width: 100%;
        }
        .logo {
          height: 40px;
          width: auto;
        }
        .footer {
          margin-top: auto;
          width: 100%;
        }
        .game-card {
          transition: transform 0.3s;
          margin-bottom: 20px;
        }
        .game-card:hover {
          transform: scale(1.05);
        }
        .card-img-top {
          height: 250px;
          object-fit: cover;
        }
        .form-container {
          max-width: 400px;
          background: #ffffff;
          padding: 30px;
          border-radius: 10px;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
          margin: 100px auto 0;
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
    <body>';
    
    if ($title == 'Login' || $title == 'Sign Up') {
        echo '<video autoplay loop muted playsinline id="bg-video">
        <source src="cyberpunk-2077-nighttime-metropolis.1920x1080.mp4" type="video/mp4">
        Your browser does not support the video tag.
      </video>';
    }
    
    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
          <img src="LOGO.png" alt="CodePlay Logo" class="logo me-2">
          CodePlay
        </a>';
        
    if (isset($_SESSION['user_id'])) {
        echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="index.php?page=dashboard">Game Templates</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="index.php?page=my_games">My Games</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="index.php?page=logout">Logout</a>
            </li>
          </ul>
        </div>';
    }
    
    echo '</div>
    </nav>';
}

// Common footer HTML
function outputFooter() {
    echo '<footer class="footer bg-dark text-white text-center py-2">
      <p>© 2025 CodePlay. All Rights Reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}

// Handle login form submission
function handleLogin() {
    global $pdo;
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill all required fields.';
        return;
    }
    
    $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

// Handle signup form submission
function handleSignup() {
    global $pdo;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields.';
        return;
    }
    
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $error = 'A user with this email already exists.';
        return;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    if ($stmt->execute([$name, $email, $hashed_password])) {
        header('Location: index.php?page=login&signup=success');
        exit;
    } else {
        $error = 'Error occurred. Please try again.';
    }
}

// Handle game editor form submission
function handleEditorSubmission() {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $game_title = trim($_POST['game_title']);
    $template_type = $_POST['template_type'];
    $code = $_POST['code'];
    $game_id = isset($_POST['game_id']) ? $_POST['game_id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : 'save';
    
    if (empty($game_title) || empty($template_type) || empty($code)) {
        $error = 'Please fill all required fields.';
        return;
    }
    
    if ($action === 'run') {
        return;
    }
    
    if ($game_id) {
        $stmt = $pdo->prepare('UPDATE games SET game_title = ?, template_type = ?, code = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
        $result = $stmt->execute([$game_title, $template_type, $code, $game_id, $user_id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO games (user_id, game_title, template_type, code, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $result = $stmt->execute([$user_id, $game_title, $template_type, $code]);
        $game_id = $pdo->lastInsertId();
    }
    
    if ($result) {
        header('Location: index.php?page=my_games&saved=success');
        exit;
    } else {
        $error = 'Error saving game. Please try again.';
    }
}

// Handle game deletion
function handleGameDeletion() {
    global $pdo;
    if (isset($_POST['game_id'])) {
        $game_id = $_POST['game_id'];
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare('DELETE FROM games WHERE id = ? AND user_id = ?');
        $stmt->execute([$game_id, $user_id]);
    }
}

// Render the appropriate page content
switch($page) {
    case 'home':
    case 'login':
        renderLoginPage();
        break;
    case 'signup':
        renderSignupPage();
        break;
    case 'dashboard':
        renderDashboardPage();
        break;
    case 'editor':
        renderEditorPage();
        break;
    case 'my_games':
        renderMyGamesPage();
        break;
    case 'run_game':
        renderRunGamePage();
        break;
    case 'logout':
        session_destroy();
        header('Location: index.php?page=login');
        exit;
// Remove unreachable break statement since it follows a header redirect and exit
    default:
        header('Location: index.php');
        exit;
}

function renderLoginPage() {
    global $error;
    outputHeader('Login');
    
    echo '<div class="form-container">
        <h3 class="text-center mb-4">Login</h3>';
        
    if (isset($_GET['signup']) && $_GET['signup'] == 'success') {
        echo '<div class="alert alert-success">Account created successfully! Please login.</div>';
    }
    
    if (isset($error)) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    }
    
    echo '<form id="loginForm" action="index.php?page=login" method="POST">
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" minlength="8" required title="Password must be at least 8 characters long.">
            <div class="form-text">Minimum 8 characters</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
          <p class="text-center mt-3">Don\'t have an account? <a href="index.php?page=signup">Sign up</a></p>
        </form>
      </div>';
    
    outputFooter();
}

function renderSignupPage() {
    global $error;
    outputHeader('Sign Up');
    
    echo '<div class="form-container">
        <h3 class="text-center mb-4">Sign Up</h3>';
    
    if (isset($error)) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    }
    
    echo '<form id="signupForm" action="index.php?page=signup" method="POST">
          <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" minlength="8" required title="Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers.">
            <div class="form-text">Minimum 8 characters with at least one uppercase, one lowercase, and one number.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Sign Up</button>
          <p class="text-center mt-3">Already have an account? <a href="index.php?page=login">Login</a></p>
        </form>
      </div>';
    
    outputFooter();
}

function renderDashboardPage() {
    outputHeader('Game Templates');
    
    echo '<div class="container py-5">
        <h2 class="text-center mb-4">Choose a Game Template</h2>
        <div class="row">
          <div class="col-md-4">
            <div class="card game-card">
              <img src="Catch_the_Fruit.jpg" class="card-img-top" alt="Catch the Fruit Game">
              <div class="card-body text-center">
                <h5 class="card-title">Catch the Fruit</h5>
                <p class="card-text">Create a game where players catch falling fruits in a basket.</p>
                <a href="index.php?page=editor&template_type=catch_fruit" class="btn btn-primary">Start Coding</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card game-card">
              <img src="Maze_Runner.jpg" class="card-img-top" alt="Maze Runner Game">
              <div class="card-body text-center">
                <h5 class="card-title">Maze Runner</h5>
                <p class="card-text">Build a maze game where players navigate through obstacles.</p>
                <a href="index.php?page=editor&template_type=maze_runner" class="btn btn-primary">Start Coding</a>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card game-card">
              <img src="Jump_Adventure.jpg" class="card-img-top" alt="Jump Adventure Game">
              <div class="card-body text-center">
                <h5 class="card-title">Jump Adventure</h5>
                <p class="card-text">Design a platform jumping game with various challenges.</p>
                <a href="index.php?page=editor&template_type=jump_adventure" class="btn btn-primary">Start Coding</a>
              </div>
            </div>
          </div>
        </div>
      </div>';
    
    outputFooter();
}

function renderEditorPage() {
    global $pdo, $error;
    
    $game_title = '';
    $template_type = '';
    $code = '';
    $game_id = '';
    
    if (isset($_GET['template_type'])) {
        $template_type = $_GET['template_type'];
        
        switch($template_type) {
            case 'catch_fruit':
                $code = '// Catch the Fruit Game Template
const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

let score = 0;
let lives = 5; // Increased from 3 to 5
let gameOver = false;

const basket = {
  x: canvas.width / 2 - 75,
  y: canvas.height - 50,
  width: 150, // Increased from 100 to 150
  height: 30,
  speed: 12 // Increased from 8 to 12
};

let fruits = [];

function initFruits() {
  for (let i = 0; i < 2; i++) { // Reduced from 3 to 2
    addFruit();
  }
}

function addFruit() {
  const fruitTypes = ["🍎", "🍌", "🍇", "🍊", "🍓"];
  const fruit = {
    x: Math.random() * (canvas.width - 30),
    y: -30,
    width: 30,
    height: 30,
    speed: 1 + Math.random() * 2, // Reduced from 2 + Math.random() * 3
    type: fruitTypes[Math.floor(Math.random() * fruitTypes.length)]
  };
  fruits.push(fruit);
}

function drawBasket() {
  ctx.fillStyle = "brown";
  ctx.fillRect(basket.x, basket.y, basket.width, basket.height);
  
  if (window.gameInput && window.gameInput.keys) {
    if (window.gameInput.isKeyPressed("ArrowLeft") || window.gameInput.isKeyPressed("a")) {
      basket.x -= basket.speed;
    }
    if (window.gameInput.isKeyPressed("ArrowRight") || window.gameInput.isKeyPressed("d")) {
      basket.x += basket.speed;
    }
  }
  
  if (basket.x < 0) basket.x = 0;
  if (basket.x > canvas.width - basket.width) basket.x = canvas.width - basket.width;
}

function updateFruits() {
  if (Math.random() < 0.01) { // Reduced from 0.02
    addFruit();
  }
  
  for (let i = fruits.length - 1; i >= 0; i--) {
    const fruit = fruits[i];
    fruit.y += fruit.speed;
    
    if (fruit.y + fruit.height > basket.y && 
        fruit.x + fruit.width > basket.x && 
        fruit.x < basket.x + basket.width) {
      fruits.splice(i, 1);
      score += 10;
      continue;
    }
    
    if (fruit.y > canvas.height) {
      fruits.splice(i, 1);
      lives--;
      if (lives <= 0) {
        gameOver = true;
      }
    }
  }
}

function drawFruits() {
  fruits.forEach(fruit => {
    ctx.font = "30px Arial";
    ctx.fillText(fruit.type, fruit.x, fruit.y);
  });
}

function drawScore() {
  ctx.fillStyle = "black";
  ctx.font = "20px Arial";
  ctx.textAlign = "left";
  ctx.fillText(`Score: ${score}`, 10, 30);
}

function drawLives() {
  ctx.fillStyle = "black";
  ctx.font = "20px Arial";
  ctx.textAlign = "right";
  ctx.fillText(`Lives: ${lives}`, canvas.width - 10, 30);
}

function drawGameOver() {
  ctx.fillStyle = "rgba(0, 0, 0, 0.7)";
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = "white";
  ctx.font = "40px Arial";
  ctx.textAlign = "center";
  ctx.fillText("Game Over", canvas.width / 2, canvas.height / 2);
  ctx.font = "20px Arial";
  ctx.fillText(`Final Score: ${score}`, canvas.width / 2, canvas.height / 2 + 40);
}

initFruits();

function gameLoop() {
  if (!gameOver) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawBasket();
    updateFruits();
    drawFruits();
    drawScore();
    drawLives();
    requestAnimationFrame(gameLoop);
  } else {
    drawGameOver();
  }
}

gameLoop();';
                break;
            case 'maze_runner':
                $code = '// Maze Runner Game Template
const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

let level = 1;
let gameOver = false;
let gameWon = false;

const player = {
  x: 50,
  y: 50,
  width: 20,
  height: 20,
  speed: 5
};

let walls = [];

const goal = {
  x: canvas.width - 70,
  y: canvas.height - 70,
  width: 30,
  height: 30
};

function initMaze() {
  walls = [
    { x: 0, y: 0, width: canvas.width, height: 10 },
    { x: 0, y: 0, width: 10, height: canvas.height },
    { x: 0, y: canvas.height - 10, width: canvas.width, height: 10 },
    { x: canvas.width - 10, y: 0, width: 10, height: canvas.height },
    { x: 100, y: 50, width: 10, height: 200 },
    { x: 200, y: 100, width: 200, height: 10 },
    { x: 300, y: 200, width: 10, height: 150 },
    { x: 100, y: 300, width: 150, height: 10 },
    { x: 400, y: 50, width: 10, height: 100 }
  ];
}

function drawPlayer() {
  ctx.fillStyle = "blue";
  ctx.fillRect(player.x, player.y, player.width, player.height);
  
  if (window.gameInput && window.gameInput.keys) {
    if (window.gameInput.isKeyPressed("ArrowLeft") || window.gameInput.isKeyPressed("a")) {
      player.x -= player.speed;
    }
    if (window.gameInput.isKeyPressed("ArrowRight") || window.gameInput.isKeyPressed("d")) {
      player.x += player.speed;
    }
    if (window.gameInput.isKeyPressed("ArrowUp") || window.gameInput.isKeyPressed("w")) {
      player.y -= player.speed;
    }
    if (window.gameInput.isKeyPressed("ArrowDown") || window.gameInput.isKeyPressed("s")) {
      player.y += player.speed;
    }
  }
}

function drawWalls() {
  ctx.fillStyle = "#333";
  walls.forEach(wall => {
    ctx.fillRect(wall.x, wall.y, wall.width, wall.height);
  });
}

function drawGoal() {
  ctx.fillStyle = "green";
  ctx.fillRect(goal.x, goal.y, goal.width, goal.height);
}

function checkCollisions() {
  if (player.x < 0) player.x = 0;
  if (player.y < 0) player.y = 0;
  if (player.x > canvas.width - player.width) player.x = canvas.width - player.width;
  if (player.y > canvas.height - player.height) player.y = canvas.height - player.height;
  
  walls.forEach(wall => {
    if (player.x < wall.x + wall.width &&
        player.x + player.width > wall.x &&
        player.y < wall.y + wall.height &&
        player.y + player.height > wall.y) {
      const overlapLeft = player.x + player.width - wall.x;
      const overlapRight = wall.x + wall.width - player.x;
      const overlapTop = player.y + player.height - wall.y;
      const overlapBottom = wall.y + wall.height - player.y;
      
      const minOverlap = Math.min(overlapLeft, overlapRight, overlapTop, overlapBottom);
      
      if (minOverlap === overlapLeft) {
        player.x = wall.x - player.width;
      } else if (minOverlap === overlapRight) {
        player.x = wall.x + wall.width;
      } else if (minOverlap === overlapTop) {
        player.y = wall.y - player.height;
      } else if (minOverlap === overlapBottom) {
        player.y = wall.y + wall.height;
      }
    }
  });
}

function checkWinCondition() {
  if (player.x < goal.x + goal.width &&
      player.x + player.width > goal.x &&
      player.y < goal.y + goal.height &&
      player.y + player.height > goal.y) {
    gameWon = true;
  }
}

function drawWinScreen() {
  ctx.fillStyle = "rgba(0, 0, 0, 0.7)";
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = "gold";
  ctx.font = "40px Arial";
  ctx.textAlign = "center";
  ctx.fillText("Level Complete!", canvas.width / 2, canvas.height / 2);
  ctx.font = "20px Arial";
  ctx.fillText("You reached the goal!", canvas.width / 2, canvas.height / 2 + 40);
}

function drawGameOver() {
  ctx.fillStyle = "rgba(0, 0, 0, 0.7)";
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = "red";
  ctx.font = "40px Arial";
  ctx.textAlign = "center";
  ctx.fillText("Game Over", canvas.width / 2, canvas.height / 2);
}

initMaze();

function gameLoop() {
  if (!gameOver && !gameWon) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawPlayer();
    drawWalls();
    drawGoal();
    checkCollisions();
    checkWinCondition();
    requestAnimationFrame(gameLoop);
  } else if (gameWon) {
    drawWinScreen();
  } else {
    drawGameOver();
  }
}

gameLoop();';
                break;
            case 'jump_adventure':
                $code = '// Jump Adventure Game Template
const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

let score = 0;
let gameOver = false;
let isJumping = false;
let jumpHeight = 0;
let gravity = 0.5;

const player = {
  x: 50,
  y: canvas.height - 100,
  width: 30,
  height: 50,
  speed: 6,
  jumpStrength: 15
};

let platforms = [];
let collectibles = [];

function initGame() {
  platforms = [
    { x: 0, y: canvas.height - 50, width: canvas.width, height: 20 },
    { x: 100, y: canvas.height - 120, width: 100, height: 20 },
    { x: 300, y: canvas.height - 180, width: 100, height: 20 },
    { x: 150, y: canvas.height - 250, width: 100, height: 20 },
    { x: 400, y: canvas.height - 300, width: 100, height: 20 }
  ];
  
  collectibles = [
    { x: 130, y: canvas.height - 150, width: 20, height: 20, value: 10 },
    { x: 350, y: canvas.height - 210, width: 20, height: 20, value: 10 },
    { x: 180, y: canvas.height - 280, width: 20, height: 20, value: 20 },
    { x: 430, y: canvas.height - 330, width: 20, height: 20, value: 30 }
  ];
}

function updatePlayer() {
  if (window.gameInput && window.gameInput.keys) {
    if (window.gameInput.isKeyPressed("ArrowLeft") || window.gameInput.isKeyPressed("a")) {
      player.x -= player.speed;
    }
    if (window.gameInput.isKeyPressed("ArrowRight") || window.gameInput.isKeyPressed("d")) {
      player.x += player.speed;
    }
    if ((window.gameInput.isKeyPressed("ArrowUp") || window.gameInput.isKeyPressed("w") || 
         window.gameInput.isKeyPressed(" ")) && !isJumping) {
      isJumping = true;
      jumpHeight = player.jumpStrength;
    }
  }
  
  let onPlatform = false;
  
  if (isJumping) {
    player.y -= jumpHeight;
    jumpHeight -= gravity;
    
    onPlatform = false;
    platforms.forEach(platform => {
      if (player.x + player.width > platform.x && 
          player.x < platform.x + platform.width &&
          player.y + player.height >= platform.y &&
          player.y + player.height <= platform.y + 10) {
        isJumping = false;
        player.y = platform.y - player.height;
        onPlatform = true;
      }
    });
    
    if (jumpHeight <= 0 && !onPlatform) {
      isJumping = true;
    }
  } else {
    onPlatform = false;
    platforms.forEach(platform => {
      if (player.x + player.width > platform.x && 
          player.x < platform.x + platform.width &&
          player.y + player.height >= platform.y &&
          player.y + player.height <= platform.y + 10) {
        onPlatform = true;
      }
    });
    
    if (!onPlatform) {
      isJumping = true;
      jumpHeight = 0;
    }
  }
  
  if (player.x < 0) player.x = 0;
  if (player.x > canvas.width - player.width) player.x = canvas.width - player.width;
  
  if (player.y > canvas.height) {
    gameOver = true;
  }
}

function drawPlayer() {
  ctx.fillStyle = "purple";
  ctx.fillRect(player.x, player.y, player.width, player.height);
}

function updatePlatforms() {}

function drawPlatforms() {
  ctx.fillStyle = "#333";
  platforms.forEach(platform => {
    ctx.fillRect(platform.x, platform.y, platform.width, platform.height);
  });
}

function updateCollectibles() {
  for (let i = collectibles.length - 1; i >= 0; i--) {
    const collectible = collectibles[i];
    if (player.x < collectible.x + collectible.width &&
        player.x + player.width > collectible.x &&
        player.y < collectible.y + collectible.height &&
        player.y + player.height > collectible.y) {
      score += collectible.value;
      collectibles.splice(i, 1);
    }
  }
}

function drawCollectibles() {
  ctx.fillStyle = "gold";
  collectibles.forEach(collectible => {
    ctx.fillRect(collectible.x, collectible.y, collectible.width, collectible.height);
  });
}

function drawScore() {
  ctx.fillStyle = "black";
  ctx.font = "20px Arial";
  ctx.textAlign = "left";
  ctx.fillText(`Score: ${score}`, 10, 30);
}

function drawGameOver() {
  ctx.fillStyle = "rgba(0, 0, 0, 0.7)";
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = "white";
  ctx.font = "40px Arial";
  ctx.textAlign = "center";
  ctx.fillText("Game Over", canvas.width / 2, canvas.height / 2);
  ctx.font = "20px Arial";
  ctx.fillText(`Final Score: ${score}`, canvas.width / 2, canvas.height / 2 + 40);
}

initGame();

function gameLoop() {
  if (!gameOver) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    updatePlayer();
    drawPlayer();
    updatePlatforms();
    drawPlatforms();
    updateCollectibles();
    drawCollectibles();
    drawScore();
    requestAnimationFrame(gameLoop);
  } else {
    drawGameOver();
  }
}

gameLoop();';
                break;
            default:
                $code = '// Custom Game Template
const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

let score = 0;
let gameOver = false;

function gameLoop() {
  if (!gameOver) {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    requestAnimationFrame(gameLoop);
  } else {
    ctx.fillStyle = "black";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = "white";
    ctx.font = "40px Arial";
    ctx.textAlign = "center";
    ctx.fillText("Game Over", canvas.width / 2, canvas.height / 2);
    ctx.font = "20px Arial";
    ctx.fillText("Score: " + score, canvas.width / 2, canvas.height / 2 + 40);
  }
}

gameLoop();';
        }
    }
    
    if (isset($_GET['game_id'])) {
        $game_id = $_GET['game_id'];
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare('SELECT game_title, template_type, code FROM games WHERE id = ? AND user_id = ?');
        $stmt->execute([$game_id, $user_id]);
        $game = $stmt->fetch();
        
        if ($game) {
            $game_title = $game['game_title'];
            $template_type = $game['template_type'];
            $code = $game['code'];
        }
    }
    
    outputHeader('Game Editor');
    
    echo '<div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Game Editor</h3>';
                        
    if (isset($error)) {
        echo '<div class="alert alert-danger">' . $error . '</div>';
    }
    
    echo '<form id="editorForm" action="index.php?page=editor' . ($game_id ? '&game_id=' . $game_id : '') . '" method="POST">
            <div class="mb-3">
                <label for="game_title" class="form-label">Game Title</label>
                <input type="text" class="form-control" id="game_title" name="game_title" value="' . htmlspecialchars($game_title) . '" required>
            </div>
            <div class="mb-3">
                <label for="template_type" class="form-label">Template Type</label>
                <select class="form-select" id="template_type" name="template_type" required>
                    <option value="" disabled' . ($template_type == '' ? ' selected' : '') . '>Select a template</option>
                    <option value="catch_fruit"' . ($template_type == 'catch_fruit' ? ' selected' : '') . '>Catch the Fruit</option>
                    <option value="maze_runner"' . ($template_type == 'maze_runner' ? ' selected' : '') . '>Maze Runner</option>
                    <option value="jump_adventure"' . ($template_type == 'jump_adventure' ? ' selected' : '') . '>Jump Adventure</option>
                    <option value="custom"' . ($template_type == 'custom' ? ' selected' : '') . '>Custom Game</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="code" class="form-label">Game Code</label>
                <textarea class="form-control" id="code" name="code" rows="20" style="font-family: monospace;" required>' . htmlspecialchars($code) . '</textarea>
            </div>
            <input type="hidden" name="game_id" value="' . htmlspecialchars($game_id) . '">
            <div class="d-flex justify-content-between">
                <a href="index.php?page=dashboard" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="action" value="save" class="btn btn-primary">Save Game</button>
                <button type="button" id="runCodeBtn" class="btn btn-success">Run Code</button>
            </div>
        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Game Preview</h3>
                        <div class="mb-3">
                            <canvas id="gameCanvas" width="100%" height="400" style="border:1px solid #000; background-color: #fff; width: 100%; max-width: 800px;"></canvas>
                        </div>
                        <div class="alert alert-info">
                            <strong>Controls:</strong> Use arrow keys or WASD to move. Space to jump/interact.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById("runCodeBtn").addEventListener("click", function() {
        const code = document.getElementById("code").value;
        const gameCanvas = document.getElementById("gameCanvas");
        const ctx = gameCanvas.getContext("2d");
        ctx.clearRect(0, 0, gameCanvas.width, gameCanvas.height);
        
        gameCanvas.width = gameCanvas.offsetWidth;
        gameCanvas.height = 400;
        
        const keys = {};
        window.addEventListener("keydown", function(e) {
            keys[e.key] = true;
            if(["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "w", "a", "s", "d"].includes(e.key)) {
                e.preventDefault();
            }
        });
        window.addEventListener("keyup", function(e) {
            keys[e.key] = false;
        });
        
        window.gameInput = {
            isKeyPressed: function(key) {
                return keys[key] === true;
            },
            keys: keys
        };
        
        try {
            const executeCode = new Function(code);
            executeCode();
        } catch (error) {
            ctx.fillStyle = "black";
            ctx.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
            ctx.fillStyle = "red";
            ctx.font = "16px Arial";
            ctx.textAlign = "left";
            ctx.fillText("Error: " + error.message, 10, 30);
            console.error("Game code error:", error);
        }
    });
    </script>';
    
    outputFooter();
}

function renderMyGamesPage() {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare('SELECT id, game_title, template_type, created_at, updated_at FROM games WHERE user_id = ? ORDER BY updated_at DESC');
    $stmt->execute([$user_id]);
    $games = $stmt->fetchAll();
    
    outputHeader('My Games');
    
    echo '<div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Games</h2>
            <a href="index.php?page=dashboard" class="btn btn-primary">Create New Game</a>
        </div>';
    
    if (isset($_GET['saved']) && $_GET['saved'] == 'success') {
        echo '<div class="alert alert-success">Game saved successfully!</div>';
    }
    
    if (empty($games)) {
        echo '<div class="alert alert-info">You haven\'t created any games yet. Go to Game Templates to create your first game!</div>';
    } else {
        echo '<div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Game Title</th>
                        <th>Template Type</th>
                        <th>Created</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($games as $game) {
            $template_display = ucwords(str_replace('_', ' ', $game['template_type']));
            
            echo '<tr>
                    <td>' . htmlspecialchars($game['game_title']) . '</td>
                    <td>' . htmlspecialchars($template_display) . '</td>
                    <td>' . date('M j, Y', strtotime($game['created_at'])) . '</td>
                    <td>' . date('M j, Y g:i A', strtotime($game['updated_at'])) . '</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="index.php?page=run_game&game_id=' . $game['id'] . '" class="btn btn-sm btn-success">Play</a>
                            <a href="index.php?page=editor&game_id=' . $game['id'] . '" class="btn btn-sm btn-primary">Edit</a>
                            <form action="index.php?page=delete_game" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this game?\')">
                                <input type="hidden" name="game_id" value="' . $game['id'] . '">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>';
        }
        
        echo '</tbody>
            </table>
        </div>';
    }
    
    echo '</div>';
    
    outputFooter();
}

function renderRunGamePage() {
    global $pdo;
    
    if (!isset($_GET['game_id'])) {
        header('Location: index.php?page=my_games');
        exit;
    }
    
    $game_id = $_GET['game_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare('SELECT game_title, template_type, code FROM games WHERE id = ? AND user_id = ?');
    $stmt->execute([$game_id, $user_id]);
    $game = $stmt->fetch();
    
    if (!$game) {
        header('Location: index.php?page=my_games');
        exit;
    }
    
    outputHeader('Play Game: ' . htmlspecialchars($game['game_title']));
    
    echo '<div class="container py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>' . htmlspecialchars($game['game_title']) . '</h3>
                            <a href="index.php?page=my_games" class="btn btn-secondary">Back to My Games</a>
                        </div>
                        <div class="mb-4">
                            <canvas id="gameCanvas" width="800" height="600" style="border:1px solid #000; background-color: #fff;"></canvas>
                        </div>
                        <div class="alert alert-info">
                            <strong>Controls:</strong> Use arrow keys or WASD to move. Space to jump/interact.
                        </div>
                        <div id="gameError" class="alert alert-danger" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const gameCanvas = document.getElementById("gameCanvas");
        const ctx = gameCanvas.getContext("2d");
        const errorDisplay = document.getElementById("gameError");
        
        const keys = {};
        window.addEventListener("keydown", function(e) {
            keys[e.key] = true;
            if(["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight", " ", "w", "a", "s", "d"].includes(e.key)) {
                e.preventDefault();
            }
        });
        window.addEventListener("keyup", function(e) {
            keys[e.key] = false;
        });
        
        window.gameInput = {
            isKeyPressed: function(key) {
                return keys[key] === true;
            },
            keys: keys
        };
        
        ctx.clearRect(0, 0, gameCanvas.width, gameCanvas.height);
        
        try {
            const gameCode = `' . str_replace(["'", "\n"], ["\'", "\\n"], $game['code']) . '`;
            eval(gameCode);
        } catch (error) {
            ctx.fillStyle = "black";
            ctx.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
            ctx.fillStyle = "red";
            ctx.font = "16px Arial";
            ctx.textAlign = "left";
            ctx.fillText("Error: " + error.message, 10, 30);
            errorDisplay.textContent = "Error running game: " + error.message;
            errorDisplay.style.display = "block";
            console.error("Game code error:", error);
        }
    });
    </script>';
    
    outputFooter();
}
?>