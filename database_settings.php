<?php
require_once 'db.php';
include 'includes/header.php';

$message = '';
$error = '';

// Handle actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'backup') {
        $name = backup_database('manual_');
        if ($name) {
            $message = "Manual backup created successfully: $name";
        } else {
            $error = "Failed to create manual backup.";
        }
    } elseif ($_POST['action'] == 'annual_backup') {
        $year = $_POST['year'] ?? date('Y');
        $name = backup_database('annual_' . $year . '_');
        if ($name) {
            $message = "Annual backup for $year created successfully: $name";
        } else {
            $error = "Failed to create annual backup.";
        }
    } elseif ($_POST['action'] == 'restore') {
        $file = $_POST['file'];
        $backup_dir = __DIR__ . '/backups/';
        $db_file = __DIR__ . '/database/parish.db';

        if (file_exists($backup_dir . $file)) {
            // Take a safety backup before restoring
            backup_database('pre_restore_');

            if (copy($backup_dir . $file, $db_file)) {
                $message = "Database restored successfully from $file. Safety backup created.";
            } else {
                $error = "Failed to restore database.";
            }
        }
    } elseif ($_POST['action'] == 'reset') {
        $password = $_POST['password'] ?? '';
        $allowed = ['bas', '123', 'admin'];
        if (!in_array($password, $allowed)) {
            $error = "Incorrect password! Action denied.";
        } else {
            // Take a safety backup before resetting
            backup_database('pre_new_db_');
            $db_file = __DIR__ . '/database/parish.db';
            if (isset($db))
                $db = null;
            if (file_exists($db_file) && unlink($db_file)) {
                $message = "New database created successfully. A safety backup of your old data was created.";
                require 'db.php';
            } else {
                $error = "Failed to create new database.";
            }
        }
    } elseif ($_POST['action'] == 'merge') {
        $file = $_POST['file'];
        $backup_path = __DIR__ . '/backups/' . $file;
        if (file_exists($backup_path)) {
            try {
                backup_database('pre_merge_');
                $other_db = new PDO('sqlite:' . $backup_path);
                $other_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->beginTransaction();

                $family_map = []; // old_id => new_id
                $stmt_f = $db->prepare("INSERT INTO families (family_code, name, head_name, spouse_name, head_image, spouse_image, address, anbiyam, phone, subscription_type, subscription_amount, subscription_start_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($other_db->query("SELECT * FROM families") as $f) {
                    $stmt_f->execute([$f['family_code'], $f['name'], $f['head_name'], $f['spouse_name'], $f['head_image'], $f['spouse_image'], $f['address'], $f['anbiyam'], $f['phone'], $f['subscription_type'], $f['subscription_amount'], $f['subscription_start_date']]);
                    $family_map[$f['id']] = $db->lastInsertId();
                }

                $p_map = []; // old_id => new_id
                $stmt_p = $db->prepare("INSERT INTO parishioners (family_id, name, image, dob, gender, relationship, education, pious_association, occupation, father_name, mother_name, baptism_date, communion_date, confirmation_date, marriage_date, death_date, is_deceased, whatsapp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($other_db->query("SELECT * FROM parishioners") as $p) {
                    $new_fid = $family_map[$p['family_id']] ?? null;
                    $stmt_p->execute([$new_fid, $p['name'], $p['image'], $p['dob'], $p['gender'], $p['relationship'], $p['education'], $p['pious_association'], $p['occupation'], $p['father_name'], $p['mother_name'], $p['baptism_date'], $p['communion_date'], $p['confirmation_date'], $p['marriage_date'], $p['death_date'], $p['is_deceased'], $p['whatsapp']]);
                    if ($new_fid)
                        $p_map[$p['id']] = $db->lastInsertId();
                }

                foreach (['baptisms', 'deaths', 'first_communions', 'confirmations', 'marriages'] as $table) {
                    $cols = array_diff($other_db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1), ['parishioner_id']);
                    $stmt = $db->prepare("INSERT INTO $table (parishioner_id, " . implode(', ', $cols) . ") VALUES (?, " . implode(', ', array_fill(0, count($cols), '?')) . ")");
                    foreach ($other_db->query("SELECT * FROM $table") as $row) {
                        if (isset($p_map[$row['parishioner_id']])) {
                            $vals = [$p_map[$row['parishioner_id']]];
                            foreach ($cols as $c)
                                $vals[] = $row[$c];
                            $stmt->execute($vals);
                        }
                    }
                }

                $stmt_s = $db->prepare("INSERT INTO subscriptions (family_id, amount, year, paid_date) VALUES (?, ?, ?, ?)");
                foreach ($other_db->query("SELECT * FROM subscriptions") as $s)
                    if (isset($family_map[$s['family_id']]))
                        $stmt_s->execute([$family_map[$s['family_id']], $s['amount'], $s['year'], $s['paid_date']]);

                $stmt_pl = $db->prepare("INSERT INTO planner (title, description, event_date) VALUES (?, ?, ?)");
                foreach ($other_db->query("SELECT * FROM planner") as $pl)
                    $stmt_pl->execute([$pl['title'], $pl['description'], $pl['event_date']]);

                $db->commit();
                $message = "Database merged successfully!";
            } catch (Exception $e) {
                if ($db->inTransaction())
                    $db->rollBack();
                $error = "Merge failed: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] == 'delete_backup') {
        $file = $_POST['file'];
        $backup_dir = __DIR__ . '/backups/';
        if (file_exists($backup_dir . $file) && strpos($file, '..') === false) {
            unlink($backup_dir . $file);
            $message = "Backup file $file deleted.";
        }
    }
}

