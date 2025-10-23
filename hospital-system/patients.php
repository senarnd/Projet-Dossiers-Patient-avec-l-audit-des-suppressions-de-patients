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

// Message de succès
$success = $_GET['success'] ?? '';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Recherche et filtrage
    $search = $_GET['search'] ?? '';
    $where = "";
    $params = [];
    
    if (!empty($search)) {
        $where = "WHERE first_name LIKE :search OR last_name LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    $query = "SELECT * FROM patients $where ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Patients</title>
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
        
        .patients-page {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2rem 0;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-form input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 300px;
        }
        
        .search-form button {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .patients-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .patients-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .patients-table th,
        .patients-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .patients-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-edit {
            background: #ffc107;
            color: black;
            padding: 0.3rem 0.6rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-block;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.6rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
            padding: 0.5rem 1rem;
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
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 0.3rem 0.6rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-block;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .action-RESTORE {
             background: #cce7ff; 
             color: #004085;
         }
        .btn-corbeille {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-corbeille:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="dashboard.php" class="btn-primary">Tableau de Bord</a>
                <a href="audit_global.php" class="btn-primary">📊 Audit Global</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="patients-page">
        <h1>Gestion des Patients</h1>
        
        <?php if (!empty($success)): ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Rechercher un patient..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Rechercher</button>
            </form>
            <a href="patient_form.php" class="btn-success">Nouveau Patient</a>
            <a href="corbeille.php" class="btn-corbeille">🗑️ Corbeille</a>
            <a href="audit_global.php" class="btn-primary">📊 Audit Global</a>
        </div>
        
        <div class="patients-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Date de Naissance</th>
                        <th>Genre</th>
                        <th>Téléphone</th>
                        <th>Date Admission</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Aucun patient trouvé</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?php echo $patient['id']; ?></td>
                            <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($patient['birth_date'])); ?></td>
                            <td><?php echo $patient['gender']; ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($patient['created_at'])); ?></td>
                            <td class="actions">
                                <a href="patient_form.php?id=<?php echo $patient['id']; ?>" class="btn-edit">Modifier</a>
                                <a href="patient_history.php?id=<?php echo $patient['id']; ?>" class="btn-info">Historique</a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="patient_delete.php?id=<?php echo $patient['id']; ?>" class="btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ?');">Supprimer</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>