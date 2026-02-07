<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_submissions.php");
    exit;
}

// Fetch submission
$stmt = $db->prepare("SELECT * FROM staging_submissions WHERE id = ?");
$stmt->execute([$id]);
$sub = $stmt->fetch();

if (!$sub || $sub['status'] !== 'pending') {
    die("Submission not found or already processed.");
}

$data = json_decode($sub['data'], true);
$family = $data['family'];
$members = $data['members'];

// 1. Create Family
$head = $family['head_name'];
$spouse = $family['spouse_name']; // Might be empty if not provided in step 1 explicitly, but we rely on members list usually? No, step 1 has spouse field.
$address = $family['address'];
$anbiyam = $family['anbiyam'];
$phone = $family['phone'];
$display_name = $head . ($spouse ? " & $spouse" : "");

// Generate Code
$next = $db->query("SELECT seq FROM sqlite_sequence WHERE name='families'")->fetchColumn();
$next_id = ($next ?: 0) + 1;
$serial = sprintf('%03d', $next_id);

$h_part = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $head), 0, 3));
$s_part = $spouse ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $spouse), 0, 3)) : 'XXX';
$code = $h_part . $s_part . $serial;

// Default sub settings
$sub_type = 'yearly';
$sub_amount = 1200;
$sub_start = date('Y-m-d');

try {
    $db->beginTransaction();

    $sql = "INSERT INTO families (family_code, name, head_name, spouse_name, address, anbiyam, phone, subscription_type, subscription_amount, subscription_start_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$code, $display_name, $head, $spouse, $address, $anbiyam, $phone, $sub_type, $sub_amount, $sub_start]);
    $family_id = $db->lastInsertId();

    // 2. Create Members
    foreach ($members as $m) {
        $name = $m['name'];
        $rel = $m['relationship'];
        $gender = $m['gender'];
        $dob = !empty($m['dob']) ? $m['dob'] : null;
        $edu = $m['education'] ?? '';
        $occ = $m['occupation'] ?? '';

        // Auto-fill parents if Son/Daughter
        $father = null;
        $mother = null;
        if ($rel === 'Son' || $rel === 'Daughter') {
            $father = $head;
            $mother = $spouse;
        }

        $sql = "INSERT INTO parishioners (family_id, name, relationship, gender, dob, education, occupation, father_name, mother_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$family_id, $name, $rel, $gender, $dob, $edu, $occ, $father, $mother]);
        $pid = $db->lastInsertId();

        // 3. Insert Sacraments
        if (!empty($m['baptism_date'])) {
            $stmt = $db->prepare("INSERT INTO baptisms (parishioner_id, place, minister, godfather, godmother) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $pid,
                $m['b_place'] ?? 'Imported',
                $m['b_minister'] ?? '',
                $m['b_godfather'] ?? '',
                $m['b_godmother'] ?? ''
            ]);
            $db->prepare("UPDATE parishioners SET baptism_date = ? WHERE id = ?")->execute([$m['baptism_date'], $pid]);
        }

        if (!empty($m['communion_date'])) {
            $stmt = $db->prepare("INSERT INTO first_communions (parishioner_id, place, minister) VALUES (?, ?, ?)");
            $stmt->execute([
                $pid,
                $m['c_place'] ?? 'Imported',
                $m['c_minister'] ?? ''
            ]);
            $db->prepare("UPDATE parishioners SET communion_date = ? WHERE id = ?")->execute([$m['communion_date'], $pid]);
        }

        if (!empty($m['confirmation_date'])) {
            $stmt = $db->prepare("INSERT INTO confirmations (parishioner_id, place, minister, sponsor) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $pid,
                $m['conf_place'] ?? 'Imported',
                $m['conf_minister'] ?? '',
                $m['conf_sponsor'] ?? ''
            ]);
            $db->prepare("UPDATE parishioners SET confirmation_date = ? WHERE id = ?")->execute([$m['confirmation_date'], $pid]);
        }

        if (!empty($m['marriage_date'])) {
            $stmt = $db->prepare("INSERT INTO marriages (parishioner_id, place, minister, witness1, witness2) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $pid,
                $m['m_place'] ?? 'Imported',
                $m['m_minister'] ?? '',
                $m['m_witness1'] ?? '',
                $m['m_witness2'] ?? ''
            ]);
            $db->prepare("UPDATE parishioners SET marriage_date = ? WHERE id = ?")->execute([$m['marriage_date'], $pid]);
        }

        // 4. Deceased Record
        if (!empty($m['is_deceased']) && $m['is_deceased'] == 1) {
            if (!empty($m['death_date'])) {
                $stmt = $db->prepare("INSERT INTO deaths (parishioner_id, date_of_death, cause, place_of_burial) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $pid,
                    $m['death_date'],
                    $m['death_cause'] ?? '',
                    $m['death_place'] ?? ''
                ]);
                // Update stats
                $db->prepare("UPDATE parishioners SET is_deceased = 1, death_date = ? WHERE id = ?")->execute([$m['death_date'], $pid]);
            } else {
                // Just mark as deceased if no date provided
                $db->prepare("UPDATE parishioners SET is_deceased = 1 WHERE id = ?")->execute([$pid]);
            }
        }
    }

    // Update submission status
    $db->prepare("UPDATE staging_submissions SET status = 'imported' WHERE id = ?")->execute([$id]);

    $db->commit();
    header("Location: admin_submissions.php?msg=imported");

} catch (Exception $e) {
    $db->rollBack();
    die("Import failed: " . $e->getMessage());
}
?>