// Handle File Import
if (isset($_FILES['import_file'])) {
    $target_dir = __DIR__ . '/backups/';
    $filename = 'imported_' . date('Y-m-d_H-i-s') . '_' . basename($_FILES["import_file"]["name"]);
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($_FILES["import_file"]["tmp_name"], $target_file)) {
        $message = "File uploaded to backups folder: $filename. You can now restore it from the list below.";
    } else {
        $error = "Error uploading file.";
    }
}

// Fetch existing backups
$backup_dir = __DIR__ . '/backups/';
$backups = [];
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (strpos($file, '.db') !== false) {
            $backups[] = [
                'name' => $file,
                'size' => round(filesize($backup_dir . $file) / 1024, 2) . ' KB',
                'time' => date('d-M-Y H:i:s', filemtime($backup_dir . $file))
            ];
        }
    }
}

// Available years for annual backup
$available_years = $db->query("SELECT DISTINCT strftime('%Y', baptism_date) as year FROM parishioners WHERE baptism_date IS NOT NULL
                UNION SELECT DISTINCT strftime('%Y', created_at) FROM families
                ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN) ?: [date('Y')];

?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>💾 Database Management</h2>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div
            style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 5px solid #10b981;">
            ✅ <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div
            style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 5px solid #ef4444;">
            ❌ <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

        <!-- Backup Actions -->
        <div class="card" style="margin: 0; background: #f8fafc; border: 1px solid #e2e8f0;">
            <h3>Backup Data</h3>
            <p style="font-size: 0.9rem; color: #64748b;">Create a safe copy of your current data.</p>

            <form method="POST" style="margin-bottom: 1.5rem;">
                <input type="hidden" name="action" value="backup">
                <button type="submit" class="btn btn-primary" style="width: 100%;">🚀 Manual Full Backup</button>
            </form>

            <form method="POST" style="border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                <input type="hidden" name="action" value="annual_backup">
                <div class="form-group">
                    <label>Annual Year Backup</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <select name="year" style="flex: 1;">
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary">Backup Year</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Import Actions -->
        <div class="card" style="margin: 0; background: #f8fafc; border: 1px solid #e2e8f0;">
            <h3>Import / Upload</h3>
            <p style="font-size: 0.9rem; color: #64748b;">Upload a previously backed up `.db` file.</p>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="import_file" accept=".db" required style="padding: 0.5rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">📤 Upload Backup File</button>
            </form>

            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                <h4 style="color: #991b1b;">Danger Zone</h4>
                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">This will permanently wipe all
                    current data and start a completely fresh database. A safety backup will be created automatically.
                </p>
                <form method="POST"
                    onsubmit="return confirm('⚠️ CRITICAL WARNING ⚠️\n\nThis action will DELETE ALL FAMILIES, PARISHIONERS, and RECORDS.\nA backup of current data will be saved in your backups folder.\n\nAre you ABSOLUTELY SURE you want to create a new empty database?');">
                    <input type="hidden" name="action" value="reset">
                    <div style="margin-bottom: 1rem;">
                        <input type="password" name="password" placeholder="System Password Required" required
                            style="width: 100%; padding: 0.6rem; border: 2px solid #fee2e2; border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-danger"
                        style="width: 100%; background: #ef4444; border: 2px solid #b91c1c;">✨ Create New Fresh
                        Database</button>
                </form>
            </div>

        </div>
    </div>

    <!-- Backup List -->
    <div style="margin-top: 2rem;">
        <h3>📋 Available Backups</h3>
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Created At</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No backups found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
                                <td><?php echo $b['time']; ?></td>
                                <td><small><?php echo $b['size']; ?></small></td>
                                <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <form method="POST"
                                        onsubmit="return confirm('Restore this backup? Current data will be backed up for safety. WARNING: This replaces ALL current data.');">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="file" value="<?php echo $b['name']; ?>">
                                        <button type="submit" class="btn"
                                            style="padding: 0.4rem 0.8rem; background: #6366f1; color: white; font-size: 0.8rem;">Restore
                                            (Replace)</button>
                                    </form>
                                    <form method="POST"
                                        onsubmit="return confirm('Merge this backup? This will ADD the records from this file to your current database. No data will be deleted.');">
                                        <input type="hidden" name="action" value="merge">
                                        <input type="hidden" name="file" value="<?php echo $b['name']; ?>">
                                        <button type="submit" class="btn"
                                            style="padding: 0.4rem 0.8rem; background: #10b981; color: white; font-size: 0.8rem;">Merge
                                            (Append)</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Delete this backup file permanently?');">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="file" value="<?php echo $b['name']; ?>">
                                        <button type="submit" class="btn"
                                            style="padding: 0.4rem 0.8rem; background: #fee2e2; color: #991b1b; font-size: 0.8rem;">Delete</button>
                                    </form>
                                    <a href="backups/<?php echo $b['name']; ?>" download class="btn"
                                        style="padding: 0.4rem 0.8rem; background: #f1f5f9; font-size: 0.8rem;">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>