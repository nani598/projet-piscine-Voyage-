<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Sejours d'un utilisateur
if ($method === 'GET' && isset($_GET['id_utilisateur'])) {
    $id = $_GET['id_utilisateur'];
    $sql = "SELECT s.*, 
                COUNT(DISTINCT pt.id_transport) as nb_transports,
                COUNT(DISTINCT ph.id_hebergement) as nb_hebergements,
                COUNT(DISTINCT pa.id_activite) as nb_activites
            FROM sejours s
            LEFT JOIN panier p ON p.id_sejour = s.id_sejour
            LEFT JOIN panier_transport pt ON pt.id_panier = p.id_panier
            LEFT JOIN panier_hebergement ph ON ph.id_panier = p.id_panier
            LEFT JOIN panier_activite pa ON pa.id_panier = p.id_panier
            WHERE s.id_utilisateur = $id
            GROUP BY s.id_sejour
            ORDER BY s.date_debut DESC";
    $result = mysqli_query($conn, $sql);
    $sejours = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sejours[] = $row;
    }
    echo json_encode(["success" => true, "data" => $sejours]);
}

// POST - Creer un sejour
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $titre = $data['titre'];
    $date_debut = $data['date_debut'];
    $date_fin = $data['date_fin'];
    $nb_voyageurs = $data['nb_voyageurs'] ?? 1;
    $id_utilisateur = $data['id_utilisateur'];

    if (strtotime($date_debut) >= strtotime($date_fin)) {
        echo json_encode(["success" => false, "message" => "La date de début doit être avant la date de fin"]);
        exit;
    }

    $sql = "INSERT INTO sejours (titre, date_debut, date_fin, nb_voyageurs, statut, id_utilisateur)
            VALUES ('$titre', '$date_debut', '$date_fin', $nb_voyageurs, 'brouillon', $id_utilisateur)";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "id" => mysqli_insert_id($conn), "message" => "Séjour créé"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}

// PUT - Modifier statut ou voyageurs
if ($method === 'PUT' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['statut'])) {
        $statut = $data['statut'];
        mysqli_query($conn, "UPDATE sejours SET statut='$statut' WHERE id_sejour=$id");
    }
    if (isset($data['nb_voyageurs'])) {
        $nb = $data['nb_voyageurs'];
        mysqli_query($conn, "UPDATE sejours SET nb_voyageurs=$nb WHERE id_sejour=$id");
    }
    echo json_encode(["success" => true, "message" => "Séjour mis à jour"]);
}

// DELETE - Supprimer un sejour
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Libérer les ressources liées
    $panier = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_panier FROM panier WHERE id_sejour=$id"));
    if ($panier) {
        $pid = $panier['id_panier'];
        mysqli_query($conn, "DELETE FROM panier_activite WHERE id_panier=$pid");
        mysqli_query($conn, "DELETE FROM panier_hebergement WHERE id_panier=$pid");
        mysqli_query($conn, "DELETE FROM panier_transport WHERE id_panier=$pid");
        mysqli_query($conn, "DELETE FROM panier WHERE id_panier=$pid");
    }
    mysqli_query($conn, "DELETE FROM sejours WHERE id_sejour=$id");
    echo json_encode(["success" => true, "message" => "Séjour supprimé"]);
}
?>