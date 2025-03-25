<?php
require_once 'config.php';

if(!isLoggedIn()) {
    redirect('login.php', 'Veuillez vous connecter pour accéder à cette page.', 'error');
}

$species = [];
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

$animals = [];
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if(!empty($search)) {
    $sql = "SELECT a.*, s.name as species_name 
            FROM animals a 
            JOIN species s ON a.species_id = s.id 
            WHERE a.name LIKE ? 
            ORDER BY a.name";
    $stmt = mysqli_prepare($conn, $sql);
    $search_term = "%$search%";
    mysqli_stmt_bind_param($stmt, "s", $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)) {
        $animals[] = $row;
    }
} else {
    $sql = "SELECT a.*, s.name as species_name 
            FROM animals a 
            JOIN species s ON a.species_id = s.id 
            ORDER BY a.name";
    $result = mysqli_query($conn, $sql);
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $animals[] = $row;
        }
    }
}

$species_count = count($species);
$animal_count = count($animals);

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['action']) && $_POST['action'] == 'add_species') {
        $species_name = sanitize($_POST['species_name']);
        
        if(empty($species_name)) {
            $species_err = "Le nom de l'espèce est requis.";
        } else {
            $sql = "SELECT id FROM species WHERE name = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $species_name);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) > 0) {
                $species_err = "Cette espèce existe déjà.";
            } else {
                $sql = "INSERT INTO species (name) VALUES (?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $species_name);
                
                if(mysqli_stmt_execute($stmt)) {
                    redirect('employee_dashboard.php', 'Espèce ajoutée avec succès!', 'success');
                } else {
                    $species_err = "Erreur lors de l'ajout de l'espèce.";
                }
            }
        }
    }
    
    if(isset($_POST['action']) && $_POST['action'] == 'add_animal') {
        $animal_name = sanitize($_POST['animal_name']);
        $species_id = intval($_POST['species_id']);
        $gender = sanitize($_POST['gender']);
        $description = sanitize($_POST['description']);
        
        if(empty($species_id)) {
            $animal_err = "Veuillez sélectionner une espèce.";
        } else {
            $sql = "INSERT INTO animals (name, species_id, gender, description) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siss", $animal_name, $species_id, $gender, $description);
            
            if(mysqli_stmt_execute($stmt)) {
                redirect('employee_dashboard.php', 'Animal ajouté avec succès!', 'success');
            } else {
                $animal_err = "Erreur lors de l'ajout de l'animal.";
            }
        }
    }
    
    if(isset($_POST['action']) && $_POST['action'] == 'update_animal_name') {
        $animal_id = intval($_POST['animal_id']);
        $animal_name = sanitize($_POST['animal_name']);
        
        if(empty($animal_name)) {
            $name_err = "Le nom de l'animal est requis.";
        } else {
            $sql = "UPDATE animals SET name = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $animal_name, $animal_id);
            
            if(mysqli_stmt_execute($stmt)) {
                redirect('employee_dashboard.php', 'Nom de l\'animal mis à jour avec succès!', 'success');
            } else {
                $name_err = "Erreur lors de la mise à jour du nom de l'animal.";
            }
        }
    }
    
    if(isset($_POST['action']) && $_POST['action'] == 'delete_animal') {
        $animal_id = intval($_POST['animal_id']);
        
        $sql = "DELETE FROM animals WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $animal_id);
        
        if(mysqli_stmt_execute($stmt)) {
            redirect('employee_dashboard.php', 'Animal supprimé avec succès!', 'success');
        } else {
            $animal_err = "Erreur lors de la suppression de l'animal.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Animaux - Zoo Management</title>
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
                <a href="employee_dashboard.php" class="active"><i class="fas fa-paw"></i> Gestion des Animaux</a>
                <a href="species_list.php"><i class="fas fa-dna"></i> Liste des Espèces</a>
                <?php if(isDirector()): ?>
                    <a href="director_dashboard.php"><i class="fas fa-users"></i> Gestion des Employés</a>
                    <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistiques</a>
                <?php endif; ?>
                <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?> (Salarié)</span>
            </div>
        </nav>
    </header>

    <main class="dashboard">
        <h1 class="dashboard-title">Gestion des Animaux</h1>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert <?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dna"></i>
                </div>
                <div class="stat-details">
                    <h3>Espèces</h3>
                    <p class="stat-number"><?php echo $species_count; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="stat-details">
                    <h3>Animaux</h3>
                    <p class="stat-number"><?php echo $animal_count; ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Ajouter une espèce</h2>
                </div>
                <div class="card-body">
                    <?php if(isset($species_err)): ?>
                        <div class="alert error"><?php echo $species_err; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form">
                        <input type="hidden" name="action" value="add_species">
                        <div class="form-group">
                            <label for="species_name"><i class="fas fa-signature"></i> Nom de l'espèce</label>
                            <input type="text" id="species_name" name="species_name" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter l'espèce</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Ajouter un animal</h2>
                </div>
                <div class="card-body">
                    <?php if(isset($animal_err)): ?>
                        <div class="alert error"><?php echo $animal_err; ?></div>
                    <?php endif; ?>
                    <?php if(count($species) > 0): ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="form">
                            <input type="hidden" name="action" value="add_animal">
                            <div class="form-group">
                                <label for="animal_name"><i class="fas fa-signature"></i> Nom de l'animal</label>
                                <input type="text" id="animal_name" name="animal_name" placeholder="Laissez vide pour un nom généré automatiquement">
                            </div>
                            <div class="form-group">
                                <label for="species_id"><i class="fas fa-dna"></i> Espèce</label>
                                <select id="species_id" name="species_id" required>
                                    <option value="">Sélectionnez une espèce</option>
                                    <?php foreach($species as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gender"><i class="fas fa-venus-mars"></i> Genre</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Sélectionnez le genre</option>
                                    <option value="male">Mâle</option>
                                    <option value="female">Femelle</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="description"><i class="fas fa-book"></i> Description</label>
                                <textarea id="description" name="description" rows="3" placeholder="Description de l'animal..."></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter l'animal</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p class="no-data">Veuillez d'abord ajouter une espèce.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card full-width">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Liste des animaux</h2>
                </div>
                <div class="card-body">
                    <!-- Ajout du formulaire de recherche -->
                    <div class="search-box">
                        <form method="get" class="search-form">
                            <input type="text" name="search" placeholder="Rechercher un animal..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>

                    <?php if(isset($name_err)): ?>
                        <div class="alert error"><?php echo $name_err; ?></div>
                    <?php endif; ?>
                    <?php if(count($animals) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Genre</th>
                                    <th>Espèce</th>
                                    <th>Description</th>
                                    <th>Date d'ajout</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($animals as $animal): ?>
                                <tr>
                                    <td><?php echo $animal['id']; ?></td>
                                    <td>
                                        <?php echo $animal['name'] ? htmlspecialchars($animal['name']) : '<em>Sans nom</em>'; ?>
                                    </td>
                                    <td class="gender-cell">
                                        <?php if($animal['gender'] == 'male'): ?>
                                            <i class="fas fa-mars male"></i>
                                        <?php else: ?>
                                            <i class="fas fa-venus female"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($animal['species_name']); ?></td>
                                    <td class="description-cell">
                                        <?php 
                                            echo $animal['description'] ? 
                                                '<span class="description-preview">' . substr(htmlspecialchars($animal['description']), 0, 50) . '...</span>' : 
                                                '<em>Pas de description</em>'; 
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($animal['created_at'])); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-view" data-id="<?php echo $animal['id']; ?>" 
                                                data-description="<?php echo htmlspecialchars($animal['description']); ?>">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                        <button class="btn btn-edit" data-id="<?php echo $animal['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($animal['name']); ?>">
                                            <i class="fas fa-edit"></i> Modifier
                                        </button>
                                        <button class="btn btn-delete" onclick="deleteAnimal(<?php echo $animal['id']; ?>)">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">Aucun animal trouvé.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal pour voir la description -->
    <div id="view-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-eye"></i> Détails de l'animal</h3>
            <div class="animal-details">
                <p id="modal-description"></p>
            </div>
        </div>
    </div>

    <!-- Modal pour renommer -->
    <div id="rename-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-edit"></i> Renommer l'animal</h3>
            <form method="post" class="form">
                <input type="hidden" name="action" value="update_animal_name">
                <input type="hidden" name="animal_id" id="modal-animal-id">
                <div class="form-group">
                    <label>Nouveau nom</label>
                    <input type="text" name="animal_name" id="modal-animal-name" required>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <p>Êtes-vous sûr de vouloir supprimer cet animal ?</p>
            <form method="post">
                <input type="hidden" name="action" value="delete_animal">
                <input type="hidden" name="animal_id" id="delete-animal-id">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                    <button type="submit" class="btn btn-delete">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewModal = document.getElementById("view-modal");
            const renameModal = document.getElementById("rename-modal");
            const deleteModal = document.getElementById("delete-modal");
            const viewButtons = document.getElementsByClassName("btn-view");
            const editButtons = document.getElementsByClassName("btn-edit");
            const closeBtns = document.getElementsByClassName("close");

            // View buttons
            Array.from(viewButtons).forEach(button => {
                button.addEventListener('click', function() {
                    const description = this.getAttribute('data-description');
                    document.getElementById('modal-description').textContent = description || "Pas de description disponible";
                    viewModal.style.display = "block";
                });
            });

            // Edit buttons
            Array.from(editButtons).forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    document.getElementById('modal-animal-id').value = id;
                    document.getElementById('modal-animal-name').value = name;
                    renameModal.style.display = "block";
                });
            });

            // Close buttons
            Array.from(closeBtns).forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.modal').style.display = "none";
                });
            });

            // Click outside modal
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = "none";
                }
            });
        });

        function deleteAnimal(animalId) {
            document.getElementById('delete-animal-id').value = animalId;
            document.getElementById('delete-modal').style.display = 'block';
        }
    </script>
</body>
</html>
