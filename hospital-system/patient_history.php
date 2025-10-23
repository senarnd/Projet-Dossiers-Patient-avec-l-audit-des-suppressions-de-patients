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

if (!isset($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les informations du patient
    $patient_query = "SELECT * FROM patients WHERE id = :id";
    $patient_stmt = $pdo->prepare($patient_query);
    $patient_stmt->bindParam(":id", $patient_id);
    $patient_stmt->execute();
    $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header("Location: patients.php");
        exit();
    }
    
    // ✅ RÉCUPÉRER L'HISTORIQUE RÉEL depuis audit_log
    $history_query = "SELECT al.*, u.full_name, u.role 
                     FROM audit_log al 
                     JOIN users u ON al.changed_by = u.id 
                     WHERE al.table_name = 'patients' AND al.record_id = :id 
                     ORDER BY al.changed_at DESC";
    $history_stmt = $pdo->prepare($history_query);
    $history_stmt->bindParam(":id", $patient_id);
    $history_stmt->execute();
    $history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique du Patient</title>
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
        
        .history-page {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .patient-info {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .history-entry {
            background: white;
            margin-bottom: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .entry-header {
            background: #f8f9fa;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .action {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.875rem;
        }
        
        .action.create { background: #d4edda; color: #155724; }
        .action.update { background: #fff3cd; color: #856404; }
        .action.delete { background: #f8d7da; color: #721c24; }
        
        .changes {
            padding: 1rem;
        }
        
        .change {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        
        .field-name {
            font-weight: bold;
            color: #495057;
        }
        
        .old-value {
            text-decoration: line-through;
            color: #dc3545;
            background: #f8d7da;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            margin: 0 0.5rem;
        }
        
        .new-value {
            color: #28a745;
            font-weight: 500;
            background: #d4edda;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            margin: 0 0.5rem;
        }
        
        .arrow {
            color: #6c757d;
            font-weight: bold;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
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
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .no-history {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .debug-info {
            background: #e9ecef;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.875rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="patients.php" class="btn-primary">Liste des Patients</a>
                <a href="audit_global.php" class="btn-primary">📊 Audit Global</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="history-page">
        <h1>Historique des Modifications</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="patient-info">
            <h2>Patient: <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
            <p>ID: <?php echo $patient['id']; ?> | Date de naissance: <?php echo date('d/m/Y', strtotime($patient['birth_date'])); ?></p>
            
            <!-- DEBUG: Afficher le nombre d'entrées d'historique -->
            <div class="debug-info">
                🔍 Debug: <?php echo count($history); ?> entrée(s) trouvée(s) dans audit_log pour ce patient
            </div>
        </div>
        
        <div class="history-list">
            <?php if (empty($history)): ?>
                <div class="no-history">
                    <h3>📝 Aucun historique enregistré</h3>
                    <p>Les modifications futures seront tracées ici automatiquement.</p>
                    <p><small>Créez ou modifiez ce patient pour voir l'historique apparaître.</small></p>
                </div>
            <?php else: ?>
                <?php foreach ($history as $entry): ?>
                <div class="history-entry">
                    <div class="entry-header">
                        <span class="action <?php echo strtolower($entry['action']); ?>">
                            <?php echo $entry['action']; ?>
                        </span>
                        <span class="user">par <?php echo htmlspecialchars($entry['full_name']); ?> (<?php echo $entry['role']; ?>)</span>
                        <span class="date">le <?php echo date('d/m/Y H:i', strtotime($entry['changed_at'])); ?></span>
                    </div>
                    
                    <div class="changes">
                        <?php if ($entry['action'] == 'UPDATE' && $entry['old_values']): ?>
                            <h4>Modifications détaillées:</h4>
                            <?php
                            $old_data = json_decode($entry['old_values'], true);
                            $new_data = json_decode($entry['new_values'], true);
                            
                            // Vérifier que le JSON est valide
                            if (json_last_error() === JSON_ERROR_NONE && $old_data && $new_data) {
                                foreach ($new_data as $field => $new_value) {
                                    $old_value = $old_data[$field] ?? '';
                                    
                                    // Afficher seulement les champs modifiés
                                    if ($old_value != $new_value) {
                                        $field_label = [
                                            'first_name' => 'Prénom',
                                            'last_name' => 'Nom',
                                            'birth_date' => 'Date de naissance',
                                            'gender' => 'Genre',
                                            'address' => 'Adresse',
                                            'phone' => 'Téléphone',
                                            'email' => 'Email',
                                            'blood_type' => 'Groupe sanguin',
                                            'allergies' => 'Allergies',
                                            'medical_conditions' => 'Conditions médicales',
                                            'current_medications' => 'Médicaments actuels'
                                        ];
                                        
                                        $label = $field_label[$field] ?? $field;
                                        echo "<div class='change'>";
                                        echo "<span class='field-name'>$label:</span> ";
                                        echo "<span class='old-value'>" . htmlspecialchars($old_value) . "</span> ";
                                        echo "<span class='arrow'>→</span> ";
                                        echo "<span class='new-value'>" . htmlspecialchars($new_value) . "</span>";
                                        echo "</div>";
                                    }
                                }
                            } else {
                                echo "<p>Erreur de lecture des données d'audit.</p>";
                            }
                            ?>
                            
                        <?php elseif ($entry['action'] == 'CREATE'): ?>
                            <h4>Patient créé avec les données suivantes:</h4>
                            <?php
                            $initial_data = json_decode($entry['new_values'], true);
                            
                            if (json_last_error() === JSON_ERROR_NONE && $initial_data) {
                                $field_labels = [
                                    'first_name' => 'Prénom',
                                    'last_name' => 'Nom',
                                    'birth_date' => 'Date de naissance',
                                    'gender' => 'Genre',
                                    'address' => 'Adresse',
                                    'phone' => 'Téléphone',
                                    'email' => 'Email',
                                    'blood_type' => 'Groupe sanguin',
                                    'allergies' => 'Allergies',
                                    'medical_conditions' => 'Conditions médicales',
                                    'current_medications' => 'Médicaments actuels'
                                ];
                                
                                foreach ($initial_data as $field => $value) {
                                    if (!empty($value) && $field != 'created_by') {
                                        $label = $field_labels[$field] ?? $field;
                                        echo "<div class='change'>";
                                        echo "<span class='field-name'>$label:</span> ";
                                        echo "<span class='new-value'>" . htmlspecialchars($value) . "</span>";
                                        echo "</div>";
                                    }
                                }
                            } else {
                                echo "<p>Erreur de lecture des données de création.</p>";
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="back-link">
            <a href="patients.php" class="btn-primary">Retour à la liste</a>
            <a href="audit_global.php" class="btn-primary">📊 Audit Global</a>
        </div>
    </div>
</body>
</html>