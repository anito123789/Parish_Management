<?php
require_once 'db.php';
include 'includes/header.php';

$substation = $_GET['substation'] ?? '';

if (empty($substation)) {
    header('Location: statistics.php');
    exit;
}

// Fetch families in this substation
$stmt = $db->prepare("SELECT * FROM families WHERE substation = ? ORDER BY name ASC");
$stmt->execute([$substation]);
$families = $stmt->fetchAll();

// Get parish profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];

// Get substation details
$substation_place = '';
if (!empty($profile['substations'])) {
    $decoded = json_decode($profile['substations'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $sub) {
            if ($sub['name'] === $substation) {
                $substation_place = $sub['place'] ?? '';
                break;
            }
        }
    }
}
$display_name = $substation . ($substation_place ? ' - ' . $substation_place : '');

// Count statistics
$family_count = count($families);
$parishioner_count = 0;
foreach ($families as $f) {
    $count = $db->prepare("SELECT COUNT(*) FROM parishioners WHERE family_id = ?");
    $count->execute([$f['id']]);
    $parishioner_count += $count->fetchColumn();
}
?>

<div class="no-print" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin:0;">🏛️ Substation Report: <?php echo htmlspecialchars($display_name); ?></h1>
            <p style="color: var(--secondary); margin: 5px 0 0 0;">
                <?php echo $family_count; ?> Families | <?php echo $parishioner_count; ?> Parishioners
            </p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="statistics.php" class="btn btn-secondary">← Back to Statistics</a>
            <button onclick="window.print()" class="btn btn-primary">📥 Download PDF</button>
        </div>
    </div>
</div>

<!-- Print Header -->
<div class="print-header" style="display: none;">
    <h2><?php echo htmlspecialchars($profile['church_name'] ?? 'Parish Church'); ?></h2>
    <p><?php echo htmlspecialchars($profile['place'] ?? ''); ?></p>
    <div class="report-title">SUBSTATION REPORT: <?php echo strtoupper(htmlspecialchars($display_name)); ?></div>
    <p style="font-size: 0.9rem; margin-top: 5px;">Generated on <?php echo date('d-m-Y H:i A'); ?></p>
    <p style="font-size: 0.9rem;">Total Families: <?php echo $family_count; ?> | Total Parishioners: <?php echo $parishioner_count; ?></p>
</div>

<div class="card">
    <table class="modern-report-table">
        <thead>
            <tr>
                <th style="width: 5%;">S.No</th>
                <th style="width: 10%;">Family Code</th>
                <th style="width: 20%;">Family Name</th>
                <th style="width: 15%;">Head</th>
                <th style="width: 15%;">Spouse</th>
                <th style="width: 10%;">Anbiyam</th>
                <th style="width: 15%;">Phone</th>
                <th style="width: 10%;">Members</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($families)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        No families found in this substation
                    </td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($families as $f): 
                    $member_count = $db->prepare("SELECT COUNT(*) FROM parishioners WHERE family_id = ?");
                    $member_count->execute([$f['id']]);
                    $members = $member_count->fetchColumn();
                ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $sno++; ?></td>
                        <td><code style="background: #e2e8f0; padding: 2px 5px; border-radius: 4px;"><?php echo htmlspecialchars($f['family_code'] ?? '-'); ?></code></td>
                        <td><strong><?php echo htmlspecialchars($f['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($f['head_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($f['spouse_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($f['anbiyam'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($f['phone'] ?? '-'); ?></td>
                        <td style="text-align: center; font-weight: 600;"><?php echo $members; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.modern-report-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-report-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
}

.modern-report-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.modern-report-table tr:hover td {
    background: #fcfdfe;
}

@media print {
    @page {
        size: A4 landscape;
        margin: 10mm;
    }

    body {
        background: white;
        color: black;
    }

    .no-print {
        display: none !important;
    }

    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    .print-header h2 {
        margin: 0;
        font-size: 18pt;
    }

    .print-header .report-title {
        font-size: 14pt;
        font-weight: bold;
        margin: 10px 0;
    }

    .card {
        box-shadow: none;
        border: none;
        padding: 0;
    }

    .modern-report-table {
        font-size: 9pt;
    }

    .modern-report-table th {
        background: #f0f0f0 !important;
        border: 1px solid #000 !important;
    }

    .modern-report-table td {
        border: 1px solid #ccc !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
