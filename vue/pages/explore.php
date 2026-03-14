<?php
//Import du fichier config et de la class User
require_once '../../modele/config.php';
require_once '../../modele/User.php';

//Vérification si un utilisateur est connecté sinon redirection vers la page d'accueil
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

//Récupération + stockage des informations et id de l'utilisateur pour les requêtes
$userConnecte = $_SESSION['user'];
$userId = $userConnecte['id'];

//préparer une requête SQL de manière sécurisés pour récupérer les l'username, la pp, le nombre de like des souvenirs , si l'utilisateur a liké,
//et les souvenirs associé a ces parametres, seulement photos, videos, et notes, de manière aléatoire et limité à 50
$stmt = $bdd->prepare("
    SELECT m.*, u.username, u.picture,
           (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS likes_count,
           (SELECT COUNT(*) FROM likes WHERE memory_id = m.id AND user_id = :uid) AS user_liked
    FROM memories m
    JOIN users u ON m.user_id = u.id
    WHERE m.user_id = :uid
    AND m.type IN ('photo', 'video', 'note')
    ORDER BY RAND()
    LIMIT 50
");
$stmt->execute([':uid' => $userId]);

//création d'une variable $memories contenant les infos selectionnés sous forme de tableau associatif
$memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories</title>
</head>
<body>

<!-- Inclusion du header -->
<?php include './templates/header.php'; ?>

<h1 class="title">Explorer</h1>
<p class="soustitre">"Redécouvrez vos souvenirs au hasard"</p>

<div class="contenu-explore">
    <!-- Si aucun souvenir dans $memories affiché : -->
    <?php if (empty($memories)): ?>
        <p class="empty-msg">Aucun souvenir à explorer.</p>
    <?php else: foreach ($memories as $m): ?>

    <div class="memory-card">

        <!-- Si le souvenir est de type note -->
        <?php if ($m['type'] === 'note'): ?>
            <div class="memory-card-note">
                <div class="note-quote">"</div>
                <p><?= nl2br(htmlspecialchars($m['content'] ?? '')) ?></p>
            </div>

        <!-- Si le souvenir est de type video -->
        <?php elseif ($m['type'] === 'video'): ?>
            <video controls>
                <source src="<?= BASE_URL ?>/<?= htmlspecialchars($m['file_path']) ?>" type="video/mp4">
            </video>

        <!-- Si le souvenir est de type photo -->
        <?php else: ?>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($m['file_path']) ?>" alt="">
        <?php endif; ?>

        <!-- Affichage des infos du souvenirs -->
        <div class="memory-card-info">
            <!-- titre -->
            <?php if ($m['title']): ?>
                <div class="memory-card-title"><?= htmlspecialchars($m['title']) ?></div>
            <?php endif; ?>
            <!-- date -->
            <div class="memory-card-date"><?= date('d/m/Y', strtotime($m['created_at'])) ?></div>
        </div>

        <!-- bouton like et commentaires -->
        <div class="memory-card-actions">
            <button class="action-btn <?= $m['user_liked'] ? 'liked' : '' ?>"
                    onclick="toggleLike(this, <?= $m['id'] ?>)">
                <ion-icon name="<?= $m['user_liked'] ? 'heart' : 'heart-outline' ?>"></ion-icon>
                <!-- nombre de like -->
                <span><?= $m['likes_count'] ?></span>
            </button>
            <button class="action-btn" onclick="openComments(<?= $m['id'] ?>)">
                <ion-icon name="chatbubble-outline"></ion-icon>
                Commenter
            </button>
        </div>
    </div>

    <?php endforeach; endif; ?>
</div>

<?php include './templates/navbar.php'; ?>

<div id="overlay" onclick="closePanel()"></div>

<!-- Bloc pour la saisie et visualisation des commentaire -->
<div id="panel-comments">
    <div class="panel-handle"></div>
    <div class="panel-header">
        <span class="panel-title">Commentaires</span>
        <button class="panel-close" onclick="closePanel()">
            <ion-icon name="close"></ion-icon>
        </button>
    </div>
    <div id="comments-list"></div>
    <div class="comment-input-wrap">
        <textarea id="comment-input" class="comment-input" placeholder="Ajouter un commentaire…" rows="1"></textarea>
        <button class="comment-submit" onclick="submitComment()">
            <ion-icon name="send"></ion-icon>
        </button>
    </div>
</div>

<div id="toast"></div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/vue/assets/js/explore.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>