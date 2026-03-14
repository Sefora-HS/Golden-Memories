<?php include_once '../../modele/config.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <title>Golden Memories — Notifications</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Notifications</h1>
    </div>

    <div class="notif-list">

        <div class="notif-item notif-unread">
            <div class="notif-icon">
                <ion-icon name="heart-outline"></ion-icon>
            </div>
            <div class="notif-content">
                <p class="notif-text"><strong>Léa</strong> a aimé votre souvenir</p>
                <span class="notif-time">Il y a 5 min</span>
            </div>
            <div class="notif-dot"></div>
        </div>

        <div class="notif-item notif-unread">
            <div class="notif-icon">
                <ion-icon name="people-outline"></ion-icon>
            </div>
            <div class="notif-content">
                <p class="notif-text"><strong>Sefora</strong> a partagé un album avec vous</p>
                <span class="notif-time">Il y a 1h</span>
            </div>
            <div class="notif-dot"></div>
        </div>

        <div class="notif-item">
            <div class="notif-icon">
                <ion-icon name="chatbubble-outline"></ion-icon>
            </div>
            <div class="notif-content">
                <p class="notif-text"><strong>Marie</strong> a commenté votre photo</p>
                <span class="notif-time">Hier</span>
            </div>
        </div>

        <div class="notif-item">
            <div class="notif-icon">
                <ion-icon name="time-outline"></ion-icon>
            </div>
            <div class="notif-content">
                <p class="notif-text">Votre capsule <strong>"Été 2024"</strong> s'ouvre bientôt !</p>
                <span class="notif-time">Il y a 2 jours</span>
            </div>
        </div>

    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>