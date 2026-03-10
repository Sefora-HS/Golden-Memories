<?php include_once '../../modele/config.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">   
    <title>Golden Memories — Confidentialité</title>
</head>
<body>



<div class="form-page settings-dark" id="settings-container">
    
    <div id="main-settings-view">
        <div class="form-top">
            <a href="index.html" class="form-back">
                <ion-icon name="arrow-back-outline"></ion-icon>
            </a>
            <h2 class="form-title">Paramètres</h2>
        </div>

        <div class="search-bar">
            <ion-icon name="search-outline"></ion-icon>
            <input type="text" placeholder="Rechercher">
        </div>

        <div class="settings-list">
            <div class="setting-item">
                <ion-icon name="person-add-outline"></ion-icon>
                <span>S'abonner et inviter des amis</span>
            </div>

            <div class="setting-item" onclick="openSubPage('page-notifications')">
                <ion-icon name="notifications-outline"></ion-icon>
                <span>Notifications</span>
            </div>

            <a href="privacy.html" class="setting-item">
                <ion-icon name="lock-closed-outline"></ion-icon>
                <span>Confidentialité</span>
            </a>

            <div class="setting-item">
                <ion-icon name="shield-checkmark-outline"></ion-icon>
                <span>Sécurité</span>
            </div>

            <div class="setting-item">
                <ion-icon name="megaphone-outline"></ion-icon>
                <span>Publicités</span>
            </div>

            <div class="setting-item" onclick="openSubPage('page-compte')">
                <ion-icon name="person-circle-outline"></ion-icon>
                <span>Compte</span>
            </div>

            <div class="setting-item">
                <ion-icon name="help-circle-outline"></ion-icon>
                <span>Aide</span>
            </div>

            <div class="setting-item">
                <ion-icon name="information-circle-outline"></ion-icon>
                <span>Infos</span>
            </div>

            <div class="setting-item">
                <ion-icon name="color-palette-outline"></ion-icon>
                <span>Thème</span>
            </div>
        </div>

        <div class="meta-section">
            <p class="meta-title">Golden Memories</p>
            <p class="meta-subtitle">Espace Comptes</p>
            <p class="meta-text">Réglez les paramètres des expériences partagées sur Golden Memories.</p>
        </div>
    </div>

    <div id="page-compte" class="sub-settings-page" style="display: none;">
        <div class="form-top">
            <button class="back-btn" onclick="closeSubPage('page-compte')">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </button>
            <h2 class="form-title">Compte</h2>
        </div>
        
        <div class="form-fields">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" value="Syrine">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" value="syrine@example.com">
            </div>
            <button class="btn-primary" onclick="alert('Modifications enregistrées !')">Enregistrer</button>
        </div>
    </div>

    <div id="page-notifications" class="sub-settings-page" style="display: none;">
        <div class="form-top">
            <button class="back-btn" onclick="closeSubPage('page-notifications')">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </button>
            <h2 class="form-title">Notifications</h2>
        </div>
        <p style="color: #888; margin-bottom: 20px;">Gérez vos alertes souvenirs.</p>
        <div class="form-group" style="flex-direction: row; justify-content: space-between; align-items: center;">
            <label>Pause générale</label>
            <input type="checkbox" style="width: 20px; height: 20px;">
        </div>
    </div>

</div>


<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
<script>
    function openSubPage(pageId) {
        // On cache la liste principale
        document.getElementById('main-settings-view').style.display = 'none';
        // On affiche la page demandée
        document.getElementById(pageId).style.display = 'block';
    }

    function closeSubPage(pageId) {
        // On cache la page de détail
        document.getElementById(pageId).style.display = 'none';
        // On réaffiche la liste principale
        document.getElementById('main-settings-view').style.display = 'block';
    }
</script>

</body>
</html>