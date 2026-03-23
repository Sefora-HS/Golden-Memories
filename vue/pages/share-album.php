<?php
require_once '../../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userId = $_SESSION['user']['id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . '/vue/pages/albums.php');
    exit;
}
$albumId = (int)$_GET['id'];

// Récupère l'album et vérifie que l'utilisateur est membre ou propriétaire
$stmtAlbum = $bdd->prepare("
    SELECT a.*
    FROM albums a
    LEFT JOIN album_members am ON am.album_id = a.id AND am.user_id = :uid
    WHERE a.id = :album_id
      AND (a.user_id = :uid2 OR am.user_id IS NOT NULL)
    LIMIT 1
");
$stmtAlbum->execute([':uid' => $userId, ':album_id' => $albumId, ':uid2' => $userId]);
$album = $stmtAlbum->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    header('Location: ' . BASE_URL . '/vue/pages/albums.php');
    exit;
}

$estProprietaire = ($album['user_id'] == $userId);

// Propriétaire
$stmtOwner = $bdd->prepare("SELECT id, username, picture FROM users WHERE id = :id");
$stmtOwner->execute([':id' => $album['user_id']]);
$owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

// Membres de l'album (sans le propriétaire)
$stmtMembers = $bdd->prepare("
    SELECT u.id, u.username, u.picture, am.role
    FROM album_members am
    JOIN users u ON u.id = am.user_id
    WHERE am.album_id = :album_id
    ORDER BY am.joined_at ASC
");
$stmtMembers->execute([':album_id' => $albumId]);
$membresDB = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

// Fusion propriétaire + membres, déduplication
$allMembers = array_merge([$owner], $membresDB);
$seenIds = [];
$members = [];
foreach ($allMembers as $m) {
    if (!in_array($m['id'], $seenIds)) {
        $seenIds[] = $m['id'];
        $members[] = $m;
    }
}
$totalMembers = count($members);

// Souvenirs de l'album
$stmtMemories = $bdd->prepare("
    SELECT m.*, u.username as auteur_username, u.picture as auteur_picture
    FROM memories m
    JOIN users u ON u.id = m.user_id
    WHERE m.album_id = :album_id
    ORDER BY m.created_at DESC
");
$stmtMemories->execute([':album_id' => $albumId]);
$memories = $stmtMemories->fetchAll(PDO::FETCH_ASSOC);

// Image de couverture
$coverImage = null;
foreach ($memories as $m) {
    if ($m['type'] === 'photo' && $m['file_path']) {
        $coverImage = $m['file_path'];
        break;
    }
}

// Amis pas encore membres (pour le panel d'invitation)
$stmtAmis = $bdd->prepare("
    SELECT u.id, u.username, u.picture
    FROM friends f
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = :user_id AND f.status = 'accepted'
      AND u.id NOT IN (
          SELECT user_id FROM album_members WHERE album_id = :album_id
          UNION ALL SELECT :owner_id
      )
    ORDER BY u.username ASC
");
$stmtAmis->execute([
    ':user_id'  => $userId,
    ':album_id' => $albumId,
    ':owner_id' => $album['user_id']
]);
$amisDisponibles = $stmtAmis->fetchAll(PDO::FETCH_ASSOC);

$inviteSuccess = isset($_GET['invited']) ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vue/assets/css/app.css?v=<?= time() ?>">
    <title><?= htmlspecialchars($album['title']) ?> — Golden Memories</title>
</head>
<body>

<?php if ($inviteSuccess): ?>
    <div class="share-success" id="invite-toast">
        <ion-icon name="checkmark-circle-outline"></ion-icon>
        Ami invité avec succès !
    </div>
<?php endif; ?>

<div class="partages-pages">

    <!-- ── Hero couverture ── -->
    <div class="album-hero">
        <?php if ($coverImage): ?>
            <img src="<?= BASE_URL . '/' . htmlspecialchars($coverImage) ?>" alt="Couverture" class="hero-img">
        <?php else: ?>
            <div class="hero-img hero-img--placeholder">
                <ion-icon name="images-outline"></ion-icon>
            </div>
        <?php endif; ?>

        <div class="hero-overlay">
            <a href="<?= BASE_URL ?>/vue/pages/partage.php" class="hero-btn-back">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span>Partages</span>
            </a>
            <div class="hero-overlay-right">
                <?php if ($estProprietaire): ?>
                    <span class="hero-badge-owner">
                        <ion-icon name="shield-checkmark-outline"></ion-icon> Admin
                    </span>
                    <button class="hero-btn-delete" onclick="openDeleteModal()" title="Supprimer l'album">
                        <ion-icon name="trash-outline"></ion-icon>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Titre + meta ── -->
    <div class="album-header-info">
        <h1 class="album-main-title"><?= htmlspecialchars($album['title']) ?></h1>
        <p class="album-meta-info">
            <ion-icon name="calendar-outline"></ion-icon>
            <?= date('d/m/Y', strtotime($album['created_at'])) ?>
            &nbsp;·&nbsp;
            <ion-icon name="sparkles-outline"></ion-icon>
            <?= count($memories) ?> souvenir<?= count($memories) > 1 ? 's' : '' ?>
            &nbsp;·&nbsp;
            <ion-icon name="people-outline"></ion-icon>
            <?= $totalMembers ?> membre<?= $totalMembers > 1 ? 's' : '' ?>
        </p>
    </div>

</div>

<!-- ── Barre membres + actions ── -->
<div class="album-interact">

    <!-- Stack des avatars (cliquable pour voir les membres) -->
    <div class="members-stack" id="members-stack" onclick="toggleMembersPanel()" style="cursor:pointer;">
        <?php
        $displayed = array_slice($members, 0, 3);
        $remaining = $totalMembers - 3;
        foreach ($displayed as $i => $m):
        ?>
            <img
                src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($m['picture'] ?? 'default.jpg') ?>"
                alt="@<?= htmlspecialchars($m['username']) ?>"
                class="member-avatar <?= $i === 0 ? 'member-first' : '' ?>"
                title="@<?= htmlspecialchars($m['username']) ?>"
            >
        <?php endforeach; ?>
        <?php if ($remaining > 0): ?>
            <div class="member-avatar member-avatar-more" title="<?= $remaining ?> autre<?= $remaining > 1 ? 's' : '' ?> membre<?= $remaining > 1 ? 's' : '' ?>">
                +<?= $remaining ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions : ajouter souvenir + inviter -->
    <div class="album-actions-right">
        <a href="<?= BASE_URL ?>/vue/pages/ajout.php?prefill_album=<?= $albumId ?>"
           class="album-action-btn" title="Ajouter un souvenir">
            <ion-icon name="camera-outline"></ion-icon>
        </a>
        <button class="album-action-btn" onclick="toggleInvitePanel()" title="Inviter un ami">
            <ion-icon name="person-add-outline"></ion-icon>
        </button>
    </div>
</div>

<!-- ── Grille bento des souvenirs ── -->
<?php if (empty($memories)): ?>
    <div class="albums-empty">
        <ion-icon name="images-outline"></ion-icon>
        <p>Aucun souvenir dans cet album.<br>
        <a href="<?= BASE_URL ?>/vue/pages/ajout.php?prefill_album=<?= $albumId ?>"
           style="color:#c49a00;text-decoration:none;font-weight:600;">Sois le premier à en ajouter un ✨</a></p>
    </div>
<?php else: ?>
    <div class="bento-grid" style="padding-bottom:100px;">
        <?php foreach ($memories as $index => $memory): ?>

            <?php if ($memory['type'] === 'note'): ?>
                <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $memory['id'] ?>&from=share-album&album_id=<?= $albumId ?>"
                   class="bento-card bento-small type-note" style="text-decoration:none;">
                    <div class="note-content">
                        <ion-icon name="document-text-outline"></ion-icon>
                        <p><?= htmlspecialchars(mb_substr($memory['content'] ?? '', 0, 80)) ?><?= mb_strlen($memory['content'] ?? '') > 80 ? '…' : '' ?></p>
                    </div>
                    <div class="bento-overlay">
                        <span class="bento-type"><ion-icon name="document-text-outline"></ion-icon></span>
                        <?php if ($memory['title']): ?><h3><?= htmlspecialchars($memory['title']) ?></h3><?php endif; ?>
                        <span class="bento-date"><ion-icon name="calendar-outline"></ion-icon> <?= date('d/m/Y', strtotime($memory['created_at'])) ?></span>
                        <span class="bento-album"><ion-icon name="person-outline"></ion-icon> @<?= htmlspecialchars($memory['auteur_username']) ?></span>
                    </div>
                </a>

            <?php elseif ($memory['type'] === 'audio'): ?>
                <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $memory['id'] ?>&from=share-album&album_id=<?= $albumId ?>"
                   class="bento-card bento-small type-audio" style="text-decoration:none;">
                    <div class="audio-content">
                        <ion-icon name="musical-notes-outline"></ion-icon>
                    </div>
                    <div class="bento-overlay">
                        <span class="bento-type"><ion-icon name="musical-notes-outline"></ion-icon></span>
                        <?php if ($memory['title']): ?><h3><?= htmlspecialchars($memory['title']) ?></h3><?php endif; ?>
                        <span class="bento-date"><ion-icon name="calendar-outline"></ion-icon> <?= date('d/m/Y', strtotime($memory['created_at'])) ?></span>
                        <span class="bento-album"><ion-icon name="person-outline"></ion-icon> @<?= htmlspecialchars($memory['auteur_username']) ?></span>
                    </div>
                </a>

            <?php elseif ($memory['type'] === 'video' && $memory['file_path']): ?>
                <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $memory['id'] ?>&from=share-album&album_id=<?= $albumId ?>"
                   class="bento-card bento-large type-video" style="text-decoration:none;">
                    <video src="<?= BASE_URL ?>/<?= htmlspecialchars($memory['file_path']) ?>" muted playsinline></video>
                    <div class="bento-overlay">
                        <span class="bento-type"><ion-icon name="videocam-outline"></ion-icon></span>
                        <?php if ($memory['title']): ?><h3><?= htmlspecialchars($memory['title']) ?></h3><?php endif; ?>
                        <span class="bento-date"><ion-icon name="calendar-outline"></ion-icon> <?= date('d/m/Y', strtotime($memory['created_at'])) ?></span>
                        <span class="bento-album"><ion-icon name="person-outline"></ion-icon> @<?= htmlspecialchars($memory['auteur_username']) ?></span>
                    </div>
                </a>

            <?php elseif ($memory['type'] === 'photo' && $memory['file_path']): ?>
                <a href="<?= BASE_URL ?>/vue/pages/post.php?id=<?= $memory['id'] ?>&from=share-album&album_id=<?= $albumId ?>"
                   class="bento-card <?= ($index % 5 === 0) ? 'bento-large' : 'bento-small' ?> type-photo" style="text-decoration:none;">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($memory['file_path']) ?>"
                         alt="<?= htmlspecialchars($memory['title'] ?? 'Photo') ?>">
                    <div class="bento-overlay">
                        <span class="bento-type"><ion-icon name="image-outline"></ion-icon></span>
                        <?php if ($memory['title']): ?><h3><?= htmlspecialchars($memory['title']) ?></h3><?php endif; ?>
                        <span class="bento-date"><ion-icon name="calendar-outline"></ion-icon> <?= date('d/m/Y', strtotime($memory['created_at'])) ?></span>
                        <span class="bento-album"><ion-icon name="person-outline"></ion-icon> @<?= htmlspecialchars($memory['auteur_username']) ?></span>
                    </div>
                </a>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ── Panel : liste des membres ── -->
<div class="share-overlay" id="members-overlay" onclick="closeOverlay(event, 'members-overlay')">
    <div class="share-panel">
        <div class="share-handle"></div>
        <h3 class="share-title">Membres de l'album</h3>
        <div class="share-amis">
            <?php foreach ($members as $m): ?>
                <div class="share-ami-item">
                    <div class="share-ami-avatar">
                        <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($m['picture'] ?? 'default.jpg') ?>" alt="">
                    </div>
                    <span class="share-ami-username">
                        @<?= htmlspecialchars($m['username']) ?>
                        <?php if ($m['id'] == $album['user_id']): ?>
                            <span class="member-badge-admin">Admin</span>
                        <?php endif; ?>
                        <?php if ($m['id'] == $userId): ?>
                            <span class="member-badge-you">Vous</span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Panel : inviter un ami ── -->
<div class="share-overlay" id="invite-overlay" onclick="closeOverlay(event, 'invite-overlay')">
    <div class="share-panel">
        <div class="share-handle"></div>
        <h3 class="share-title">Inviter un ami</h3>

        <div class="share-search">
            <input type="text" id="invite-search-input" placeholder="Rechercher un ami..." oninput="filterInviteAmis()">
        </div>

        <div class="share-amis" id="invite-amis">
            <?php if (empty($amisDisponibles)): ?>
                <p class="share-empty">Tous tes amis sont déjà membres 🎉</p>
            <?php else: ?>
                <?php foreach ($amisDisponibles as $ami): ?>
                    <div class="share-ami-item" data-username="<?= htmlspecialchars($ami['username']) ?>">
                        <div class="share-ami-avatar">
                            <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($ami['picture'] ?? 'default.jpg') ?>" alt="">
                        </div>
                        <span class="share-ami-username">@<?= htmlspecialchars($ami['username']) ?></span>
                        <a href="<?= BASE_URL ?>/controler/add_album_member.php?album_id=<?= $albumId ?>&user_id=<?= $ami['id'] ?>"
                           class="share-ami-btn">Inviter</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Modale de confirmation de suppression ── -->
<div class="delete-modal-overlay" id="delete-modal-overlay" onclick="closeDeleteModal(event)">
    <div class="delete-modal">
        <div class="delete-modal-icon">
            <ion-icon name="trash-outline"></ion-icon>
        </div>
        <h3 class="delete-modal-title">Supprimer l'album ?</h3>
        <p class="delete-modal-text">
            Cette action est <strong>irréversible</strong>. L'album et tous ses souvenirs seront définitivement supprimés pour tous les membres.
        </p>
        <div class="delete-modal-actions">
            <button class="delete-modal-cancel" onclick="closeDeleteModalBtn()">Annuler</button>
            <a href="<?= BASE_URL ?>/controler/delete_album.php?id=<?= $albumId ?>&redirect=partage"
               class="delete-modal-confirm">Supprimer</a>
        </div>
    </div>
</div>

<?php include './templates/navbar.php'; ?>

<script>
function toggleInvitePanel() {
    document.getElementById('invite-overlay').classList.toggle('visible');
    document.getElementById('members-overlay').classList.remove('visible');
}

function toggleMembersPanel() {
    document.getElementById('members-overlay').classList.toggle('visible');
    document.getElementById('invite-overlay').classList.remove('visible');
}

function closeOverlay(e, id) {
    if (e.target === document.getElementById(id)) {
        document.getElementById(id).classList.remove('visible');
    }
}

function filterInviteAmis() {
    const q = document.getElementById('invite-search-input').value.toLowerCase();
    document.querySelectorAll('#invite-amis .share-ami-item').forEach(item => {
        item.style.display = item.dataset.username.toLowerCase().includes(q) ? 'flex' : 'none';
    });
}

function openDeleteModal() {
    document.getElementById('delete-modal-overlay').classList.add('visible');
}

function closeDeleteModal(e) {
    if (e.target === document.getElementById('delete-modal-overlay')) {
        document.getElementById('delete-modal-overlay').classList.remove('visible');
    }
}

function closeDeleteModalBtn() {
    document.getElementById('delete-modal-overlay').classList.remove('visible');
}

const toast = document.getElementById('invite-toast');
if (toast) {
    setTimeout(() => { toast.style.transition = 'opacity 0.5s'; toast.style.opacity = '0'; }, 2500);
}
</script>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>