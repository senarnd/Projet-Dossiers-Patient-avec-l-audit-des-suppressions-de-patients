NOUVELLE STRUCTURE
hospital-system/
├── patients.php              (Ajout bouton supprimer)
├── patient_delete.php        ✅ NOUVEAU (Suppression sécurisée)
├── patient_restore.php       ✅ NOUVEAU (Restauration)
├── corbeille.php             ✅ NOUVEAU (Vue des suppressions)
├── audit_global.php          (Affichera aussi DELETE)
└── audit_log table           (Nouvelles entrées DELETE)

FICHIER 1: patient_delete.php (NOUVEAU)
FICHIER 2: corbeille.php (NOUVEAU)
FICHIER 3: patient_restore.php (NOUVEAU)

MODIFICATIONS DANS patients.php
**Partie 1 : Ajouter le bouton "Corbeille"
CHERCHEZ la section toolbar (~ligne 130) et AJOUTEZ :
//php
<div style="display: flex; gap: 1rem;">
    <a href="patient_form.php" class="btn-success">Nouveau Patient</a>
    <a href="corbeille.php" class="btn-secondary">🗑️ Corbeille</a>    
    <a href="audit_global.php" class="btn-primary">📊 Audit Global</a>
</div>

**Partie 2 : Ajouter le bouton "Supprimer" dans le tableau
CHERCHEZ la section des actions dans le tableau (~ligne 180) :
AVANT 
//php
<td class="actions">
    <a href="patient_form.php?id=<?php echo $patient['id']; ?>" class="btn-edit">Modifier</a>
    <a href="patient_history.php?id=<?php echo $patient['id']; ?>" class="btn-info">Historique</a>
</td>

APRES
//php
<td class="actions">
    <a href="patient_form.php?id=<?php echo $patient['id']; ?>" class="btn-edit">Modifier</a>
    <a href="patient_history.php?id=<?php echo $patient['id']; ?>" class="btn-info">Historique</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="patient_delete.php?id=<?php echo $patient['id']; ?>" 
           class="btn-danger" 
           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ? Cette action est irréversible.')">
            🗑️ Supprimer
        </a>
    <?php endif; ?>
</td>
**Partie 3 : Ajouter le style pour le bouton danger
AJOUTEZ dans la balise <style> dans patients.php :
//css 
.btn-danger {
    background: #dc3545;
    color: white;
    padding: 0.3rem 0.6rem;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    display: inline-block;
}

.btn-danger:hover {
    background: #c82333;
}

🔧 MODIFICATION DANS audit_global.php
Mettre à jour le filtre "Action"
CHERCHEZ le filtre action (~ligne 280) et MODIFIEZ :

AVANT :
//php
<select id="action" name="action">
    <option value="">Toutes les actions</option>
    <option value="CREATE" <?php echo $filters['action'] == 'CREATE' ? 'selected' : ''; ?>>Création</option>
    <option value="UPDATE" <?php echo $filters['action'] == 'UPDATE' ? 'selected' : ''; ?>>Modification</option>
</select>

APRES :
//php
<select id="action" name="action">
    <option value="">Toutes les actions</option>
    <option value="CREATE" <?php echo $filters['action'] == 'CREATE' ? 'selected' : ''; ?>>Création</option>
    <option value="UPDATE" <?php echo $filters['action'] == 'UPDATE' ? 'selected' : ''; ?>>Modification</option>
    <option value="DELETE" <?php echo $filters['action'] == 'DELETE' ? 'selected' : ''; ?>>Suppression</option>
    <option value="RESTORE" <?php echo $filters['action'] == 'RESTORE' ? 'selected' : ''; ?>>Restauration</option>
</select>

Ajouter le style pour RESTORE
AJOUTEZ dans la balise <style> dans audit_global.php et corbeille.php :
//css
.action-RESTORE { background: #cce7ff; color: #004085; }

🎯 TEST IMMÉDIAT
Étapes de test :
Créez les 3 nouveaux fichiers

Modifiez patients.php avec les ajouts

Connectez-vous en tant qu'admin

Testez la suppression d'un patient

Vérifiez la corbeille

Testez la restauration

Résultats attendus :
✅ Suppression enregistrée dans audit_log

✅ Patient disparaît de la liste

✅ Apparaît dans corbeille avec données sauvegardées

✅ Restauration possible

✅ Tout tracé dans l'audit global

Je dois connecter en tant qu'admin pour voir les options de suppression et de restauration
Dans patients.php j'ai ecrit par erreur "Admin" au lieu de "admin" en minuscules dans SESSION['role'], donc j'ai pas pu voir le bouton supprimer. Après correction, tout fonctionne parfaitement.
//php
<td class="actions">
    <a href="patient_form.php?id=<?php echo $patient['id']; ?>" class="btn-edit">Modifier</a>
    <a href="patient_history.php?id=<?php echo $patient['id']; ?>" class="btn-info">Historique</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="patient_delete.php?id=<?php echo $patient['id']; ?>" 
           class="btn-danger" 
           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ? Cette action est irréversible.')">
            🗑️ Supprimer
        </a>
    <?php endif; ?>
</td>

J'ai oublier d'ajouter le style pour le bouton corbeille dans patients.php, donc le bouton n'avait pas le bon style 
//css
.btn-corbeille {
    background: #6c757d;
    color: white;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-corbeille:hover {
    background: #5a6268;
    color: white;
}

J'ai ecrit par erreur class="btn-secondary" au lieu de "btn-corbeille" pour le bouton corbeille dans patients.php dans "Toolbar", donc le style n'était pas appliqué
//php
<a href="corbeille.php" class="btn-secondary">🗑️ Corbeille</a>
Remplacé par
//php
<a href="corbeille.php" class="btn-corbeille">🗑️ Corbeille</a>
