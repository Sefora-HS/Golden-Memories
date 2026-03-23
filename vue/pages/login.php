<?php
require_once '../../modele/config.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $erreur = 'Tous les champs sont obligatoires.';
    } else {
        $stmt = $bdd->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="icon" type="image/png" sizes="60x60" href="<?= BASE_URL ?>/vue/assets/images/favicon.png">
    <title>Golden Memories — Connexion</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="./home.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Hello ! Bon retour :)</h1>
    </div>

    <?php if ($erreur): ?>
        <p class="form-error"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="POST" class="form-fields">

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Exemple : lea@mail.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" placeholder="Ton mot de passe">
        </div>

        <button type="submit" class="btn-primary">Se connecter</button>

        <a href="./signup.php" class="btn-secondary">Pas encore de compte ?</a>

    </form>

</div>

<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>