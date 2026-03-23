<?php
require_once '../../modele/config.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$username || !$email || !$password || !$confirm) {
        $erreur = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } elseif (strlen($password) < 8) {
        $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $confirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        $check = $bdd->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $check->execute([':email' => $email, ':username' => $username]);

        if ($check->fetch()) {
            $erreur = 'Cet email ou ce nom d\'utilisateur est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $bdd->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([':username' => $username, ':email' => $email, ':password' => $hash]);

            $newUser = $bdd->prepare("SELECT * FROM users WHERE email = :email");
            $newUser->execute([':email' => $email]);
            $_SESSION['user'] = $newUser->fetch(PDO::FETCH_ASSOC);

            header('Location: ' . BASE_URL . '/index.php');
            exit;
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
    <title>Golden Memories — Inscription</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="./home.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Créer un compte</h1>
    </div>

    <?php if ($erreur): ?>
        <p class="form-error"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="POST" class="form-fields">

        <div class="form-group">
            <label>Nom d'utilisateur</label>
            <input type="text" name="username" placeholder="Votre pseudo" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Votre adresse mail" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" placeholder="8 caractères minimum">
        </div>

        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="confirm" placeholder="Répéter le mot de passe">
        </div>

        <button type="submit" class="btn-primary">Créer mon compte</button>

    </form>

</div>

<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>