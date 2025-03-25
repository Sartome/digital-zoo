<?php
require_once 'config.php';

if(isLoggedIn()) {
    if(isDirector()) {
        redirect('dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(empty(trim($_POST["username"]))) {
        $username_err = "Veuillez entrer votre nom d'utilisateur.";
    } else {
        $username = sanitize($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer votre mot de passe.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {                    
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if(mysqli_stmt_fetch($stmt)) {
                        if(password_verify($password, $hashed_password)) {
                            session_start();
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            
                            if($role == "directeur") {
                                redirect('dashboard.php');
                            } else {
                                redirect('dashboard.php');
                            }
                        } else {
                            $login_err = "Nom d'utilisateur ou mot de passe invalide.";
                        }
                    }
                } else {
                    $login_err = "Nom d'utilisateur ou mot de passe invalide.";
                }
            } else {
                $login_err = "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
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
    <title>Connexion - Zoo Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: url('preview.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.8); /* Add transparency */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="fas fa-paw"></i> Zoo Management</h2>
        <h3>Connexion</h3>
        
        <?php if(!empty($login_err)) { ?>
            <div class="alert error"><?php echo $login_err; ?></div>
        <?php } ?>
        
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
                <button type="submit"><i class="fas fa-sign-in-alt"></i> Se connecter</button>
            </div>
        </form>
        
        <div class="create-account">
            <p>Vous n'avez pas de compte? <a href="register.php">Créer un compte</a></p>
        </div>
        
        <footer>
            <p>&copy; <?php echo date("Y"); ?> Zoo Management System</p>
        </footer>
    </div>
</body>
</html>
