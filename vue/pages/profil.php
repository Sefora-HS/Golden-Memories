<?php include_once '../../modele/config.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <title>Golden Memories — Profil</title>
</head>
<body>

<div class="profil-page">

    <!-- Header navigation -->
    <div class="profil-top">
        <a href="<?= BASE_URL ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <p class="profil-arobase">@candice</p>
        <a href="./parametres.php" class="parametre">
            <ion-icon name="cog-outline"></ion-icon>
        </a>
    </div>

    <!-- Photo de profil + nom -->
    <div class="profil-header">
        <div class="profil-avatar">
            <img src="../assets/images/default.jpg" alt="Photo de profil">
        </div>
        <h2 class="profil-nom">Candice</h2>
        <p class="profil-bio">✨ Collectionneuse de jolis souvenirs</p>
    </div>

    <!-- Stats -->
    <div class="profil-stats">
        <div class="profil-stat">
            <span class="stat-nombre">24</span>
            <span class="stat-label">Souvenirs</span>
        </div>
        <div class="profil-stat">
            <span class="stat-nombre">6</span>
            <span class="stat-label">Albums</span>
        </div>
        <div class="profil-stat">
            <span class="stat-nombre">12</span>
            <span class="stat-label">Amis</span>
        </div>
    </div>

    <!-- Grille de photos -->
    <div class="profil-grille">
        <img src="../assets/images/neige.png" alt="">
        <img src="../assets/images/drink.jfif" alt="">
        <img src="../assets/images/plane.jfif" alt="">
        <img src="../assets/images/neige.png" alt="">
        <img src="../assets/images/drink.jfif" alt="">
        <img src="../assets/images/plane.jfif" alt="">
    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>