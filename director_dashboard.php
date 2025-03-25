<?php
require_once 'config.php';

if(!isLoggedIn()) {
    redirect('login.php', 'Veuillez vous connecter pour accéder à cette page.', 'error');
}

if(!isDirector()) {
    redirect('employee_dashboard.php', 'Accès refusé. Vous n\'avez pas les droits nécessaires.', 'error');
}

// Gestion des utilisateurs
$users = [];
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if(!empty($search)) {
    $sql = "SELECT * FROM users WHERE username LIKE ? ORDER BY username";
    $stmt = mysqli_prepare($conn, $sql);
    $search_term = "%$search%";
    mysqli_stmt_bind_param($stmt, "s", $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $users = [];
    while($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
} else {
    $sql = "SELECT * FROM users ORDER BY username";
    $result = mysqli_query($conn, $sql);
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
}

// Gestion des espèces
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

// Gestion des animaux
$animals = [];
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

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'add_edit') {
            $name = sanitize($_POST['name']);
            $role = sanitize($_POST['role']);
            $contact = sanitize($_POST['contact']);
            $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
            
            if(empty($name) || empty($role) || empty($contact)) {
                $error_message = "Tous les champs sont obligatoires.";
            } else {
                if($employee_id > 0) {
                    $sql = "UPDATE employees SET name = ?, role = ?, contact = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssi", $name, $role, $contact, $employee_id);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        redirect('director_dashboard.php', 'Employé mis à jour avec succès!', 'success');
                    } else {
                        $error_message = "Erreur lors de la mise à jour de l'employé.";
                    }
                } else {
                    $sql = "INSERT INTO employees (name, role, contact) VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sss", $name, $role, $contact);
                    
                    if(mysqli_stmt_execute($stmt)) {
                        redirect('director_dashboard.php', 'Employé ajouté avec succès!', 'success');
                    } else {
                        $error_message = "Erreur lors de l'ajout de l'employé.";
                    }
                }
            }
        }
        
        if($_POST['action'] == 'delete' && isset($_POST['employee_id'])) {
            $employee_id = intval($_POST['employee_id']);
            
            $sql = "DELETE FROM employees WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $employee_id);
            
            if(mysqli_stmt_execute($stmt)) {
                redirect('director_dashboard.php', 'Employé supprimé avec succès!', 'success');
            } else {
                $error_message = "Erreur lors de la suppression de l'employé.";
            }
        }
    }
}

$edit_employee = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $employee_id = intval($_GET['edit']);
    $sql = "SELECT * FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($result && mysqli_num_rows($result) > 0) {
        $edit_employee = mysqli_fetch_assoc($result);
    }
}

$view_employee = null;
if(isset($_GET['view']) && is_numeric($_GET['view'])) {
    $employee_id = intval($_GET['view']);
    $sql = "SELECT * FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($result && mysqli_num_rows($result) > 0) {
        $view_employee = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Directeur - Zoo Management</title>
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
                <a href="species_list.php"><i class="fas fa-dna"></i> Liste des Espèces</a>
                <?php if(isDirector()): ?>
                    <a href="director_dashboard.php" class="active"><i class="fas fa-users"></i> Gestion des Employés</a>
                    <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistiques</a>
                <?php endif; ?>
                <a href="profile.php"><i class="fas fa-user"></i> Profil</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?> (Directeur)</span>
            </div>
        </nav>
    </header>

    <main class="dashboard">
        <h1>Gestion des Employés</h1>
        
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus"></i> Ajouter un employé</h2>
            </div>
            <div class="card-body">
                <form method="post" class="form">
                    <input type="hidden" name="action" value="add_employee">
                    <div class="form-group">
                        <label>Nom d'utilisateur</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="salarie">Salarié</option>
                            <option value="directeur">Directeur</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Liste des employés</h2>
            </div>
            <div class="card-body">
                <!-- Ajouter le formulaire de recherche -->
                <div class="search-box">
                    <form method="get" class="search-form">
                        <input type="text" name="search" placeholder="Rechercher un employé..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </form>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom d'utilisateur</th>
                            <th>Role</th>
                            <th>Date d'ajout</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td class="actions">
                                <button class="btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button class="btn btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de confirmation de suppression -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <p>Êtes-vous sûr de vouloir supprimer cet employé ?</p>
            <form method="post" class="form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="employee_id" id="delete-employee-id">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                    <button type="submit" class="btn btn-delete">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-edit"></i> Modifier l'employé</h3>
            <form method="post" class="form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="employee_id" id="edit-employee-id">
                <div class="form-group">
                    <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" name="new_password">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit-role" required>
                        <option value="salarie">Salarié</option>
                        <option value="directeur">Directeur</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <script>
    function editUser(userId) {
        document.getElementById('edit-employee-id').value = userId;
        document.getElementById('edit-modal').style.display = 'block';
    }

    function deleteUser(userId) {
        document.getElementById('delete-employee-id').value = userId;
        document.getElementById('delete-modal').style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Fermeture des modals
        const modals = document.getElementsByClassName('modal');
        const closeBtns = document.getElementsByClassName('close');
        const cancelBtns = document.getElementsByClassName('close-modal');

        Array.from(closeBtns).forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        Array.from(cancelBtns).forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });
    });
    </script>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
    </footer>
</body>
</html>
