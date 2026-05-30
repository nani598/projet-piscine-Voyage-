<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer tous les hebergements
if ($method === 'GET' && !isset($_GET['id'])) {
    $sql = "SELECT h.*, d.nom as destination_nom, d.pays 
            FROM hebergements h 
            LEFT JOIN destinations d ON h.id_destination = d.id_destination
            WHERE 1=1";

    if (isset($_GET['id_destination'])) {
        $id_dest = $_GET['id_destination'];
        $sql .= " AND h.id_destination = $id_dest";
    }

    if (isset($_GET['prix_max'])) {
        $prix_max = $_GET['prix_max'];
        $sql .= " AND h.prix_nuit <= $prix_max";
    }

    if (isset($_GET['nb_etoiles'])) {
        $etoiles = $_GET['nb_etoiles'];
        $sql .= " AND h.nb_etoiles >= $etoiles";
    }

    $result = mysqli_query($conn, $sql);
    $hebergements = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $hebergements[] = $row;
    }

    echo json_encode(["success" => true, "data" => $hebergements]);
}

// GET - Un hebergement par ID
if ($method === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT h.*, d.nom as destination_nom, d.pays
            FROM hebergements h
            LEFT JOIN destinations d ON h.id_destination = d.id_destination
            WHERE h.id_hebergement = $id";

    $result = mysqli_query($conn, $sql);
    $hebergement = mysqli_fetch_assoc($result);

    if ($hebergement) {
        // Récupérer les disponibilités
        $dispo_sql = "SELECT * FROM disponibilites 
                      WHERE id_hebergement = $id 
                      AND statut = 'disponible'
                      AND date_fin >= CURDATE()";
        $dispo_result = mysqli_query($conn, $dispo_sql);
        $disponibilites = [];
        while ($dispo = mysqli_fetch_assoc($dispo_result)) {
            $disponibilites[] = $dispo;
        }
        $hebergement['disponibilites'] = $disponibilites;

        echo json_encode(["success" => true, "data" => $hebergement]);
    } else {
        echo json_encode(["success" => false, "message" => "Hébergement non trouvé"]);
    }
}

// POST - Ajouter un hebergement
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $nom = $data['nom'];
    $type = $data['type'] ?? '';
    $prix_nuit = $data['prix_nuit'];
    $capacite = $data['capacite'] ?? 1;
    $nb_etoiles = $data['nb_etoiles'] ?? 0;
    $equipements = $data['equipements'] ?? '';
    $id_destination = $data['id_destination'];
    $id_proprietaire = $data['id_proprietaire'];

    $sql = "INSERT INTO hebergements 
            (nom, type, prix_nuit, capacite, nb_etoiles, equipements, id_destination, id_proprietaire)
            VALUES 
            ('$nom', '$type', $prix_nuit, $capacite, $nb_etoiles, '$equipements', $id_destination, $id_proprietaire)";

    if (mysqli_query($conn, $sql)) {
        $id = mysqli_insert_id($conn);

        // Ajouter disponibilité par défaut
        if (isset($data['date_debut']) && isset($data['date_fin'])) {
            $date_debut = $data['date_debut'];
            $date_fin = $data['date_fin'];
            $dispo_sql = "INSERT INTO disponibilites (date_debut, date_fin, id_hebergement)
                         VALUES ('$date_debut', '$date_fin', $id)";
            mysqli_query($conn, $dispo_sql);
        }

        echo json_encode([
            "success" => true,
            "message" => "Hébergement ajouté",
            "id" => $id
        ]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}

// DELETE - Supprimer un hebergement
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM hebergements WHERE id_hebergement = $id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Hébergement supprimé"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}
?>
