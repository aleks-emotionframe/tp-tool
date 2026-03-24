<?php
/**
 * Login-Seite
 */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::login($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Ungültige E-Mail oder Passwort.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">TP</div>
                <h1><?= APP_NAME ?></h1>
                <p><?= APP_SUBTITLE ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= Helper::e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=login">
                <div class="form-group">
                    <label for="email">E-Mail</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= Helper::e($email ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Passwort</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Anmelden</button>
            </form>

            <div class="login-footer">
                <small>Standard: admin@tp-tool.ch / admin123</small>
            </div>
        </div>
    </div>
</body>
</html>
