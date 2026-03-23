<?php
require_once '../../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Recherche d'utilisateurs
$recherche = trim($_GET['q'] ?? '');
$resultats = [];

if ($recherche) {
    $stmtRecherche = $bdd->prepare("
        SELECT id, username, picture 
        FROM users 
        WHERE username LIKE :q 
        AND id != :user_id
        LIMIT 20
    ");
    $stmtRecherche->execute([':q' => '%' . $recherche . '%', ':user_id' => $userId]);
    $resultats = $stmtRecherche->fetchAll(PDO::FETCH_ASSOC);
}

// Récupère les demandes envoyées et les amis acceptés
$stmtAmis = $bdd->prepare("SELECT friend_id, status FROM friends WHERE user_id = :user_id");
$stmtAmis->execute([':user_id' => $userId]);
$amisData = $stmtAmis->fetchAll(PDO::FETCH_ASSOC);
$amisIds = array_column($amisData, 'friend_id');
$amisStatus = array_column($amisData, 'status', 'friend_id');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories — Amis</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>/vue/pages/profil.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Trouver des amis</h1>
    </div>

    <form method="GET" action="" class="amis-search">
        <input 
            type="text" 
            name="q" 
            placeholder="Rechercher un utilisateur..." 
            value="<?= htmlspecialchars($recherche) ?>"
            class="amis-search-input"
        >
        <button type="submit" class="amis-search-btn">
            <ion-icon name="search-outline"></ion-icon>
        </button>
    </form>

    <div class="amis-resultats">
        <?php if ($recherche && empty($resultats)): ?>
            <p class="amis-empty">Aucun utilisateur trouvé pour "<?= htmlspecialchars($recherche) ?>"</p>

        <?php elseif (!empty($resultats)): ?>
            <?php foreach ($resultats as $user): ?>
                <div class="amis-item">
                    <div class="amis-avatar">
                        <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($user['picture'] ?? 'default.jpg') ?>" alt="">
                    </div>
                    <span class="amis-username">@<?= htmlspecialchars($user['username']) ?></span>

                    <?php if (in_array($user['id'], $amisIds)): ?>
                        <?php if ($amisStatus[$user['id']] === 'accepted'): ?>
                            <span class="amis-deja">Ami ✓</span>
                        <?php else: ?>
                            <span class="amis-deja">Demande envoyée ⏳</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/controler/add_friends.php?id=<?= $user['id'] ?>&q=<?= urlencode($recherche) ?>" class="amis-add-btn">
                            <ion-icon name="person-add-outline"></ion-icon>
                            Ajouter
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php elseif (!$recherche): ?>
            <p class="amis-empty">Tape un nom pour rechercher un utilisateur 🔍</p>
        <?php endif; ?>
    </div>

</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>