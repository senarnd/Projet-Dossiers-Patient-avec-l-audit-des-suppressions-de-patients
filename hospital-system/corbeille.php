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

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les suppressions depuis audit_log
    $query = "SELECT al.*, u.full_name, u.role,
                     JSON_EXTRACT(al.old_values, '$.first_name') as patient_first_name,
                     JSON_EXTRACT(al.old_values, '$.last_name') as patient_last_name,
                     JSON_EXTRACT(al.old_values, '$.birth_date') as patient_birth_date
              FROM audit_log al 
              JOIN users u ON al.changed_by = u.id 
              WHERE al.table_name = 'patients' AND al.action = 'DELETE'
              ORDER BY al.changed_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $deleted_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille - Système Hospitalier</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .header { background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 1rem 0; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 1rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .btn-logout { background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .corbeille-page { max-width: 1400px; margin: 2rem auto; padding: 0 1rem; }
        .stats-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; text-align: center; }
        .stats-number { font-size: 2rem; font-weight: bold; color: #dc3545; margin-bottom: 0.5rem; }
        .corbeille-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .corbeille-table table { width: 100%; border-collapse: collapse; }
        .corbeille-table th, .corbeille-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .corbeille-table th { background: #f8f9fa; font-weight: 600; }
        .action-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: bold; font-size: 0.875rem; display: inline-block; }
        .action-DELETE { background: #f8d7da; color: #721c24; }
        .btn-primary { background: #007bff; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-warning { background: #ffc107; color: black; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .error { background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .patient-info { background: #f8f9fa; padding: 1rem; border-radius: 5px; margin: 0.5rem 0; }
        .action-RESTORE { background: #cce7ff; color: #004085; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="patients.php" class="btn-primary">Patients</a>
                <a href="audit_global.php" class="btn-primary">Audit Global</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="corbeille-page">
        <h1>🗑️ Corbeille - Patients Supprimés</h1>
        <p>Historique des patients supprimés avec possibilité de restauration</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem;">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="stats-card">
            <div class="stats-number"><?php echo count($deleted_patients); ?></div>
            <div>Patients supprimés</div>
        </div>
        
        <!-- Tableau des suppressions -->
        <div class="corbeille-table">
            <?php if (empty($deleted_patients)): ?>
                <div class="no-results">
                    <h3>✅ Aucun patient supprimé</h3>
                    <p>Aucune suppression n'a été enregistrée dans le système.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date Suppression</th>
                            <th>Supprimé par</th>
                            <th>Données du patient</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deleted_patients as $deleted): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars(trim($deleted['patient_first_name'], '"') . ' ' . trim($deleted['patient_last_name'], '"')); ?>
                                </strong>
                                <br><small>ID: <?php echo $deleted['record_id']; ?></small>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($deleted['changed_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($deleted['full_name']); ?></strong>
                                <br><small><?php echo $deleted['role']; ?></small>
                            </td>
                            <td>
                                <div class="patient-info">
                                    <strong>Données sauvegardées :</strong><br>
                                    <small>
                                        Naissance: <?php echo date('d/m/Y', strtotime(trim($deleted['patient_birth_date'], '"'))); ?><br>
                                        Données complètes disponibles pour restauration
                                    </small>
                                </div>
                            </td>
                            <td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a href="patient_restore.php?id=<?php echo $deleted['record_id']; ?>&audit_id=<?php echo $deleted['id']; ?>" 
                                       class="btn-warning" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce patient ?')">
                                        🔄 Restaurer
                                    </a>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Admin seulement</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="patients.php" class="btn-primary">Retour à la liste des patients</a>
            <a href="audit_global.php" class="btn-secondary">Voir l'audit global</a>
        </div>
    </div>
</body>
</html>