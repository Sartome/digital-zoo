<?php
require_once 'config.php';

if(!isLoggedIn() || !isDirector()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Accès refusé');
}

if(isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $sql = "SELECT id, username, role, salary FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo 'Utilisateur non trouvé';
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'ID utilisateur manquant';
}
