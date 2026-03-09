<?php
require_once __DIR__ . '/../modele/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

$userId = $_SESSION['user']['id'];

// GET — récupérer les commentaires
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $memoryId = (int)($_GET['memory_id'] ?? 0);
    if (!$memoryId) { echo json_encode([]); exit; }

    $stmt = $bdd->prepare("
        SELECT c.content, c.created_at, u.username, u.picture
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.memory_id = :mid
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([':mid' => $memoryId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($comments as &$c) {
        $c['created_at'] = (new DateTime($c['created_at']))->format('d/m/Y à H:i');
    }

    echo json_encode($comments);
    exit;
}

// POST — ajouter un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data     = json_decode(file_get_contents('php://input'), true);
    $memoryId = (int)($data['memory_id'] ?? 0);
    $content  = trim($data['content'] ?? '');

    if (!$memoryId || !$content) {
        echo json_encode(['success' => false, 'error' => 'Données manquantes']);
        exit;
    }

    $stmt = $bdd->prepare("INSERT INTO comments (user_id, memory_id, content) VALUES (:uid, :mid, :content)");
    $stmt->execute([':uid' => $userId, ':mid' => $memoryId, ':content' => $content]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Méthode non supportée']);