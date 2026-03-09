<?php include_once '../../modele/config.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <title>Golden Memories — Paramètres</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="./profil.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Paramètres</h1>
    </div>

    <div class="settings-list">

        <div class="settings-item">
            <ion-icon name="person-outline"></ion-icon>
            <span>Mon compte</span>
            <ion-icon name="chevron-forward-outline" class="settings-arrow"></ion-icon>
        </div>

        <div class="settings-item">
            <ion-icon name="lock-closed-outline"></ion-icon>
            <span>Mot de passe</span>
            <ion-icon name="chevron-forward-outline" class="settings-arrow"></ion-icon>
        </div>

        <div class="settings-item">
            <ion-icon name="notifications-outline"></ion-icon>
            <span>Notifications</span>
            <ion-icon name="chevron-forward-outline" class="settings-arrow"></ion-icon>
        </div>

        <div class="settings-item">
            <ion-icon name="shield-outline"></ion-icon>
            <span>Confidentialité</span>
            <ion-icon name="chevron-forward-outline" class="settings-arrow"></ion-icon>
        </div>

        <div class="settings-item settings-danger">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Se déconnecter</span>
            <ion-icon name="chevron-forward-outline" class="settings-arrow"></ion-icon>
        </div>

    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>