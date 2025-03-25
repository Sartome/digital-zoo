<?php
require_once 'config.php';

if(!isLoggedIn()) {
    redirect('login.php', 'Veuillez vous connecter pour accéder à cette page.', 'error');
}

// Obtenir les statistiques de base
$total_animals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM animals"))['count'];
$total_species = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM species"))['count'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Zoo Management</title>
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
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Accueil</a>
                <a href="employee_dashboard.php"><i class="fas fa-paw"></i> Gestion des Animaux</a>
                <a href="species_list.php"><i class="fas fa-dna"></i> Liste des Espèces</a>
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

    <main class="dashboard">
        <h1>Bienvenue dans le Zoo Management System</h1>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="stat-details">
                    <h3>Animaux</h3>
                    <p class="stat-number"><?php echo $total_animals; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dna"></i>
                </div>
                <div class="stat-details">
                    <h3>Espèces</h3>
                    <p class="stat-number"><?php echo $total_species; ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-tasks"></i> Actions rapides</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <?php if(isDirector()): ?>
                            <a href="director_dashboard.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> Gestion des Employés
                            </a>
                            <a href="employee_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-paw"></i> Gestion des Animaux
                            </a>
                            <a href="statistics.php" class="btn btn-info">
                                <i class="fas fa-chart-bar"></i> Statistiques
                            </a>
                        <?php else: ?>
                            <a href="employee_dashboard.php" class="btn btn-primary">
                                <i class="fas fa-paw"></i> Gestion des Animaux
                            </a>
                            <a href="species_list.php" class="btn btn-secondary">
                                <i class="fas fa-dna"></i> Liste des Espèces
                            </a>
                        <?php endif; ?>
                        <a href="profile.php" class="btn btn-info">
                            <i class="fas fa-user"></i> Mon Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
    </footer>
</body>
</html>