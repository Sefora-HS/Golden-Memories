<?php
require_once '../../modele/config.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <link rel="icon" type="image/png" sizes="60x60" href="<?= BASE_URL ?>/vue/assets/images/favicon.png">
    <title>Golden Memories — Inscription</title>
</head>
<body>

<div class="auth-images">
    <img src="../assets/images/neige.png" alt="">
    <img src="../assets/images/drink.jfif" alt="">
    <img src="../assets/images/plane.jfif" alt="">
</div>

<div class="auth-content">

    <div class="auth-header">
        <h1 class="auth-logo">Golden Memories</h1>
        <p class="auth-baseline">Vos moments précieux, tous au même endroit.</p>
    </div>

    <div class="auth-buttons">
        <a href="./signup.php" class="btn-primary">S'inscrire</a>
        <a href="./login.php" class="btn-secondary">Se connecter</a>
    </div>

    <div class="auth-logo-img">
        <img src="../assets/images/logo.png" alt="Golden Memories">
    </div>

</div>

<script>
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');
   }
</script>
</body>
</html>