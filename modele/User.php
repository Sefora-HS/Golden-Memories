<?php
// modele/User.php
class User {
    public static function getById($bdd, $id) {
        $stmt = $bdd->prepare("SELECT id, username, picture FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>