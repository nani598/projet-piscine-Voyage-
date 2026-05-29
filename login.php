<?php
require_once '../../config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email']) || empty($data['mot_de_passe'])) {
    http_response_code(400);
    echo json_encode(["error" => "Email et mot de passe obligatoires"]);
    exit();
}

try {
    $pdo = getConnection();

    $stmt = $pdo->prepare("
        SELECT * FROM UTILISATEUR WHERE email = ?
    ");
    $stmt->execute([$data['email']]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur || !password_verify($data['mot_de_passe'], $utilisateur['mot_de_passe'])) {
        http_response_code(401);
        echo json_encode(["error" => "Email ou mot de passe incorrect"]);
        exit();
    }

    // Créer la session
    $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
    $_SESSION['role'] = $utilisateur['role'];
    $_SESSION['nom'] = $utilisateur['nom'];
    $_SESSION['prenom'] = $utilisateur['prenom'];

    http_response_code(200);
    echo json_encode([
        "message" => "Connexion réussie",
        "user" => [
            "id_utilisateur" => $utilisateur['id_utilisateur'],
            "nom" => $utilisateur['nom'],
            "prenom" => $utilisateur['prenom'],
            "email" => $utilisateur['email'],
            "telephone" => $utilisateur['telephone'],
            "role" => $utilisateur['role'],
            "date_inscription" => $utilisateur['date_inscription']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
}
?>
