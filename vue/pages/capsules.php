<?php include_once '../../modele/config.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <title>Golden Memories — Capsules</title>
</head>
<body>

<div class="capsules-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Capsules</h1>
    </div>

<div class="capsule-hero">
<p class="capsule-hero-label">✦ Vos souvenirs temporels, qui viennent du futur</p>
<p class="capsule-hero-sub">Des moments scellés dans le temps, qui s'ouvrent quand le moment est venu.</p>
</div>
    <!-- Capsule bientôt -->
    <div class="capsule-card capsule-soon">
        <div class="capsule-icon">
            <ion-icon name="time-outline"></ion-icon>
        </div>
        <div class="capsule-info">
            <h3 class="capsule-title">Fête de famille 🩷 </h3>
            <p class="capsule-date">S'ouvre dans 3 jours</p>
        </div>
        <div class="capsule-badge capsule-badge-soon">Bientôt</div>
    </div>

    <!-- Capsule verrouillée -->
    <div class="capsule-card capsule-locked">
        <div class="capsule-icon">
            <ion-icon name="lock-closed-outline"></ion-icon>
        </div>
        <div class="capsule-info">
            <h3 class="capsule-title">Été 2024 🌸</h3>
            <p class="capsule-date">S'ouvre le 21 juin 2025</p>
        </div>
        <div class="capsule-badge capsule-badge-locked">Verrouillée</div>
    </div>

    <!-- Capsule ouverte -->
    <div class="capsule-card capsule-open">
        <div class="capsule-icon">
            <ion-icon name="star-outline"></ion-icon>
        </div>
        <div class="capsule-info">
            <h3 class="capsule-title">Voyage à Rome 🇮🇹</h3>
            <p class="capsule-date">Ouverte depuis le 1er jan 2025</p>
        </div>
        <div class="capsule-badge capsule-badge-open">Ouverte</div>
    </div>

    <!-- Photos de la capsule ouverte -->
    <div class="capsule-photos">
        <img src="../assets/images/neige.png" alt="">
        <img src="../assets/images/drink.jfif" alt="">
        <img src="../assets/images/plane.jfif" alt="">
    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>