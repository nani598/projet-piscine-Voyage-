<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// INSCRIPTION
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'inscription') {
    $nom = $data['nom'];
    $prenom = $data['prenom'];
    $email = $data['email'];
    $mot_de_passe = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
    $role = $data['role'] ?? 'voyageur';

    $check = mysqli_query($conn, "SELECT id_utilisateur FROM utilisateurs WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(["success" => false, "message" => "Email déjà utilisé"]);
        exit;
    }

    $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) 
            VALUES ('$nom', '$prenom', '$email', '$mot_de_passe', '$role')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Compte créé avec succès"]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur : " . mysqli_error($conn)]);
    }
}

// CONNEXION
if ($method === 'POST' && isset($data['action']) && $data['action'] === 'connexion') {
    $email = $data['email'];
    $mot_de_passe = $data['mot_de_passe'];

    $sql = "SELECT * FROM utilisateurs WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) === 0) {
        echo json_encode(["success" => false, "message" => "Email ou mot de passe incorrect"]);
        exit;
    }

    $user = mysqli_fetch_assoc($result);

    if (!password_verify($mot_de_passe, $user['mot_de_passe'])) {
        echo json_encode(["success" => false, "message" => "Email ou mot de passe incorrect"]);
        exit;
    }

    // Génération du token JWT simple
    $token = base64_encode(json_encode([
        "id" => $user['id_utilisateur'],
        "email" => $user['email'],
        "role" => $user['role'],
        "expire" => time() + 86400
    ]));

    echo json_encode([
        "success" => true,
        "token" => $token,
        "user" => [
            "id" => $user['id_utilisateur'],
            "nom" => $user['nom'],
            "prenom" => $user['prenom'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);
}
// PUT - Modifier le profil
if ($method === 'PUT' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $nom = $data['nom'];
    $prenom = $data['prenom'];
    $email = $data['email'];
    $telephone = $data['telephone'] ?? '';

    $sql = "UPDATE utilisateurs SET nom='$nom', prenom='$prenom', email='$email', telephone='$telephone'
            WHERE id_utilisateur=$id";
    if (mysqli_query($conn, $sql)) {
        $result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM utilisateurs WHERE id_utilisateur=$id"));
        echo json_encode(["success" => true, "user" => $result]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}
?>