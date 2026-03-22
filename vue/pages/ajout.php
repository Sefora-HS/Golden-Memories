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
    $isCapsule     = isset($_POST['is_capsule']) && $_POST['is_capsule'] === '1';
    $capsuleTitle  = trim($_POST['capsule_title'] ?? '');
    $unlockAt      = trim($_POST['unlock_at'] ?? '');

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
            }

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

<!-- inclusion du template header -->
<?php include './templates/header.php'; ?>

<div class="ajout-page">

    <div class="form-top">
        <a href="<?= BASE_URL ?>/index.php" class="form-back">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
        <h1 class="form-title">Nouveau souvenir</h1>
    </div>

    <!-- affichage des erreurs ici si il y'en a une  -->
    <?php if ($erreur): ?>
        <p class="form-error"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <!-- boutons pour la création de souvenir -->
    <div class="type-grid" id="type-grid">
        <!-- photos -->
        <button class="type-card" data-type="photo" onclick="selectType('photo')">
            <div class="type-icon"><ion-icon name="image-outline"></ion-icon></div>
            <span>Photo</span>
        </button>
        <!-- videos -->
        <button class="type-card" data-type="video" onclick="selectType('video')">
            <div class="type-icon"><ion-icon name="videocam-outline"></ion-icon></div>
            <span>Vidéo</span>
        </button>
        <!-- audio -->
        <button class="type-card" data-type="audio" onclick="selectType('audio')">
            <div class="type-icon"><ion-icon name="musical-notes-outline"></ion-icon></div>
            <span>Audio</span>
        </button>
        <!-- note -->
        <button class="type-card" data-type="note" onclick="selectType('note')">
            <div class="type-icon"><ion-icon name="document-text-outline"></ion-icon></div>
            <span>Note</span>
        </button>
    </div>

    <!-- formulaire pour la création de souvenir caché par default -->
    <form method="POST" enctype="multipart/form-data" id="ajout-form" class="ajout-form hidden">
        <input type="hidden" name="type" id="input-type">

        <!-- titre du souvenir optionnel -->
        <div class="form-group">
            <label>Titre <span class="optional">(optionnel)</span></label>
            <input type="text" name="title" placeholder="Donnez un titre à ce souvenir" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <!-- zone de fichier , pour pouvoir importer caché par default-->
        <div id="file-zone" class="file-zone hidden">
            <label class="file-drop" id="file-label" for="file-input">
                <ion-icon name="cloud-upload-outline" id="upload-icon"></ion-icon>
                <span id="upload-text">Appuyez pour importer</span>
                <span id="file-name" class="file-name"></span>
            </label>
            <input type="file" name="file" id="file-input" class="hidden" onchange="previewFile(this)">
        </div>

        <!-- zone pour inscrire une note caché par default -->
        <div id="note-zone" class="form-group hidden">
            <label>Votre note</label>
            <textarea name="content" id="note-content" class="note-textarea" placeholder="Écrivez votre souvenir ici…" rows="6"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>

        <!-- zone de prévisualisation caché par default, disponible une fois le souvenir importé -->
        <div id="preview-zone" class="preview-zone hidden"></div>

        <!-- affichage et création des albums pour attribuer ou non un souvenir à un album -->
        <?php if (!empty($albums)): ?>
        <div class="extra-section">
            <!-- bouton qui permet d'ouvrir la div album-block -->
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
            <!-- affichage des albums deja crée, par default en surbriance "aucun album" -->
            <div id="album-block" class="extra-block hidden">
                <div class="album-list">
                    <label class="album-option">
                        <input type="radio" name="album_id" value="" onchange="updateAlbumSub(this)" checked>
                        <span class="album-option-content">
                            <ion-icon name="close-circle-outline"></ion-icon>
                            Aucun album
                        </span>
                    </label>
                    <!-- affichage des album de l'utilisateur -->
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
                <!-- création d'un album -->
                <div class="new-album-wrap">
                    <!-- nom -->
                    <input type="text" id="new-album-input" placeholder="Nom du nouvel album…" class="new-album-input">
                    <!-- toggle pour définir si l'album est partagé ou non, privé par défaut -->
                    <label class="new-album-share-toggle" id="share-toggle-label" title="Partager l'album">
                        <input type="checkbox" id="new-album-shared" style="display:none;">
                        <ion-icon name="people-outline" id="share-icon"></ion-icon>
                    </label>
                    <!-- bouton de création -->
                    <button type="button" onclick="createAlbum()" class="new-album-btn">
                        <ion-icon name="add-outline"></ion-icon>
                        Créer
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Zone pour créer une capsule temporelle -->
        <div class="extra-section">
            <!-- bouton toggle qui permet d'afficher le formulaire de création si selectionné, sur off par default -->
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

            <!-- div affiché si toggle sur on -->
            <div id="capsule-block" class="extra-block hidden">
                <input type="hidden" name="is_capsule" id="is-capsule-input" value="0">
                <div class="form-group" style="margin-bottom:.75rem;">
                    <!-- nom de la capsule -->
                    <label>Nom de la capsule <span class="optional">(optionnel)</span></label>
                    <input type="text" name="capsule_title" placeholder="Ex : Été 2025 🌸"
                           value="<?= htmlspecialchars($_POST['capsule_title'] ?? '') ?>">
                </div>
                <!-- selection de la date d'ouverture -->
                <div class="form-group">
                    <label>Date d'ouverture</label>
                    <input type="datetime-local" name="unlock_at" id="unlock-at"
                           min="<?= date('Y-m-d\TH:i') ?>"
                           value="<?= htmlspecialchars($_POST['unlock_at'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- bouton submit qui permet d'enregistrer le souvenir -->
        <button type="submit" class="btn-primary ajout-submit">
            <ion-icon name="checkmark-outline"></ion-icon>
            Enregistrer le souvenir
        </button>
    </form>

</div>

<!-- inclusion de la navbar -->
<?php include './templates/navbar.php'; ?>

<script src="<?= BASE_URL ?>/vue/assets/js/app.js?v=<?= time() ?>"></script>
<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>