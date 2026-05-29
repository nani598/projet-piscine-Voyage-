<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Méthode non autorisée"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Vérification des champs obligatoires
if (empty($data['nom']) || empty($data['prenom']) || 
    empty($data['email']) || empty($data['mot_de_passe'])) {
    http_response_code(400);
    echo json_encode(["error" => "Tous les champs obligatoires doivent être remplis"]);
    exit();
}

// Validation email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Email invalide"]);
    exit();
}

// Validation mot de passe
if (strlen($data['mot_de_passe']) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "Le mot de passe doit contenir au moins 6 caractères"]);
    exit();
}

try {
    $pdo = getConnection();

    // Vérifier si email existe déjà
    $stmt = $pdo->prepare("SELECT id_utilisateur FROM UTILISATEUR WHERE email = ?");
    $stmt->execute([$data['email']]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["error" => "Cet email est déjà utilisé"]);
        exit();
    }

    // Hasher le mot de passe
    $mot_de_passe_hash = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);

    // Insérer l'utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO UTILISATEUR (nom, prenom, email, mot_de_passe, telephone, role)
        VALUES (?, ?, ?, ?, ?, 'voyageur')
    ");
    
    $stmt->execute([
        $data['nom'],
        $data['prenom'],
        $data['email'],
        $mot_de_passe_hash,
        $data['telephone'] ?? null
    ]);

    $id = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        "message" => "Compte créé avec succès",
        "id_utilisateur" => $id,
        "nom" => $data['nom'],
        "prenom" => $data['prenom'],
        "email" => $data['email'],
        "role" => "voyageur"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur serveur : " . $e->getMessage()]);
}
?>
