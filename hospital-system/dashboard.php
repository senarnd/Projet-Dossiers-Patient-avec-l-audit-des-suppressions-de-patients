<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données pour les statistiques
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Statistiques
    $stats = [];
    
    // Total patients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients");
    $stats['total_patients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Admissions aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = CURDATE()");
    $stats['today_admissions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total médecins
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'doctor'");
    $stats['total_doctors'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Patients par genre
    $stmt = $pdo->query("SELECT gender, COUNT(*) as count FROM patients GROUP BY gender");
    $stats['gender_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dernier patient
    $stmt = $pdo->query("SELECT first_name, last_name FROM patients ORDER BY created_at DESC LIMIT 1");
    $stats['last_patient'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Système Hospitalier</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin-top: 0.5rem;
        }
        
        .stat-text {
            font-size: 1.2rem;
            font-weight: 500;
            color: #28a745;
            margin-top: 0.5rem;
        }
        
        .gender-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .gender-stat {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }
        
        .gender-M { background: #d1ecf1; color: #0c5460; }
        .gender-F { background: #f8d7da; color: #721c24; }
        .gender-Other { background: #fff3cd; color: #856404; }
        
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .welcome-message {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="dashboard">
        <div class="welcome-message">
            <h1>Tableau de Bord</h1>
            <p>Bienvenue, <strong><?php echo $_SESSION['full_name']; ?></strong> ! Voici un aperçu de l'activité du système.</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Patients Totaux</h3>
                <p class="stat-number"><?php echo $stats['total_patients']; ?></p>
                <p>dans la base de données</p>
            </div>
            
            <div class="stat-card">
                <h3>Admissions Aujourd'hui</h3>
                <p class="stat-number"><?php echo $stats['today_admissions']; ?></p>
                <p>nouvelles admissions</p>
            </div>
            
            <div class="stat-card">
                <h3>Médecins</h3>
                <p class="stat-number"><?php echo $stats['total_doctors']; ?></p>
                <p>médecins actifs</p>
            </div>
            
            <div class="stat-card">
                <h3>Dernier Patient</h3>
                <?php if ($stats['last_patient']): ?>
                    <p class="stat-text"><?php echo htmlspecialchars($stats['last_patient']['first_name'] . ' ' . $stats['last_patient']['last_name']); ?></p>
                <?php else: ?>
                    <p class="stat-text">Aucun patient</p>
                <?php endif; ?>
                <p>dernière admission</p>
            </div>
        </div>

        <!-- Statistiques par genre -->
        <?php if (!empty($stats['gender_distribution'])): ?>
        <div class="stat-card">
            <h3>Répartition par Genre</h3>
            <div class="gender-stats">
                <?php foreach ($stats['gender_distribution'] as $distribution): ?>
                    <div class="gender-stat gender-<?php echo $distribution['gender']; ?>">
                        <?php 
                        $gender_label = [
                            'M' => 'Hommes',
                            'F' => 'Femmes', 
                            'Other' => 'Autres'
                        ];
                        echo $gender_label[$distribution['gender']] . ': ' . $distribution['count']; 
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="quick-actions">
            <h2>Actions Rapides</h2>
            <div class="action-buttons">
                <a href="patients.php" class="btn-primary">Voir tous les patients</a>
                <a href="patient_form.php" class="btn-success">Nouveau patient</a>
                <a href="audit_global.php" class="btn-primary">📊 Audit Global</a>
            </div>
        </div>
    </div>
</body>
</html>