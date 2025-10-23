<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test Hospital System</h1>";
echo "<p>Si vous voyez ce message, PHP fonctionne.</p>";

// Test connexion base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hospital_db", "root", "");
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie!</p>";
    
    // Test table users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Utilisateurs dans la base : " . $result['count'] . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur base de données : " . $e->getMessage() . "</p>";
}

echo "<a href='login.php'>Aller à la page de connexion</a>";
?>