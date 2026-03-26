<?php
//import du fichier config
require_once '../../modele/config.php';

//Vérification si un utilisateur est connecté sinon redirection vers la page d'accueil
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

//Récupération + stockage des informations et id de l'utilisateur pour les requêtes
$userConnecte = $_SESSION['user'];
$userId = $userConnecte['id'];

// préparer une requête SQL de manière sécurisés pour récupérer les albums de l'utilisateur
$stmtAlbums = $bdd->prepare("SELECT id, title FROM albums WHERE user_id = :uid ORDER BY created_at DESC");
$stmtAlbums->execute([':uid' => $userId]);
$albums = $stmtAlbums->fetchAll(PDO::FETCH_ASSOC);

// Récupère les amis acceptés pour le panel de partage de capsule
$stmtAmis = $bdd->prepare("
    SELECT u.id, u.username, u.picture
    FROM friends f
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = :uid AND f.status = 'accepted'
    ORDER BY u.username ASC
");
$stmtAmis->execute([':uid' => $userId]);
$amisPourCapsule = $stmtAmis->fetchAll(PDO::FETCH_ASSOC);

// Création d'un album via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_album_title'])) {
    //récupération du titre entré par l'utilisateur
    $newAlbumTitle = trim($_POST['new_album_title']);
    // récupération du statut de partage (0 par défaut si non fourni)
    $isShared = isset($_POST['is_shared']) ? (int)$_POST['is_shared'] : 0;
    if (!empty($newAlbumTitle)) {
        $stmtAlbum = $bdd->prepare("
            INSERT INTO albums (user_id, title, nbr_memories, is_shared)
            VALUES (:uid, :title, 0, :is_shared)
        ");
        $stmtAlbum->execute([':uid' => $userId, ':title' => $newAlbumTitle, ':is_shared' => $isShared]);
        $newId = $bdd->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'title' => $newAlbumTitle, 'is_shared' => $isShared]);
    } else {
        //si aucun titre, empêche la création de l'album
        echo json_encode(['success' => false]);
    }
    exit;
}

$success = '';
$erreur  = '';

