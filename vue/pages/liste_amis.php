<?php
require_once '../../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$recherche = trim($_GET['q'] ?? '');

// Récupère la liste des amis acceptés avec filtre de recherche
$stmt = $bdd->prepare("
    SELECT u.id, u.username, u.picture
    FROM friends f
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = :user_id 
    AND f.status = 'accepted'
    AND u.username LIKE :q
    ORDER BY u.username ASC
");
$stmt->execute([':user_id' => $userId, ':q' => '%' . $recherche . '%']);
$amis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories — Mes amis</title>
</head>
<body>

<div class="form-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>/vue/pages/profil.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Mes amis</h1>
    </div>

    <!-- Barre de recherche -->
    <form method="GET" action="" class="amis-search">
        <input
            type="text"
            name="q"
            placeholder="Rechercher parmi mes amis..."
            value="<?= htmlspecialchars($recherche) ?>"
            class="amis-search-input"
        >
        <button type="submit" class="amis-search-btn">
            <ion-icon name="search-outline"></ion-icon>
        </button>
    </form>

    <div class="amis-resultats">

        <?php if (empty($amis)): ?>
            <p class="amis-empty">
                <?= $recherche ? 'Aucun ami trouvé pour "' . htmlspecialchars($recherche) . '"' : 'Tu n\'as pas encore d\'amis 👀' ?>
            </p>
        <?php else: ?>
            <?php foreach ($amis as $ami): ?>
                <div class="amis-item">
                    <div class="amis-avatar">
                        <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($ami['picture'] ?? 'default.jpg') ?>" alt="">
                    </div>
                    <span class="amis-username">@<?= htmlspecialchars($ami['username']) ?></span>
                    <button class="amis-delete-btn" onclick="showConfirm(<?= $ami['id'] ?>, '<?= htmlspecialchars($ami['username']) ?>')">
                        <ion-icon name="person-remove-outline"></ion-icon>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</div>

<!-- Pop-up confirmation suppression -->
<div class="confirm-overlay" id="confirm-overlay">
    <div class="confirm-box">
        <p id="confirm-text">Supprimer cet ami ?</p>
        <p class="confirm-sub">Cette action est irréversible.</p>
        <div class="confirm-btns">
            <button class="confirm-cancel" onclick="hideConfirm()">Annuler</button>
            <a href="#" id="confirm-link" class="confirm-delete">Supprimer</a>
        </div>
    </div>
</div>

<script>
function showConfirm(amiId, username) {
    document.getElementById('confirm-text').textContent = 'Supprimer @' + username + ' de vos amis ?';
    document.getElementById('confirm-link').href = '<?= BASE_URL ?>/controler/remove_friend.php?id=' + amiId;
    document.getElementById('confirm-overlay').classList.add('visible');
}

function hideConfirm() {
    document.getElementById('confirm-overlay').classList.remove('visible');
}
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>