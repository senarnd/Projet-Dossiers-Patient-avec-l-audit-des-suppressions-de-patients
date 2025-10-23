<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Accès non autorisé";
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !isset($_GET['audit_id'])) {
    $_SESSION['error'] = "Paramètres manquants";
    header("Location: corbeille.php");
    exit();
}

$patient_id = $_GET['id'];
$audit_id = $_GET['audit_id'];

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Récupérer les données sauvegardées depuis audit_log
    $audit_query = "SELECT old_values FROM audit_log WHERE id = :audit_id AND action = 'DELETE'";
    $audit_stmt = $pdo->prepare($audit_query);
    $audit_stmt->bindParam(":audit_id", $audit_id);
    $audit_stmt->execute();
    $audit_data = $audit_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$audit_data || !$audit_data['old_values']) {
        $_SESSION['error'] = "Données de restauration non trouvées";
        header("Location: corbeille.php");
        exit();
    }
    
    // 2. Convertir les données JSON
    $patient_data = json_decode($audit_data['old_values'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error'] = "Erreur de lecture des données sauvegardées";
        header("Location: corbeille.php");
        exit();
    }
    
    // 3. Vérifier si le patient existe déjà (éviter les doublons)
    $check_query = "SELECT id FROM patients WHERE id = :id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->bindParam(":id", $patient_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $_SESSION['error'] = "Un patient avec cet ID existe déjà";
        header("Location: corbeille.php");
        exit();
    }
    
    // 4. RESTAURATION du patient
    $columns = implode(", ", array_keys($patient_data));
    $placeholders = ":" . implode(", :", array_keys($patient_data));
    
    $restore_query = "INSERT INTO patients ($columns) VALUES ($placeholders)";
    $restore_stmt = $pdo->prepare($restore_query);
    
    foreach ($patient_data as $key => $value) {
        $restore_stmt->bindValue(":$key", $value);
    }
    
    if ($restore_stmt->execute()) {
        // 5. ENREGISTREMENT AUDIT de la restauration
        $audit_restore_query = "INSERT INTO audit_log 
                               (table_name, record_id, action, old_values, new_values, changed_by) 
                               VALUES (:table_name, :record_id, :action, :old_values, :new_values, :changed_by)";
        
        $audit_restore_stmt = $pdo->prepare($audit_restore_query);
        $audit_restore_stmt->execute([
            ':table_name' => 'patients',
            ':record_id' => $patient_id,
            ':action' => 'RESTORE',
            ':old_values' => null,
            ':new_values' => $audit_data['old_values'],
            ':changed_by' => $_SESSION['user_id']
        ]);
        
        $_SESSION['success'] = "Patient restauré avec succès !";
    } else {
        $_SESSION['error'] = "Erreur lors de la restauration du patient";
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur base de données: " . $e->getMessage();
}

header("Location: corbeille.php");
exit();
?>