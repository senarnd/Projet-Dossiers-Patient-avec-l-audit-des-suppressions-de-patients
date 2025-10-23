<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système Hospitalier</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏥 Système de Suivi des Patients</h1>
            <div class="user-info">
                <?php if (isset($_SESSION['full_name'])): ?>
                    <span>Bonjour, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                    <a href="logout.php" class="btn-logout">Déconnexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="container">