//attribution des valeurs saisie dans le formulaire a des variables
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type          = $_POST['type'] ?? '';
    $title         = trim($_POST['title'] ?? '');
    $content       = trim($_POST['content'] ?? '');
    $albumId       = !empty($_POST['album_id']) ? (int)$_POST['album_id'] : null;
    $isCapsule       = isset($_POST['is_capsule']) && $_POST['is_capsule'] === '1';
    $capsuleTitle    = trim($_POST['capsule_title'] ?? '');
    $unlockAt        = trim($_POST['unlock_at'] ?? '');
    $capsuleShareIds = isset($_POST['capsule_share_ids']) ? array_filter(array_map('intval', (array)$_POST['capsule_share_ids'])) : [];

    //autorise les photos, videos, audios et notes
    $allowedTypes = ['photo', 'video', 'audio', 'note'];

    //gestion des erreurs avec message à afficher associer
    if (!in_array($type, $allowedTypes)) {
        $erreur = 'Type de souvenir invalide.';
    } elseif ($type === 'note' && empty($content)) {
        $erreur = 'Le contenu de la note est obligatoire.';
    } elseif ($type !== 'note' && empty($_FILES['file']['name'])) {
        $erreur = 'Veuillez sélectionner un fichier.';
    } elseif ($isCapsule && empty($unlockAt)) {
        $erreur = 'Veuillez choisir une date d\'ouverture pour la capsule.';
    } elseif ($isCapsule && strtotime($unlockAt) <= time()) {
        $erreur = 'La date d\'ouverture doit être dans le futur.';
    } else {
        $filePath = null;

        // extensions autorisés à être importé en dehors des notes
        if ($type !== 'note') {
            $file     = $_FILES['file'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = [
                'photo' => ['jpg','jpeg','png','gif','webp','jfif'],
                'video' => ['mp4','mov','avi','webm'],
                'audio' => ['mp3','wav','ogg','m4a'],
            ];

            // gestion des erreurs , taille, format...
            if (!in_array($ext, $allowed[$type])) {
                $erreur = 'Format de fichier non supporté.';
            } elseif ($file['size'] > 1000 * 1024 * 1024) {
                $erreur = 'Fichier trop lourd (max 50 Mo).';
            } else {
                //création d'un chemin dans le dossier uploads pour ajouter le souvenir
                $uploadDir = __DIR__ . '/../../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName  = uniqid('mem_') . '.' . $ext;
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $filePath = 'uploads/' . $newName;
                } else {
                    $erreur = 'Erreur lors de l\'upload du fichier.';
                }
            }
        }

        if (!$erreur) {
            // Insertion du souvenir dans la base de données, si aucune erreur détéctée
            $stmt = $bdd->prepare("
                INSERT INTO memories (user_id, album_id, type, title, file_path, content)
                VALUES (:uid, :album_id, :type, :title, :file_path, :content)
            ");
            $stmt->execute([
                ':uid'       => $userId,
                ':album_id'  => $albumId,
                ':type'      => $type,
                ':title'     => $title ?: null,
                ':file_path' => $filePath,
                ':content'   => $content ?: null,
            ]);

            //on récupère l'id du souvenir que l'on vient de créer
            $memoryId = $bdd->lastInsertId();

            // Mise à jour du compteur album, si le souvenir appartient à un album
            if ($albumId) {
                $bdd->prepare("UPDATE albums SET nbr_memories = nbr_memories + 1 WHERE id = :id")
                    ->execute([':id' => $albumId]);
            }

            // Création de la capsule temporelle si l'utilisateur a selectionné l'option
            if ($isCapsule) {
                $stmtCap = $bdd->prepare("
                    INSERT INTO time_capsules (memory_id, capsule_title, unlock_at, is_open)
                    VALUES (:memory_id, :capsule_title, :unlock_at, 0)
                ");
                $stmtCap->execute([
                    ':memory_id'     => $memoryId,
                    ':capsule_title' => $capsuleTitle ?: ($title ?: null),
                    ':unlock_at'     => $unlockAt,
                ]);

                $capsuleId = $bdd->lastInsertId();

                // Partage de la capsule avec les amis sélectionnés dès la création
                if (!empty($capsuleShareIds)) {
                    // Vérifie que chaque destinataire est bien un ami accepté
                    $stmtCheckAmi = $bdd->prepare("
                        SELECT id FROM friends
                        WHERE user_id = :uid AND friend_id = :fid AND status = 'accepted'
                    ");
                    $stmtInsertShare = $bdd->prepare("
                        INSERT IGNORE INTO capsule_shared (capsule_id, user_id) VALUES (:capsule_id, :user_id)
                    ");
                    $stmtNotifCap = $bdd->prepare("
                        INSERT INTO notifications (user_id, from_user_id, type, content, reference_id)
                        VALUES (:user_id, :from_user_id, 'new_memory', :content, :reference_id)
                    ");

                    $titreNotif = $capsuleTitle ?: ($title ?: 'une capsule');

                    foreach ($capsuleShareIds as $amiId) {
                        $stmtCheckAmi->execute([':uid' => $userId, ':fid' => $amiId]);
                        if (!$stmtCheckAmi->fetch()) continue; // sécurité : skip si pas ami

                        $stmtInsertShare->execute([':capsule_id' => $capsuleId, ':user_id' => $amiId]);
                        $stmtNotifCap->execute([
                            ':user_id'      => $amiId,
                            ':from_user_id' => $userId,
                            ':content'      => '@' . $userConnecte['username'] . ' a partagé une capsule avec vous : "' . $titreNotif . '". Elle s\'ouvrira le ' . date('d/m/Y', strtotime($unlockAt)) . ' !',
                            ':reference_id' => $capsuleId,
                        ]);
                    }
                }
            }

            // --- DEBUT DU CODE NOTIFICATION ---
            // On notifie UNIQUEMENT si le souvenir est publié dans un album partagé.
            // Un souvenir personnel ou dans un album privé ne génère aucune notification.
            if ($albumId) {
                // Vérifie que l'album est bien de type partagé
                $stmtCheckAlbum = $bdd->prepare("SELECT is_shared FROM albums WHERE id = :id");
                $stmtCheckAlbum->execute([':id' => $albumId]);
                $albumInfo = $stmtCheckAlbum->fetch(PDO::FETCH_ASSOC);

                if ($albumInfo && $albumInfo['is_shared'] == 1) {
                    // Notifie uniquement les membres de cet album, sauf l'auteur du souvenir
                    $stmtNotif = $bdd->prepare("
                        INSERT INTO notifications (user_id, from_user_id, type, content, reference_id)
                        SELECT user_id, :my_id, 'new_memory', :msg, :mem_id
                        FROM album_members
                        WHERE album_id = :album_id
                          AND user_id != :my_id
                    ");

                    $msgNotif = $userConnecte['username'] . " a ajouté un souvenir dans l'album partagé : " . ($title ?: "Sans titre");

                    $stmtNotif->execute([
                        ':my_id'    => $userId,
                        ':msg'      => $msgNotif,
                        ':mem_id'   => $memoryId,
                        ':album_id' => $albumId,
                    ]);
                }
                // Album non partagé (is_shared = 0) → aucune notification
            }
            // Souvenir sans album (personnel) → aucune notification
            // --- FIN DU CODE NOTIFICATION ---

            //redirection vers la page d'accueil une fois le souvenir crée
            header('Location: ' . BASE_URL . '/index.php?added=1');
            exit;
        }
    }
}
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

<?php include './templates/header.php'; ?>

<div class="ajout-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>/index.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Nouveau souvenir</h1>
    </div>

    <?php if ($erreur): ?>
        <p class="form-error"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <div class="type-grid" id="type-grid">
        <button class="type-card" data-type="photo" onclick="selectType('photo')">
            <div class="type-icon"><ion-icon name="image-outline"></ion-icon></div>
            <span>Photo</span>
        </button>
        <button class="type-card" data-type="video" onclick="selectType('video')">
            <div class="type-icon"><ion-icon name="videocam-outline"></ion-icon></div>
            <span>Vidéo</span>
        </button>
        <button class="type-card" data-type="audio" onclick="selectType('audio')">
            <div class="type-icon"><ion-icon name="musical-notes-outline"></ion-icon></div>
            <span>Audio</span>
        </button>
        <button class="type-card" data-type="note" onclick="selectType('note')">
            <div class="type-icon"><ion-icon name="document-text-outline"></ion-icon></div>
            <span>Note</span>
        </button>
    </div>

    <form method="POST" enctype="multipart/form-data" id="ajout-form" class="ajout-form hidden">
        <input type="hidden" name="type" id="input-type">

        <div class="form-group">
            <label>Titre <span class="optional">(optionnel)</span></label>
            <input type="text" name="title" placeholder="Donnez un titre à ce souvenir" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <div id="file-zone" class="file-zone hidden">
            <label class="file-drop" id="file-label" for="file-input">
                <ion-icon name="cloud-upload-outline" id="upload-icon"></ion-icon>
                <span id="upload-text">Appuyez pour importer</span>
                <span id="file-name" class="file-name"></span>
            </label>
            <input type="file" name="file" id="file-input" class="hidden" onchange="previewFile(this)">
        </div>

        <div id="note-zone" class="form-group hidden">
            <label>Votre note</label>
            <textarea name="content" id="note-content" class="note-textarea" placeholder="Écrivez votre souvenir ici…" rows="6"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>

        <div id="preview-zone" class="preview-zone hidden"></div>

        
        <div class="extra-section">
            <button type="button" class="extra-toggle" onclick="toggleExtra('album-block')">
                <div class="extra-toggle-left">
                    <div class="extra-toggle-icon"><ion-icon name="albums-outline"></ion-icon></div>
                    <div>
                        <p class="extra-toggle-label">Ajouter à un album</p>
                        <p class="extra-toggle-sub" id="album-sub">Aucun album sélectionné</p>
                    </div>
                </div>
                <ion-icon name="chevron-forward-outline" class="extra-chevron" id="chevron-album"></ion-icon>
            </button>
            <div id="album-block" class="extra-block hidden">
                <div class="album-list">
                    <label class="album-option">
                        <input type="radio" name="album_id" value="" onchange="updateAlbumSub(this)" checked>
                        <span class="album-option-content">
                            <ion-icon name="close-circle-outline"></ion-icon>
                            Aucun album
                        </span>
                    </label>
                    <?php foreach ($albums as $album): ?>
                    <label class="album-option">
                        <input type="radio" name="album_id" value="<?= $album['id'] ?>"
                               onchange="updateAlbumSub(this, '<?= htmlspecialchars($album['title']) ?>')">
                        <span class="album-option-content">
                            <ion-icon name="albums-outline"></ion-icon>
                            <?= htmlspecialchars($album['title']) ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="new-album-wrap">
                    <input type="text" id="new-album-input" placeholder="Nom du nouvel album…" class="new-album-input">
                    <label class="new-album-share-toggle" id="share-toggle-label" title="Partager l'album">
                        <input type="checkbox" id="new-album-shared" style="display:none;">
                        <ion-icon name="people-outline" id="share-icon"></ion-icon>
                    </label>
                    <button type="button" onclick="createAlbum()" class="new-album-btn">
                        <ion-icon name="add-outline"></ion-icon>
                        Créer
                    </button>
                </div>
            </div>
        </div>
        

        <div class="extra-section">
            <button type="button" class="extra-toggle" onclick="toggleCapsule()">
                <div class="extra-toggle-left">
                    <div class="extra-toggle-icon capsule-icon-bg"><ion-icon name="time-outline"></ion-icon></div>
                    <div>
                        <p class="extra-toggle-label">Créer une capsule temporelle</p>
                        <p class="extra-toggle-sub" id="capsule-sub">Ce souvenir s'ouvrira à une date choisie</p>
                    </div>
                </div>
                <div class="toggle-switch" id="capsule-toggle-switch">
                    <div class="toggle-thumb"></div>
                </div>
            </button>

            <div id="capsule-block" class="extra-block hidden">
                <input type="hidden" name="is_capsule" id="is-capsule-input" value="0">
                <div class="form-group" style="margin-bottom:.75rem;">
                    <label>Nom de la capsule <span class="optional">(optionnel)</span></label>
                    <input type="text" name="capsule_title" placeholder="Ex : Été 2025 🌸"
                           value="<?= htmlspecialchars($_POST['capsule_title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date d'ouverture</label>
                    <input type="datetime-local" name="unlock_at" id="unlock-at"
                           min="<?= date('Y-m-d\TH:i') ?>"
                           value="<?= htmlspecialchars($_POST['unlock_at'] ?? '') ?>">
                </div>

                <!-- Partager la capsule avec des amis dès la création -->
                <div class="form-group capsule-share-group">
                    <button type="button" class="capsule-share-toggle" onclick="toggleCapsuleShare()">
                        <ion-icon name="people-outline" id="capsule-share-icon"></ion-icon>
                        <span id="capsule-share-label">Partager avec des amis</span>
                        <ion-icon name="chevron-forward-outline" id="capsule-share-chevron" style="margin-left:auto;"></ion-icon>
                    </button>
                    <div id="capsule-share-block" class="hidden">
                        <?php if (empty($amisPourCapsule)): ?>
                            <p class="share-empty" style="padding:.5rem 0;">Tu n'as pas encore d'amis 👀</p>
                        <?php else: ?>
                            <p class="capsule-share-hint">Ils pourront voir la capsule une fois la date atteinte.</p>
                            <div class="capsule-share-list" id="capsule-share-list">
                                <?php foreach ($amisPourCapsule as $ami): ?>
                                <label class="capsule-share-item">
                                    <input type="checkbox" name="capsule_share_ids[]" value="<?= $ami['id'] ?>"
                                        <?= in_array($ami['id'], $_POST['capsule_share_ids'] ?? []) ? 'checked' : '' ?>>
                                    <div class="capsule-share-avatar">
                                        <img src="<?= BASE_URL ?>/vue/assets/images/<?= htmlspecialchars($ami['picture'] ?? 'default.jpg') ?>" alt="">
                                    </div>
                                    <span class="capsule-share-username">@<?= htmlspecialchars($ami['username']) ?></span>
                                    <div class="capsule-share-check"><ion-icon name="checkmark-outline"></ion-icon></div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <button type="submit" class="btn-primary ajout-submit">
            <ion-icon name="checkmark-outline"></ion-icon>
            Enregistrer le souvenir
        </button>
    </form>

</div>

<?php include './templates/navbar.php'; ?>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script>
function toggleCapsuleShare() {
    const block   = document.getElementById('capsule-share-block');
    const chevron = document.getElementById('capsule-share-chevron');
    const isHidden = block.classList.toggle('hidden');
    chevron.style.transform = isHidden ? '' : 'rotate(90deg)';
    if (!isHidden) {
        // met à jour le label avec le nombre d'amis sélectionnés
        updateCapsuleShareLabel();
    }
}

function updateCapsuleShareLabel() {
    const checked = document.querySelectorAll('input[name="capsule_share_ids[]"]:checked').length;
    const label   = document.getElementById('capsule-share-label');
    label.textContent = checked > 0
        ? checked + ' ami' + (checked > 1 ? 's' : '') + ' sélectionné' + (checked > 1 ? 's' : '')
        : 'Partager avec des amis';
}

document.querySelectorAll('input[name="capsule_share_ids[]"]').forEach(cb => {
    cb.addEventListener('change', updateCapsuleShareLabel);
});
</script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>