<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer tous les transports
if ($method === 'GET' && !isset($_GET['id'])) {
    $sql = "SELECT * FROM transports WHERE 1=1";

    if (isset($_GET['ville_depart'])) {
        $depart = $_GET['ville_depart'];
        $sql .= " AND ville_depart LIKE '%$depart%'";
    }

    if (isset($_GET['ville_arrivee'])) {
        $arrivee = $_GET['ville_arrivee'];
        $sql .= " AND ville_arrivee LIKE '%$arrivee%'";
    }

    if (isset($_GET['date_depart'])) {
        $date = $_GET['date_depart'];
        $sql .= " AND DATE(date_depart) = '$date'";
    }

    if (isset($_GET['type'])) {
        $type = $_GET['type'];
        $sql .= " AND type = '$type'";
    }

    // Seulement les transports avec des places disponibles
    $sql .= " AND places_dispo > 0";
    $sql .= " ORDER BY prix ASC";

    $result = mysqli_query($conn, $sql);
    $transports = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $transports[] = $row;
    }

    echo json_encode(["success" => true, "data" => $transports]);
}

// GET - Un transport par ID
if ($method === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM transports WHERE id_transport = $id";
    $result = mysqli_query($conn, $sql);
    $transport = mysqli_fetch_assoc($result);

    if ($transport) {
        echo json_encode(["success" => true, "data" => $transport]);
    } else {
        echo json_encode(["success" => false, "message" => "Transport non trouvé"]);
    }
}

// POST - Ajouter un transport
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $type = $data['type'];
    $compagnie = $data['compagnie'] ?? '';
    $ville_depart = $data['ville_depart'];
    $ville_arrivee = $data['ville_arrivee'];
    $date_depart = $data['date_depart'];
    $date_arrivee = $data['date_arrivee'];
    $prix = $data['prix'];
    $places_dispo = $data['places_dispo'];
    $classe = $data['classe'] ?? 'Economique';

    // Vérifier que date depart est avant date arrivee
    if (strtotime($date_depart) >= strtotime($date_arrivee)) {
        echo json_encode([
            "success" => false,
            "message" => "La date de départ doit être avant la date d'arrivée"
        ]);
        exit;
    }

    $sql = "INSERT INTO transports 
            (type, compagnie, ville_depart, ville_arrivee, date_depart, date_arrivee, prix, places_dispo, classe)
            VALUES 
            ('$type', '$compagnie', '$ville_depart', '$ville_arrivee', '$date_depart', '$date_arrivee', $prix, $places_dispo, '$classe')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            "success" => true,
            "message" => "Transport ajouté",
            "id" => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}

// DELETE - Supprimer un transport
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM transports WHERE id_transport = $id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Transport supprimé"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}
?>