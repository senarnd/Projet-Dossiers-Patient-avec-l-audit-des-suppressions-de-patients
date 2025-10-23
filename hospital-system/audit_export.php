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
    
    // Récupérer les mêmes filtres que audit_global.php
    $filters = [
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'user_id' => $_GET['user_id'] ?? '',
        'action' => $_GET['action'] ?? '',
        'patient_id' => $_GET['patient_id'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Construire la même requête que audit_global.php (sans pagination)
    $query = "SELECT al.*, u.full_name, u.role, 
                     p.first_name as patient_first_name, 
                     p.last_name as patient_last_name
              FROM audit_log al 
              JOIN users u ON al.changed_by = u.id 
              JOIN patients p ON al.record_id = p.id 
              WHERE al.table_name = 'patients'";
    
    $params = [];
    
    // Appliquer les mêmes filtres
    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(al.changed_at) >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(al.changed_at) <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }
    if (!empty($filters['user_id'])) {
        $query .= " AND al.changed_by = :user_id";
        $params[':user_id'] = $filters['user_id'];
    }
    if (!empty($filters['action'])) {
        $query .= " AND al.action = :action";
        $params[':action'] = $filters['action'];
    }
    if (!empty($filters['patient_id'])) {
        $query .= " AND al.record_id = :patient_id";
        $params[':patient_id'] = $filters['patient_id'];
    }
    if (!empty($filters['search'])) {
        $query .= " AND (p.first_name LIKE :search OR p.last_name LIKE :search OR u.full_name LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $query .= " ORDER BY al.changed_at DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_global_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'Date/Heure',
        'Utilisateur', 
        'Rôle',
        'Action',
        'Patient',
        'ID Patient',
        'Anciennes valeurs',
        'Nouvelles valeurs'
    ], ';');
    
    // Données
    foreach ($audit_logs as $log) {
        fputcsv($output, [
            $log['changed_at'],
            $log['full_name'],
            $log['role'],
            $log['action'],
            $log['patient_first_name'] . ' ' . $log['patient_last_name'],
            $log['record_id'],
            $log['old_values'] ?? '',
            $log['new_values'] ?? ''
        ], ';');
    }
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    // En cas d'erreur, rediriger vers la page audit
    header("Location: audit_global.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>