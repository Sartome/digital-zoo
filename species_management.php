<?php
require_once 'config.php';

if(!isLoggedIn() || !isDirector()) {
    redirect('login.php', 'Accès non autorisé.', 'error');
}

// Get species statistics
$sql = "SELECT s.*, COUNT(a.id) as animal_count 
        FROM species s 
        LEFT JOIN animals a ON s.id = a.species_id 
        GROUP BY s.id";
$result = mysqli_query($conn, $sql);
$species = [];
while($row = mysqli_fetch_assoc($result)) {
    $species[] = $row;
}

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_species':
                // Add species logic
                break;
            case 'edit_species':
                // Edit species logic
                break;
            case 'delete_species':
                // Delete species logic
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Espèces - Zoo Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Same header/nav as director_dashboard.php but with species_management.php active -->
    
    <main class="dashboard">
        <h1>Gestion des Espèces</h1>
        
        <div class="dashboard-stats">
            <!-- Species statistics -->
        </div>
        
        <div class="dashboard-container">
            <!-- Add/Edit Species Form -->
            <!-- Species List -->
            <!-- Species Details -->
        </div>
    </main>
</body>
</html>
