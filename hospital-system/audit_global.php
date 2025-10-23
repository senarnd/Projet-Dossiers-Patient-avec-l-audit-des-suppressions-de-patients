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

// Inclure les fonctions d'audit
require_once 'includes/audit_functions.php';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les filtres
    $filters = [
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'user_id' => $_GET['user_id'] ?? '',
        'action' => $_GET['action'] ?? '',
        'patient_id' => $_GET['patient_id'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Construire la requête avec filtres
    $query = "SELECT al.*, u.full_name, u.role, 
                     p.first_name as patient_first_name, 
                     p.last_name as patient_last_name
              FROM audit_log al 
              JOIN users u ON al.changed_by = u.id 
              JOIN patients p ON al.record_id = p.id 
              WHERE al.table_name = 'patients'";
    
    $params = [];
    
    // Filtre date début
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(al.changed_at) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    
    // Filtre date fin
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(al.changed_at) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    
    // Filtre utilisateur
    if (!empty($filters['user_id'])) {
        $query .= " AND al.changed_by = :user_id";
        $params[':user_id'] = $filters['user_id'];
    }
    
    // Filtre action
    if (!empty($filters['action'])) {
        $query .= " AND al.action = :action";
        $params[':action'] = $filters['action'];
    }
    
    // Filtre patient
    if (!empty($filters['patient_id'])) {
        $query .= " AND al.record_id = :patient_id";
        $params[':patient_id'] = $filters['patient_id'];
    }
    
    // Recherche texte
    if (!empty($filters['search'])) {
        $query .= " AND (p.first_name LIKE :search OR p.last_name LIKE :search OR u.full_name LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $query .= " ORDER BY al.changed_at DESC";
    
    // Pagination SIMPLIFIÉE
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Requête pour le total
    $count_query = "SELECT COUNT(*) as total FROM ($query) as count_table";
    $count_stmt = $pdo->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Requête principale avec pagination FIXE (sans paramètres nommés)
    $query .= " LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la liste des utilisateurs pour le filtre
    $users_stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la liste des patients pour le filtre
    $patients_stmt = $pdo->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name");
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiques - version corrigée
    $stats_query = "SELECT 
        COUNT(*) as total_actions,
        COUNT(DISTINCT al.changed_by) as unique_users,
        COUNT(DISTINCT al.record_id) as unique_patients,
        SUM(CASE WHEN al.action = 'CREATE' THEN 1 ELSE 0 END) as creations,
        SUM(CASE WHEN al.action = 'UPDATE' THEN 1 ELSE 0 END) as updates
        FROM audit_log al 
        JOIN users u ON al.changed_by = u.id 
        JOIN patients p ON al.record_id = p.id 
        WHERE al.table_name = 'patients'";
    
    $stats_params = [];
    $stats_conditions = [];
    
    // Appliquer les mêmes filtres
    if (!empty($filters['date_from'])) {
        $stats_conditions[] = "DATE(al.changed_at) >= :date_from";
        $stats_params[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $stats_conditions[] = "DATE(al.changed_at) <= :date_to";
        $stats_params[':date_to'] = $filters['date_to'];
    }
    if (!empty($filters['user_id'])) {
        $stats_conditions[] = "al.changed_by = :user_id";
        $stats_params[':user_id'] = $filters['user_id'];
    }
    if (!empty($filters['action'])) {
        $stats_conditions[] = "al.action = :action";
        $stats_params[':action'] = $filters['action'];
    }
    if (!empty($filters['patient_id'])) {
        $stats_conditions[] = "al.record_id = :patient_id";
        $stats_params[':patient_id'] = $filters['patient_id'];
    }
    if (!empty($filters['search'])) {
        $stats_conditions[] = "(p.first_name LIKE :search OR p.last_name LIKE :search OR u.full_name LIKE :search)";
        $stats_params[':search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($stats_conditions)) {
        $stats_query .= " AND " . implode(" AND ", $stats_conditions);
    }
    
    $stats_stmt = $pdo->prepare($stats_query);
    foreach ($stats_params as $key => $value) {
        $stats_stmt->bindValue($key, $value);
    }
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Global - Système Hospitalier</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .header { background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 1rem 0; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 1rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .btn-logout { background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .audit-page { max-width: 1400px; margin: 2rem auto; padding: 0 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #007bff; margin-bottom: 0.5rem; }
        .filters-section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9rem; }
        .filter-actions { display: flex; gap: 1rem; justify-content: flex-end; }
        .btn-primary { background: #007bff; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-secondary { background: #6c757d; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .audit-table { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .audit-table table { width: 100%; border-collapse: collapse; }
        .audit-table th, .audit-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .audit-table th { background: #f8f9fa; font-weight: 600; position: sticky; top: 0; }
        .action-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: bold; font-size: 0.875rem; display: inline-block; }
        .action-CREATE { background: #d4edda; color: #155724; }
        .action-UPDATE { background: #fff3cd; color: #856404; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin: 2rem 0; }
        .page-link { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #007bff; }
        .page-link.active { background: #007bff; color: white; border-color: #007bff; }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error { background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; }
        .export-section { text-align: right; margin-bottom: 1rem; }
        .btn-success { background: #28a745; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .action-RESTORE { background: #cce7ff; color: #004085; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="dashboard.php" class="btn-secondary">Tableau de Bord</a>
                <a href="patients.php" class="btn-secondary">Patients</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="audit-page">
        <h1>📊 Audit Global des Modifications</h1>
        <p>Surveillance de toutes les activités du système</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_actions'] ?? 0; ?></div>
                <div>Actions totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['unique_patients'] ?? 0; ?></div>
                <div>Patients modifiés</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['unique_users'] ?? 0; ?></div>
                <div>Utilisateurs actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['creations'] ?? 0; ?></div>
                <div>Créations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['updates'] ?? 0; ?></div>
                <div>Modifications</div>
            </div>
        </div>
        
        <!-- Filtres -->
        <div class="filters-section">
            <h3>🔍 Filtres de recherche</h3>
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="date_from">Date de début</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Date de fin</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="user_id">Utilisateur</label>
                        <select id="user_id" name="user_id">
                            <option value="">Tous les utilisateurs</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="action">Action</label>
                        <select id="action" name="action">
                            <option value="">Toutes les actions</option>
                            <option value="CREATE" <?php echo $filters['action'] == 'CREATE' ? 'selected' : ''; ?>>Création</option>
                            <option value="UPDATE" <?php echo $filters['action'] == 'UPDATE' ? 'selected' : ''; ?>>Modification</option>
                            <option value="DELETE" <?php echo $filters['action'] == 'DELETE' ? 'selected' : ''; ?>>Suppression</option>
                            <option value="RESTORE" <?php echo $filters['action'] == 'RESTORE' ? 'selected' : ''; ?>>Restauration</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="patient_id">Patient</label>
                        <select id="patient_id" name="patient_id">
                            <option value="">Tous les patients</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['id']; ?>" <?php echo $filters['patient_id'] == $patient['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search">Recherche texte</label>
                        <input type="text" id="search" name="search" placeholder="Nom patient ou utilisateur..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">Appliquer les filtres</button>
                    <a href="audit_global.php" class="btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
        
        <!-- Export -->
        <div class="export-section">
            <a href="audit_export.php?<?php echo http_build_query($_GET); ?>" class="btn-success">📥 Export CSV</a>
        </div>
        
        <!-- Tableau des résultats -->
        <div class="audit-table">
            <?php if (empty($audit_logs)): ?>
                <div class="no-results">
                    <h3>📝 Aucun résultat trouvé</h3>
                    <p>Aucune action ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date/Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Patient</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_logs as $log): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($log['changed_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                <br><small><?php echo $log['role']; ?></small>
                            </td>
                            <td>
                                <span class="action-badge action-<?php echo $log['action']; ?>">
                                    <?php echo $log['action']; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['patient_first_name'] . ' ' . $log['patient_last_name']); ?></strong>
                                <br><small>ID: <?php echo $log['record_id']; ?></small>
                            </td>
                            <td>
                                <a href="patient_history.php?id=<?php echo $log['record_id']; ?>" class="btn-secondary">
                                    Voir l'historique
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="dashboard.php" class="btn-primary">Retour au tableau de bord</a>
        </div>
    </div>
</body>
</html>