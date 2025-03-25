<?php
require_once 'config.php';

// Check if the user is logged in
if(!isLoggedIn()) {
    redirect('login.php', 'Veuillez vous connecter pour accéder à cette page.', 'error');
}

// Get species list with animal counts
$species = [];
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if(!empty($search)) {
    $sql = "SELECT s.*, COUNT(a.id) as animal_count 
            FROM species s 
            LEFT JOIN animals a ON s.id = a.species_id 
            WHERE s.name LIKE ? 
            GROUP BY s.id 
            ORDER BY s.name";
    $stmt = mysqli_prepare($conn, $sql);
    $search_term = "%$search%";
    mysqli_stmt_bind_param($stmt, "s", $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $species = [];
    while($row = mysqli_fetch_assoc($result)) {
        $species[] = $row;
    }
} else {
    $sql = "SELECT s.*, COUNT(a.id) as animal_count 
            FROM species s 
            LEFT JOIN animals a ON s.id = a.species_id 
            GROUP BY s.id 
            ORDER BY s.name";
    $result = mysqli_query($conn, $sql);
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $species[] = $row;
        }
    }
}

// View animals of a specific species
$selected_species = null;
$species_animals = [];
if(isset($_GET['species_id']) && is_numeric($_GET['species_id'])) {
    $species_id = intval($_GET['species_id']);
    
    // Get species details
    $sql = "SELECT * FROM species WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $species_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($result && mysqli_num_rows($result) > 0) {
        $selected_species = mysqli_fetch_assoc($result);
        
        // Get animals of this species
        $sql = "SELECT * FROM animals WHERE species_id = ? ORDER BY name";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $species_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $species_animals[] = $row;
        }
    }
}

// Gestion de la modification d'espèce
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'edit_species') {
            $species_id = intval($_POST['species_id']);
            $species_name = sanitize($_POST['species_name']);
            
            $sql = "UPDATE species SET name = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $species_name, $species_id);
            
            if(mysqli_stmt_execute($stmt)) {
                redirect('species_list.php', 'Espèce modifiée avec succès!', 'success');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Espèces - Zoo Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-paw"></i> Zoo Management
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="employee_dashboard.php"><i class="fas fa-paw"></i> Gestion des Animaux</a>
                <a href="species_list.php" class="active"><i class="fas fa-dna"></i> Liste des Espèces</a>
                <?php if(isDirector()): ?>
                    <a href="director_dashboard.php"><i class="fas fa-users"></i> Gestion des Employés</a>
                    <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistiques</a>
                <?php endif; ?>
                <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> 
                    <?php echo htmlspecialchars($_SESSION["username"]); ?> 
                    (<?php echo isDirector() ? 'Directeur' : 'Salarié'; ?>)
                </span>
            </div>
        </nav>
    </header>

    <main>
        <h1>Liste des Espèces</h1>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert <?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-container">
            <!-- Ajouter le formulaire de recherche -->
            <div class="search-box">
                <form method="get" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher une espèce..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </form>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-dna"></i> Espèces</h2>
                    <span class="count-badge"><?php echo count($species); ?> espèce(s)</span>
                </div>
                <div class="card-body">
                    <?php if(count($species) > 0): ?>
                        <div class="species-list">
                            <?php foreach($species as $s): ?>
                                <div class="species-item">
                                    <div class="species-details">
                                        <h3><?php echo htmlspecialchars($s['name']); ?></h3>
                                        <p><i class="fas fa-paw"></i> <?php echo $s['animal_count']; ?> animal(aux)</p>
                                    </div>
                                    <div class="species-actions">
                                        <button class="btn btn-edit" onclick="editSpecies(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['name']); ?>')">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <a href="?species_id=<?php echo $s['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> Voir les animaux
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Aucune espèce trouvée.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($selected_species): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-paw"></i> Animaux de l'espèce: <?php echo htmlspecialchars($selected_species['name']); ?></h2>
                        <span class="count-badge"><?php echo count($species_animals); ?> animal(aux)</span>
                    </div>
                    <div class="card-body">
                        <?php if(count($species_animals) > 0): ?>
                            <div class="animals-grid">
                                <?php foreach($species_animals as $animal): ?>
                                    <div class="animal-card">
                                        <div class="animal-icon">
                                            <i class="fas fa-paw"></i>
                                        </div>
                                        <div class="animal-details">
                                            <h3><?php echo $animal['name'] ? htmlspecialchars($animal['name']) : '<em>Sans nom</em>'; ?></h3>
                                            <p>ID: <?php echo $animal['id']; ?></p>
                                            <p>Ajouté le: <?php echo date('d/m/Y', strtotime($animal['created_at'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">Aucun animal trouvé pour cette espèce.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Ajouter le modal de modification -->
    <div id="edit-species-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-edit"></i> Modifier l'espèce</h3>
            <form method="post" class="form">
                <input type="hidden" name="action" value="edit_species">
                <input type="hidden" name="species_id" id="edit-species-id">
                <div class="form-group">
                    <label>Nom de l'espèce</label>
                    <input type="text" name="species_name" id="edit-species-name" required>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
    </footer>

    <script>
        // Script pour gérer le modal de modification
        function editSpecies(id, name) {
            document.getElementById('edit-species-id').value = id;
            document.getElementById('edit-species-name').value = name;
            document.getElementById('edit-species-modal').style.display = 'block';
        }

        // Fermer le modal
        document.querySelector('.modal .close').onclick = function() {
            document.getElementById('edit-species-modal').style.display = 'none';
        }

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('edit-species-modal')) {
                document.getElementById('edit-species-modal').style.display = 'none';
            }
        }
    </script>
</body>
</html>
