<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // We can either delete or mark rejected. Let's mark rejected for history.
    // Actually user might want to delete to clear the list.
    // I'll delete for now to keep it clean, or update status. 
    // "Delete this submission?" was the prompt in admin view.
    $db->prepare("DELETE FROM staging_submissions WHERE id = ?")->execute([$id]);
}

header("Location: admin_submissions.php");
exit;
?>