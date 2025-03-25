<?php
require_once 'config.php';

if(isLoggedIn()) {
    if(isDirector()) {
        redirect('director_dashboard.php');
    } else {
        redirect('employee_dashboard.php');
    }
}

$username = $password = $confirm_password = $role = "";
$username_err = $password_err = $confirm_password_err = $role_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty(trim($_POST["username"]))) {
        $username_err = "Veuillez entrer un nom d'utilisateur.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "Ce nom d'utilisateur est déjà pris.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer un mot de passe.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Le mot de passe doit avoir au moins 6 caractères.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Veuillez confirmer le mot de passe.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Les mots de passe ne correspondent pas.";
        }
    }
    
    if(empty(trim($_POST["role"]))) {
        $role_err = "Veuillez sélectionner un rôle.";     
    } else {
        $role = trim($_POST["role"]);
        if($role !== "salarie" && $role !== "directeur") {
            $role_err = "Rôle invalide.";
        }
    }
    
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err)) {
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_password, $param_role);
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_role = $role;
            
            if(mysqli_stmt_execute($stmt)) {
                redirect('login.php', 'Votre compte a été créé avec succès!', 'success');
            } else {
                echo "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Zoo Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="register-container">
        <h2><i class="fas fa-paw"></i> Zoo Management</h2>
        <h3>Créer un compte</h3>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?php echo $username; ?>" required>
                <span class="error-message"><?php echo $username_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                <input type="password" id="password" name="password" required>
                <span class="error-message"><?php echo $password_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <span class="error-message"><?php echo $confirm_password_err; ?></span>
            </div>
            
            <div class="form-group">
                <label for="role"><i class="fas fa-user-tag"></i> Rôle</label>
                <select id="role" name="role" required>
                    <option value="">Sélectionnez un rôle</option>
                    <option value="salarie" <?php echo ($role == "salarie") ? "selected" : ""; ?>>Salarié</option>
                    <option value="directeur" <?php echo ($role == "directeur") ? "selected" : ""; ?>>Directeur</option>
                </select>
                <span class="error-message"><?php echo $role_err; ?></span>
            </div>
            
            <div class="form-group">
                <button type="submit"><i class="fas fa-user-plus"></i> S'inscrire</button>
            </div>
        </form>
        
        <div class="login-link">
            <p>Vous avez déjà un compte? <a href="login.php">Se connecter</a></p>
        </div>
        
        <footer>
            <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
        </footer>
    </div>
</body>
</html>
