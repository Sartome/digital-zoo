<?php
require_once 'config.php';

if(!isLoggedIn()) {
    redirect('login.php', 'Veuillez vous connecter pour accéder à cette page.', 'error');
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if($new_password === $confirm_password) {
            // Vérification du mot de passe actuel
            if(password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE id = $user_id");
                $_SESSION['message'] = "Mot de passe mis à jour avec succès";
                $_SESSION['message_type'] = "success";
                redirect('profile.php');
            } else {
                $error = "Mot de passe actuel incorrect";
            }
        } else {
            $error = "Les nouveaux mots de passe ne correspondent pas";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Zoo Management</title>
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
                    <a href="director_dashboard.php"><i class="fas fa-users"></i> Gestion des Employés</a>
                    <a href="statistics.php"><i class="fas fa-chart-bar"></i> Statistiques</a>
                <?php endif; ?>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profil</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?> (Directeur)</span>
            </div>
        </nav>
    </header>

    <main class="dashboard">
        <h1>Mon Profil</h1>

        <?php if(isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

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
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-user"></i> Informations du compte</h2>
                </div>
                <div class="card-body">
                    <div class="profile-info">
                        <p><strong>Nom d'utilisateur:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Role:</strong> <?php echo $user['role'] == 'directeur' ? 'Directeur' : 'Salarié'; ?></p>
                        <p><strong>Date d'inscription:</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-key"></i> Changer le mot de passe</h2>
                </div>
                <div class="card-body">
                    <form method="post" class="form">
                        <input type="hidden" name="update_password" value="1">
                        <div class="form-group">
                            <label>Mot de passe actuel</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirmer le nouveau mot de passe</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Mettre à jour le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
    </footer>
</body>
</html>
