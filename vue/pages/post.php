<?php
require_once '../../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$memoryId = $_GET['id'] ?? null;

// Récupère la page d'origine pour le bouton retour
$from = $_GET['from'] ?? 'index';
$urlRetour = ($from === 'profil')
    ? 'http://localhost/golden-memories/vue/pages/profil.php'
    : 'http://localhost/golden-memories/index.php';

if (!$memoryId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Récupère le souvenir depuis la db
$stmt = $bdd->prepare("SELECT * FROM memories WHERE id = :id");
$stmt->execute([':id' => $memoryId]);
$souvenir = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$souvenir) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Récupère le créateur du post
$stmtCreateur = $bdd->prepare("SELECT username, picture FROM users WHERE id = :id");
$stmtCreateur->execute([':id' => $souvenir['user_id']]);
$createur = $stmtCreateur->fetch(PDO::FETCH_ASSOC);

// Vérifie si l'utilisateur est le créateur du post
$estCreateur = ($souvenir['user_id'] === $userId);

// Compte les likes
$stmtLikes = $bdd->prepare("SELECT COUNT(*) FROM likes WHERE memory_id = :id");
$stmtLikes->execute([':id' => $memoryId]);
$nbLikes = $stmtLikes->fetchColumn();

// Vérifie si l'utilisateur a déjà liké
$stmtMonLike = $bdd->prepare("SELECT COUNT(*) FROM likes WHERE memory_id = :id AND user_id = :user_id");
$stmtMonLike->execute([':id' => $memoryId, ':user_id' => $userId]);
$jaLike = $stmtMonLike->fetchColumn() > 0;

// Compte les commentaires
$stmtNbComments = $bdd->prepare("SELECT COUNT(*) FROM comments WHERE memory_id = :id");
$stmtNbComments->execute([':id' => $memoryId]);
$nbComments = $stmtNbComments->fetchColumn();

// Récupère les commentaires
$stmtComments = $bdd->prepare("
    SELECT c.*, u.username, u.picture 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.memory_id = :id 
    ORDER BY c.created_at ASC
");
$stmtComments->execute([':id' => $memoryId]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title>Golden Memories — <?= htmlspecialchars($souvenir['title'] ?? 'Souvenir') ?></title>
</head>
<body>

<div class="post-page">

    <!-- Header -->
    <div class="post-top">
        <a href="<?= $urlRetour ?>" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="post-titre"><?= htmlspecialchars($souvenir['title'] ?? 'Souvenir') ?></h1>
    </div>

    <!-- Créateur du post -->
    <div class="post-createur">
        <div class="post-createur-avatar">
            <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($createur['picture'] ?? 'default.jpg') ?>" alt="">
        </div>
        <span class="post-createur-username">@<?= htmlspecialchars($createur['username'] ?? '') ?></span>
    </div>

    <!-- Contenu du souvenir -->
    <div class="post-contenu">
        <?php if ($souvenir['type'] === 'photo' && $souvenir['file_path']): ?>
            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" alt="Souvenir" class="post-media">
        <?php elseif ($souvenir['type'] === 'video' && $souvenir['file_path']): ?>
            <video src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" controls class="post-media"></video>
        <?php elseif ($souvenir['type'] === 'audio' && $souvenir['file_path']): ?>
            <div class="post-audio">
                <ion-icon name="musical-notes-outline"></ion-icon>
                <audio src="<?= BASE_URL ?>/<?= htmlspecialchars($souvenir['file_path']) ?>" controls></audio>
            </div>
        <?php elseif ($souvenir['type'] === 'note'): ?>
            <div class="post-note">
                <p><?= nl2br(htmlspecialchars($souvenir['content'] ?? '')) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Infos date -->
    <div class="post-infos">
        <span class="post-date">
            <ion-icon name="calendar-outline"></ion-icon>
            <?= date('d/m/Y', strtotime($souvenir['created_at'])) ?>
        </span>
    </div>

    <!-- Actions -->
    <div class="post-actions">

        <button class="post-action-btn like-btn <?= $jaLike ? 'liked' : '' ?>">
            <ion-icon name="<?= $jaLike ? 'heart' : 'heart-outline' ?>"></ion-icon>
            <span class="like-count"><?= $nbLikes ?></span>
        </button>

        <button class="post-action-btn" onclick="scrollToComments()">
            <ion-icon name="chatbubble-outline"></ion-icon>
            <span><?= $nbComments ?></span>
        </button>

        <button class="post-action-btn">
            <ion-icon name="share-outline"></ion-icon>
        </button>

        <?php if ($estCreateur): ?>
            <button class="post-action-btn post-action-danger" onclick="showConfirm()">
                <ion-icon name="trash-outline"></ion-icon>
            </button>
        <?php endif; ?>

    </div>

    <!-- Section commentaires -->
    <div class="post-comments" id="post-comments">
        <h3 class="post-comments-title">Commentaires</h3>

        <?php if (empty($comments)): ?>
            <p class="post-comments-empty">Aucun commentaire pour l'instant.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="post-comment-item">
                    <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($comment['picture'] ?? 'default.jpg') ?>" alt="">
                    <div class="post-comment-bubble">
                        <span class="post-comment-user"><?= htmlspecialchars($comment['username']) ?></span>
                        <p class="post-comment-text"><?= htmlspecialchars($comment['content']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Input commentaire -->
    <div class="post-comment-input">
        <input type="text" id="comment-input" placeholder="Ajouter un commentaire...">
        <button onclick="sendComment()"><ion-icon name="send-outline"></ion-icon></button>
    </div>

</div>

<!-- Pop-up confirmation suppression -->
<div class="confirm-overlay" id="confirm-overlay">
    <div class="confirm-box">
        <p>Supprimer ce souvenir ?</p>
        <p class="confirm-sub">Cette action est irréversible.</p>
        <div class="confirm-btns">
            <button class="confirm-cancel" onclick="hideConfirm()">Annuler</button>
            <a href="<?= BASE_URL ?>/controler/delete_memory.php?id=<?= $souvenir['id'] ?>" class="confirm-delete">Supprimer</a>
        </div>
    </div>
</div>

<script>
function scrollToComments() {
    document.getElementById('post-comments').scrollIntoView({ behavior: 'smooth' });
}

function showConfirm() {
    document.getElementById('confirm-overlay').classList.add('visible');
}

function hideConfirm() {
    document.getElementById('confirm-overlay').classList.remove('visible');
}

document.querySelector('.like-btn').addEventListener('click', function() {
    fetch('<?= BASE_URL ?>/controler/like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ memory_id: <?= $souvenir['id'] ?> })
    })
    .then(res => res.json())
    .then(data => {
        document.querySelector('.like-count').textContent = data.likes_count;
        this.classList.toggle('liked');
        const icon = this.querySelector('ion-icon');
        icon.setAttribute('name', this.classList.contains('liked') ? 'heart' : 'heart-outline');
    });
});

function sendComment() {
    const input = document.getElementById('comment-input');
    const content = input.value.trim();
    if (!content) return;

    fetch('<?= BASE_URL ?>/controler/comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ memory_id: <?= $souvenir['id'] ?>, content: content })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            location.reload();
        }
    });
}
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>