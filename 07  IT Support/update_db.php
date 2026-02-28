<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

try {
    // Add requested_technician_id column if not exists
    $db->exec("ALTER TABLE tickets ADD COLUMN requested_technician_id INT NULL DEFAULT NULL AFTER technician_id");
    $db->exec("ALTER TABLE tickets ADD CONSTRAINT fk_requested_tech FOREIGN KEY (requested_technician_id) REFERENCES technicians(id) ON DELETE SET NULL");
    
    echo "Database updated successfully! Added requested_technician_id column.";
} catch (PDOException $e) {
    echo "Database might already be updated or error occurred: " . $e->getMessage();
}
?>
