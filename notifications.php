<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Récupérer les notifications d'un utilisateur
if ($method === 'GET' && isset($_GET['id_utilisateur'])) {
    $id_user = $_GET['id_utilisateur'];

    $sql = "SELECT * FROM notifications 
            WHERE id_utilisateur = $id_user 
            ORDER BY date_envoi DESC";

    $result = mysqli_query($conn, $sql);
    $notifications = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    // Compter les non lues
    $non_lues = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as nb FROM notifications 
         WHERE id_utilisateur = $id_user AND lu = 0"
    ));

    echo json_encode([
        "success" => true,
        "data" => $notifications,
        "non_lues" => $non_lues['nb']
    ]);
}

// PUT - Marquer une notification comme lue
if ($method === 'PUT' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "UPDATE notifications SET lu = 1 WHERE id_notification = $id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Notification marquée comme lue"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}

// PUT - Marquer toutes les notifications comme lues
if ($method === 'PUT' && isset($_GET['id_utilisateur']) && !isset($_GET['id'])) {
    $id_user = $_GET['id_utilisateur'];

    $sql = "UPDATE notifications SET lu = 1 WHERE id_utilisateur = $id_user";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Toutes les notifications marquées comme lues"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}

// DELETE - Supprimer une notification
if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM notifications WHERE id_notification = $id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true, "message" => "Notification supprimée"]);
    } else {
        echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
    }
}
?>
