<?php
require_once 'db.php';

try {
    $db->exec("CREATE TABLE IF NOT EXISTS staging_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        family_head VARCHAR(255),
        phone VARCHAR(50),
        status VARCHAR(50) DEFAULT 'pending',
        data TEXT
    )");
    echo "Staging table created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>