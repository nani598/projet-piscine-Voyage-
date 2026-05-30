<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer toutes les activites
if ($method === 'GET' && !isset($_GET['id'])) {
    $sql = "SELECT a.*, d.nom as destination_nom 
            FROM activites a 
            LEFT JOIN destinations d ON a.id_destination = d.id_destination
            WHERE 1=1";

    if (isset($_GET['id_destination'])) {
        $id_dest = $_GET['id_destination'];
        $sql .= " AND a.id_destination = $id_dest";
    }

    if (isset($_GET['type'])) {
        $type = $_GET['type'];
        $sql .= " AND a.type = '$type'";
    }

    if (isset($_GET['prix_max'])) {
        $prix_max = $_GET['prix_max'];
        $sql .= " AND a.prix <= $prix_max";
    }

    $sql .= " ORDER BY a.prix ASC";

    $result = mysqli_query($conn, $sql);
    $activites = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $activites[] = $row;
    }

    echo json_encode(["success" => true, "data" => $activites]);
}

// GET - Une activite par ID
if ($method === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT a.*, d.nom as destination_nom
            FROM activites a
            LEFT JOIN destinations d ON a.id_destination = d.id_destination
            WHERE a.id_activite = $id";

    $result = mysqli_query($conn, $sql);
    $activite = mysqli_fetch_assoc($result);

    if ($activite) {
        // Compter les inscrits
        $inscrits_sql = "SELECT COUNT(*) as nb_inscrits 
                         FROM panier_activite pa
                         LEFT JOIN panier p ON pa.id_panier = p.id_panier
                         WHERE pa.id_activite = $id
                         AND p.statut = 'confirme'";
        $inscrits_result = mysqli_query($conn, $inscrits_sql);
        $inscrits = mysqli_fetch_assoc($inscrits_result);
        $activite['nb_inscrits'] = $inscrits['nb_inscrits'];
        $activite['places_restantes'] = $activite['capacite_max'] - $inscrits['nb_inscrits'];

        echo json_encode(["success" => true, "data" => $activite]);
    } else {
        echo json_encode(["success" => false, "message" => "Activité non trouvée"]);
    }
}

// POST - Ajouter une activite
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $nom = $data['nom'];
    $type = $data['type'] ?? '';
    $duree_heures = $data['duree_heures'] ?? 1;
    $prix = $data['prix'];
    $capacite_max = $data['capacite_max'];
    $id_destination = $data['id_destination'];
    $id_organisateur = $data['id_organisateur'];

    $sql = "INSERT INTO activites 
            (nom, type, duree_heures, prix, capacite_max, id_destination, id_organisateur)
            VALUES 
            ('$nom', '$type', $duree_heures, $prix, $capacite_max, $id_destination, $id_organisateur)";

    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            "success" => true,
            "message" => "Activité ajoutée",
            "id" => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}

// DELETE - Supprimer une activite
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // Vérifier si l'activite a des inscrits
    $check = mysqli_query($conn, "SELECT COUNT(*) as nb FROM panier_activite WHERE id_activite = $id");
    $result = mysqli_fetch_assoc($check);

    if ($result['nb'] > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Impossible de supprimer une activité avec des inscrits"
        ]);
        exit;
    }

    $sql = "DELETE FROM activites WHERE id_activite = $id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Activité supprimée"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}
?>
