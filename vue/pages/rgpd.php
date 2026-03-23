<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="icon" type="image/png" sizes="60x60" href="<?= BASE_URL ?>/vue/assets/images/favicon.png">
    <title>Golden Memories — Confidentialité</title>
</head>
<body>

<div class="form-page">
    <div class="form-top">
        <a href="parametres.php" class="form-back">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <h2 class="form-title">Confidentialité</h2>
    </div>

    <div class="legal-card">
        <div class="privacy-content">
            <p class="soustitre" style="margin: 0 0 1rem 0;">Dernière mise à jour : 10 mars 2026</p>

            <section class="privacy-section">
                <h3>1. Introduction</h3>
                <p>Bienvenue sur <strong>Golden Memories</strong>. Nous accordons une importance capitale à la protection de vos souvenirs et de vos données personnelles.</p>
            </section>

            <section class="privacy-section">
                <h3>2. Données collectées</h3>
                <p>Pour vous offrir la meilleure expérience bento, nous collectons :</p>
                <ul>
                    <li>Vos médias (photos, vidéos, audio) que vous choisissez d'importer.</li>
                    <li>Vos notes personnelles.</li>
                    <li>Informations de compte (nom, email).</li>
                </ul>
            </section>

            <section class="privacy-section">
                <h3>3. Utilisation des données</h3>
                <p>Vos souvenirs restent les vôtres. Nous utilisons vos données uniquement pour afficher votre grille personnalisée et permettre l'interaction avec vos contenus.</p>
            </section>

            <section class="privacy-section">
                <h3>4. Vos droits (RGPD)</h3>
                <p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et de suppression de vos données directement depuis les paramètres de l'application.</p>
            </section>
            
            <a href="../../controleur/export_data.php" class="btn-download">
                    <ion-icon name="download-outline"></ion-icon>
                    Télécharger mes données (JSON)
            </a>
            
        </div>

        <div class="auth-logo-img" style="padding-top: 2rem;">
            <p class="auth-logo" style="font-size: 1.8rem;">Golden Memories</p>
        </div>
    </div>
</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>

</body>
</html>

