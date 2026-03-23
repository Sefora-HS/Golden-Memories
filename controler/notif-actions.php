<?php
require_once '../modele/config.php';

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$userId = $_SESSION['user']['id'];

// Récupère les données JSON envoyées
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'], $data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

$notifId = intval($data['id']);
$action = $data['action'];

try {
    if ($action === 'delete') {
        // Supprime la notification
        $stmt = $bdd->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $notifId, ':uid' => $userId]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'mark_read') {
        // Marque la notification comme lue
        $stmt = $bdd->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $notifId, ':uid' => $userId]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}