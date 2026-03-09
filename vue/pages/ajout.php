<?php
require_once '../../modele/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/vue/pages/home.php');
    exit;
}

$userConnecte = $_SESSION['user'];
$userId = $userConnecte['id'];

$success = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type    = $_POST['type'] ?? '';
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    $allowedTypes = ['photo', 'video', 'audio', 'note'];

    if (!in_array($type, $allowedTypes)) {
        $erreur = 'Type de souvenir invalide.';
    } elseif ($type === 'note' && empty($content)) {
        $erreur = 'Le contenu de la note est obligatoire.';
    } elseif ($type !== 'note' && empty($_FILES['file']['name'])) {
        $erreur = 'Veuillez sélectionner un fichier.';
    } else {
        $filePath = null;

        if ($type !== 'note') {
            $file     = $_FILES['file'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = [
                'photo' => ['jpg','jpeg','png','gif','webp','jfif'],
                'video' => ['mp4','mov','avi','webm'],
                'audio' => ['mp3','wav','ogg','m4a'],
            ];

            if (!in_array($ext, $allowed[$type])) {
                $erreur = 'Format de fichier non supporté.';
            } elseif ($file['size'] > 1000 * 1024 * 1024) {
                $erreur = 'Fichier trop lourd (max 50 Mo).';
            } else {
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
            $stmt = $bdd->prepare("
                INSERT INTO memories (user_id, type, title, file_path, content)
                VALUES (:uid, :type, :title, :file_path, :content)
            ");
            $stmt->execute([
                ':uid'       => $userId,
                ':type'      => $type,
                ':title'     => $title ?: null,
                ':file_path' => $filePath,
                ':content'   => $content ?: null,
            ]);
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
    <link rel="stylesheet" href="../assets/css/app.css">
    <title>Golden Memories — Ajouter</title>
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

    <!-- Sélecteur de type -->
    <div class="type-grid" id="type-grid">
        <button class="type-card" data-type="photo" onclick="selectType('photo')">
            <div class="type-icon">
                <ion-icon name="image-outline"></ion-icon>
            </div>
            <span>Photo</span>
        </button>
        <button class="type-card" data-type="video" onclick="selectType('video')">
            <div class="type-icon">
                <ion-icon name="videocam-outline"></ion-icon>
            </div>
            <span>Vidéo</span>
        </button>
        <button class="type-card" data-type="audio" onclick="selectType('audio')">
            <div class="type-icon">
                <ion-icon name="musical-notes-outline"></ion-icon>
            </div>
            <span>Audio</span>
        </button>
        <button class="type-card" data-type="note" onclick="selectType('note')">
            <div class="type-icon">
                <ion-icon name="document-text-outline"></ion-icon>
            </div>
            <span>Note</span>
        </button>
    </div>

    <!-- Formulaire (caché jusqu'à sélection) -->
    <form method="POST" enctype="multipart/form-data" id="ajout-form" class="ajout-form hidden">
        <input type="hidden" name="type" id="input-type">

        <!-- Titre (optionnel) -->
        <div class="form-group">
            <label>Titre <span class="optional">(optionnel)</span></label>
            <input type="text" name="title" placeholder="Donnez un titre à ce souvenir" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <!-- Zone fichier (photo / video / audio) -->
        <div id="file-zone" class="file-zone hidden">
            <label class="file-drop" id="file-label" for="file-input">
                <ion-icon name="cloud-upload-outline" id="upload-icon"></ion-icon>
                <span id="upload-text">Appuyez pour importer</span>
                <span id="file-name" class="file-name"></span>
            </label>
            <input type="file" name="file" id="file-input" class="hidden" onchange="previewFile(this)">
        </div>

        <!-- Zone note -->
        <div id="note-zone" class="form-group hidden">
            <label>Votre note</label>
            <textarea name="content" id="note-content" class="note-textarea" placeholder="Écrivez votre souvenir ici…" rows="6"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>

        <!-- Prévisualisation -->
        <div id="preview-zone" class="preview-zone hidden"></div>

        <button type="submit" class="btn-primary ajout-submit">
            <ion-icon name="checkmark-outline"></ion-icon>
            Enregistrer le souvenir
        </button>
    </form>

</div>

<?php include './templates/navbar.php'; ?>

<script>
const acceptMap = {
    photo: 'image/*',
    video: 'video/*',
    audio: 'audio/*',
};

const iconMap = {
    photo: 'image-outline',
    video: 'videocam-outline',
    audio: 'musical-notes-outline',
    note:  'document-text-outline',
};

function selectType(type) {
    // Highlight sélection
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');

    document.getElementById('input-type').value = type;

    const form      = document.getElementById('ajout-form');
    const fileZone  = document.getElementById('file-zone');
    const noteZone  = document.getElementById('note-zone');
    const preview   = document.getElementById('preview-zone');
    const fileInput = document.getElementById('file-input');

    form.classList.remove('hidden');
    preview.classList.add('hidden');
    preview.innerHTML = '';

    if (type === 'note') {
        fileZone.classList.add('hidden');
        noteZone.classList.remove('hidden');
        fileInput.removeAttribute('name');
    } else {
        noteZone.classList.add('hidden');
        fileZone.classList.remove('hidden');
        fileInput.setAttribute('name', 'file');
        fileInput.setAttribute('accept', acceptMap[type]);

        const icon = document.getElementById('upload-icon');
        icon.setAttribute('name', iconMap[type]);

        const labels = {
            photo: 'Importer une photo',
            video: 'Importer une vidéo',
            audio: 'Importer un audio',
        };
        document.getElementById('upload-text').textContent = labels[type];
        document.getElementById('file-name').textContent = '';
    }

    // Scroll vers le formulaire
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function previewFile(input) {
    if (!input.files || !input.files[0]) return;
    const file    = input.files[0];
    const type    = document.getElementById('input-type').value;
    const preview = document.getElementById('preview-zone');
    const url     = URL.createObjectURL(file);

    document.getElementById('file-name').textContent = file.name;

    preview.innerHTML = '';
    preview.classList.remove('hidden');

    if (type === 'photo') {
        const img = document.createElement('img');
        img.src = url;
        img.className = 'preview-img';
        preview.appendChild(img);
    } else if (type === 'video') {
        const vid = document.createElement('video');
        vid.src = url;
        vid.controls = true;
        vid.className = 'preview-video';
        preview.appendChild(vid);
    } else if (type === 'audio') {
        const aud = document.createElement('audio');
        aud.src = url;
        aud.controls = true;
        aud.className = 'preview-audio';
        preview.appendChild(aud);
    }
}

// Clic sur la zone = ouvre le file picker
document.getElementById('file-label').addEventListener('click', () => {
    document.getElementById('file-input').click();
});
</script>

<style>
.ajout-page {
    padding: 0 1.5rem 120px;
    display: flex;
    flex-direction: column;
    gap: 1.8rem;
}

/* Grille de sélection */
.type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.type-card {
    background: #fff;
    border: 2px solid transparent;
    border-radius: 20px;
    padding: 1.6rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: border-color .2s, transform .15s, box-shadow .2s;
    box-shadow: 0 2px 12px rgba(255, 221, 87, 0.15);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #2c2c2c;
}

.type-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 20px rgba(255, 221, 87, 0.35);
}

.type-card.selected {
    border-color: #f6e7b4;
    background: #fffdf0;
    box-shadow: 0 4px 20px rgba(255, 221, 87, 0.4);
}

.type-icon {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    background: #f6e7b4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #2c2c2c;
    transition: transform .2s;
}

.type-card.selected .type-icon {
    background: #2c2c2c;
    color: #f6e7b4;
    transform: scale(1.05);
}

/* Formulaire */
.ajout-form {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    animation: fadeSlideUp .3s ease;
}

.ajout-form.hidden {
    display: none;
}

@keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

.hidden { display: none !important; }

.optional {
    opacity: .45;
    font-size: .8rem;
}

/* Zone d'upload */
.file-zone { display: flex; flex-direction: column; gap: .5rem; }

.file-drop {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #fff;
    border: 2px dashed #f6e7b4;
    border-radius: 20px;
    padding: 2.5rem 1rem;
    cursor: pointer;
    color: #2c2c2c;
    font-size: 1rem;
    transition: background .2s, border-color .2s;
}

.file-drop:hover {
    background: #fffdf0;
    border-color: #e0c84a;
}

.file-drop ion-icon {
    font-size: 2.5rem;
    color: #2c2c2c;
    opacity: .5;
}

.file-name {
    font-size: .8rem;
    opacity: .5;
    max-width: 90%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Note textarea */
.note-textarea {
    background: #fff;
    border: none;
    border-radius: 16px;
    padding: 1rem;
    font-size: 1.1rem;
    font-family: 'Caveat', cursive;
    color: #2c2c2c;
    outline: none;
    resize: none;
    box-shadow: 0 0 10px rgba(255, 221, 87, 0.2);
    line-height: 1.6;
    min-height: 160px;
}

.note-textarea:focus {
    box-shadow: 0 0 0 2px #f6e7b4;
}

.note-textarea::placeholder { color: #ccc; }

/* Prévisualisation */
.preview-zone {
    border-radius: 16px;
    overflow: hidden;
    background: #1a1a2e;
}

.preview-img, .preview-video {
    width: 100%;
    max-height: 320px;
    object-fit: cover;
    display: block;
}

.preview-audio {
    width: 100%;
    padding: 12px;
}

/* Bouton submit */
.ajout-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    margin-top: .4rem;
}

.ajout-submit ion-icon { font-size: 1.2rem; }

/* Erreur */
.form-error {
    background: #ffe0e0;
    color: #c0392b;
    padding: .8rem 1rem;
    border-radius: 12px;
    font-size: .9rem;
}
</style>

<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>