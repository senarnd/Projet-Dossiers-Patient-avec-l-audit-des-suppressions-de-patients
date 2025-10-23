<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function logAudit($table_name, $record_id, $action, $old_values, $new_values, $changed_by) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO audit_log (table_name, record_id, action, old_values, new_values, changed_by) 
              VALUES (:table_name, :record_id, :action, :old_values, :new_values, :changed_by)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":table_name", $table_name);
    $stmt->bindParam(":record_id", $record_id);
    $stmt->bindParam(":action", $action);
    $stmt->bindParam(":old_values", $old_values);
    $stmt->bindParam(":new_values", $new_values);
    $stmt->bindParam(":changed_by", $changed_by);
    
    return $stmt->execute();
}

function hasAccess($required_role) {
    $user_role = $_SESSION['role'] ?? null;
    $roles_hierarchy = ['admin' => 3, 'doctor' => 2, 'nurse' => 1];
    
    return isset($roles_hierarchy[$user_role]) && 
           $roles_hierarchy[$user_role] >= $roles_hierarchy[$required_role];
}
?>