<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier si l'utilisateur est connecté et a les droits
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Accès non autorisé";
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Récupérer les données du patient avant suppression
    $patient_query = "SELECT * FROM patients WHERE id = :id";
    $patient_stmt = $pdo->prepare($patient_query);
    $patient_stmt->bindParam(":id", $patient_id);
    $patient_stmt->execute();
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        $_SESSION['error'] = "Patient non trouvé";
        header("Location: patients.php");
        exit();
    }
    
    // 2. ENREGISTREMENT AUDIT avant suppression
    $audit_query = "INSERT INTO audit_log 
                   (table_name, record_id, action, old_values, new_values, changed_by) 
                   VALUES (:table_name, :record_id, :action, :old_values, :new_values, :changed_by)";
    
    $audit_stmt = $pdo->prepare($audit_query);
    $audit_stmt->execute([
        ':table_name' => 'patients',
        ':record_id' => $patient_id,
        ':action' => 'DELETE',
        ':old_values' => json_encode($patient, JSON_UNESCAPED_UNICODE),
        ':new_values' => null,
        ':changed_by' => $_SESSION['user_id']
    ]);
    
    // 3. SUPPRESSION du patient
    $delete_query = "DELETE FROM patients WHERE id = :id";
    $delete_stmt = $pdo->prepare($delete_query);
    $delete_stmt->bindParam(":id", $patient_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Patient supprimé avec succès. L'action a été enregistrée dans l'audit.";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du patient";
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur base de données: " . $e->getMessage();
}

header("Location: patients.php");
exit();
?>