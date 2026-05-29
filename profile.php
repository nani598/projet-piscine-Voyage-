<?php
require_once '../../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
    exit();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorisé, veuillez vous connecter"]);
    exit();
}

try {
    $pdo = getConnection();

    $stmt = $pdo->prepare("
        SELECT id_utilisateur, nom, prenom, email, 
               telephone, role, date_inscription 
        FROM UTILISATEUR 
        WHERE id_utilisateur = ?
    ");
    $stmt->execute([$_SESSION['id_utilisateur']]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur) {
        http_response_code(404);
        echo json_encode(["error" => "Utilisateur non trouvé"]);
        exit();
    }

    http_response_code(200);
    echo json_encode($utilisateur);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
}
?>
