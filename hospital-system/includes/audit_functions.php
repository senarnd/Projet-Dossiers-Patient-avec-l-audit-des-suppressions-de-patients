<?php
/**
 * Fonctions utilitaires pour le système d'audit
 */

/**
 * Convertit un nom de champ en libellé lisible
 */
function getFieldLabel($field) {
    $labels = [
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
    
    return $labels[$field] ?? $field;
}

/**
 * Formate les différences pour l'affichage
 */
function formatChanges($old_values, $new_values) {
    $changes = [];
    
    if (!$old_values || !$new_values) return $changes;
    
    $old_data = json_decode($old_values, true);
    $new_data = json_decode($new_values, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) return $changes;
    
    foreach ($new_data as $field => $new_value) {
        $old_value = $old_data[$field] ?? '';
        
        if ($old_value != $new_value) {
            $changes[] = [
                'field' => $field,
                'label' => getFieldLabel($field),
                'old' => $old_value,
                'new' => $new_value
            ];
        }
    }
    
    return $changes;
}

/**
 * Génère un badge coloré pour l'action
 */
function getActionBadge($action) {
    $classes = [
        'CREATE' => 'action-CREATE',
        'UPDATE' => 'action-UPDATE', 
        'DELETE' => 'action-DELETE'
    ];
    
    $class = $classes[$action] ?? 'action-default';
    
    return "<span class='action-badge $class'>$action</span>";
}

/**
 * Valide et filtre les dates pour l'audit
 */
function validateAuditDates($date_from, $date_to) {
    $errors = [];
    
    if (!empty($date_from) && !strtotime($date_from)) {
        $errors[] = "Date de début invalide";
    }
    
    if (!empty($date_to) && !strtotime($date_to)) {
        $errors[] = "Date de fin invalide";
    }
    
    if (!empty($date_from) && !empty($date_to) && $date_from > $date_to) {
        $errors[] = "La date de début doit être avant la date de fin";
    }
    
    return $errors;
}
?>