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
    
    $patient = [];
    $is_edit = false;
    
    // Si modification, charger les données du patient
    if (isset($_GET['id'])) {
        $is_edit = true;
        $query = "SELECT * FROM patients WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":id", $_GET['id']);
        $stmt->execute();
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$patient) {
            header("Location: patients.php");
            exit();
        }
    }
    
    // Récupérer les anciennes valeurs POUR L'AUDIT
    $old_values = [];
    if ($is_edit) {
        $old_values = $patient;
    }
    
    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'birth_date' => $_POST['birth_date'],
            'gender' => $_POST['gender'],
            'address' => $_POST['address'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'blood_type' => $_POST['blood_type'],
            'allergies' => $_POST['allergies'],
            'medical_conditions' => $_POST['medical_conditions'],
            'current_medications' => $_POST['current_medications']
        ];
        
        if ($is_edit) {
            // Mise à jour AVEC AUDIT
            $query = "UPDATE patients SET ";
            $fields = [];
            foreach ($data as $key => $value) {
                $fields[] = "$key = :$key";
            }
            $fields[] = "updated_at = NOW()";
            $query .= implode(", ", $fields) . " WHERE id = :id";
            
            $stmt = $pdo->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(":id", $_GET['id']);
            
            if ($stmt->execute()) {
                // ✅ ENREGISTREMENT AUDIT - TOUJOURS enregistrer même sans changements
                $audit_query = "INSERT INTO audit_log 
                               (table_name, record_id, action, old_values, new_values, changed_by) 
                               VALUES (:table_name, :record_id, :action, :old_values, :new_values, :changed_by)";
                
                $audit_stmt = $pdo->prepare($audit_query);
                $audit_stmt->execute([
                    ':table_name' => 'patients',
                    ':record_id' => $_GET['id'],
                    ':action' => 'UPDATE',
                    ':old_values' => json_encode($old_values, JSON_UNESCAPED_UNICODE),
                    ':new_values' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ':changed_by' => $_SESSION['user_id']
                ]);
                
                header("Location: patients.php?success=Patient mis à jour avec succès");
                exit();
            }
        } else {
            // Création AVEC AUDIT
            $data['created_by'] = $_SESSION['user_id'];
            
            $query = "INSERT INTO patients (" . implode(", ", array_keys($data)) . ") 
                      VALUES (:" . implode(", :", array_keys($data)) . ")";
            
            $stmt = $pdo->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            if ($stmt->execute()) {
                $patient_id = $pdo->lastInsertId();
                
                // ✅ ENREGISTREMENT AUDIT POUR CRÉATION
                $audit_query = "INSERT INTO audit_log 
                               (table_name, record_id, action, old_values, new_values, changed_by) 
                               VALUES (:table_name, :record_id, :action, :old_values, :new_values, :changed_by)";
                
                $audit_stmt = $pdo->prepare($audit_query);
                $audit_stmt->execute([
                    ':table_name' => 'patients',
                    ':record_id' => $patient_id,
                    ':action' => 'CREATE',
                    ':old_values' => null,
                    ':new_values' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ':changed_by' => $_SESSION['user_id']
                ]);
                
                header("Location: patients.php?success=Patient créé avec succès");
                exit();
            }
        }
    }
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Modifier' : 'Nouveau'; ?> Patient</title>
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
        
        .patient-form-page {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .form-section {
            background: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section h2 {
            margin-bottom: 1.5rem;
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-actions {
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
            font-size: 1rem;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 1rem;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="patients.php" class="btn-primary">Liste des Patients</a>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="patient-form-page">
        <h1><?php echo $is_edit ? 'Modifier le Patient' : 'Nouveau Patient'; ?></h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="patient-form">
            <div class="form-section">
                <h2>Informations Personnelles</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Prénom *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Nom *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="birth_date">Date de Naissance *</label>
                        <input type="date" id="birth_date" name="birth_date" 
                               value="<?php echo $patient['birth_date'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Genre *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Sélectionner</option>
                            <option value="M" <?php echo ($patient['gender'] ?? '') == 'M' ? 'selected' : ''; ?>>Masculin</option>
                            <option value="F" <?php echo ($patient['gender'] ?? '') == 'F' ? 'selected' : ''; ?>>Féminin</option>
                            <option value="Other" <?php echo ($patient['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Adresse</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Informations Médicales</h2>
                
                <div class="form-group">
                    <label for="blood_type">Groupe Sanguin</label>
                    <select id="blood_type" name="blood_type">
                        <option value="">Sélectionner</option>
                        <option value="A+" <?php echo ($patient['blood_type'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($patient['blood_type'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($patient['blood_type'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($patient['blood_type'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo ($patient['blood_type'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($patient['blood_type'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo ($patient['blood_type'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($patient['blood_type'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="allergies">Allergies</label>
                    <textarea id="allergies" name="allergies" rows="3" placeholder="Liste des allergies, séparées par des virgules"><?php echo htmlspecialchars($patient['allergies'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="medical_conditions">Conditions Médicales</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="3" placeholder="Maladies chroniques, antécédents..."><?php echo htmlspecialchars($patient['medical_conditions'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="current_medications">Médicaments Actuels</label>
                    <textarea id="current_medications" name="current_medications" rows="3" placeholder="Médicaments en cours"><?php echo htmlspecialchars($patient['current_medications'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary"><?php echo $is_edit ? 'Mettre à jour' : 'Créer le patient'; ?></button>
                <a href="patients.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>