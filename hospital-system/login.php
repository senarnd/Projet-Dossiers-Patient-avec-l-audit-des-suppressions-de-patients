<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Connexion à la base de données
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Recherche de l'utilisateur
        $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = :username");
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérification du mot de passe (en production, utilisez password_verify)
            if ($password === 'password') { // Mot de passe temporaire
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Redirection vers le tableau de bord
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Mot de passe incorrect";
            }
        } else {
            $error = "Utilisateur non trouvé";
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion à la base de données: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Système Hospitalier</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-form h1 {
            text-align: center;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .login-form > p {
            text-align: center;
            margin-bottom: 2rem;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .demo-accounts {
            margin-top: 2rem;
            padding: 1rem;
            background: #e9ecef;
            border-radius: 5px;
            font-size: 0.875rem;
        }
        
        .demo-accounts h3 {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1>Connexion</h1>
            <p>Système de Suivi des Dossiers Patients</p>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur:</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">Se connecter</button>
            </form>
            
            <div class="demo-accounts">
                <h3>Comptes de démonstration:</h3>
                <p><strong>Docteur:</strong> dr.smith / password</p>
                <p><strong>Infirmière:</strong> nurse.jones / password</p>
                <p><strong>Admin:</strong> admin / password</p>
            </div>
        </div>
    </div>
</body>
</html>