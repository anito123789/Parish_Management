<?php
require_once 'db.php';
include 'includes/header.php';

// --- Substation Filter ---
$selected_substation = $_GET['substation'] ?? '';

// --- General Statistics ---
$substation_where = "";
if ($selected_substation) {
    $substation_where = " WHERE substation = " . $db->quote($selected_substation);
}

$total_families = $db->query("SELECT COUNT(*) FROM families" . $substation_where)->fetchColumn();
$total_parishioners = $selected_substation 
    ? $db->query("SELECT COUNT(*) FROM parishioners WHERE family_id IN (SELECT id FROM families WHERE substation = " . $db->quote($selected_substation) . ")")->fetchColumn()
    : $db->query("SELECT COUNT(*) FROM parishioners")->fetchColumn();
$total_anbiyams = $selected_substation
    ? $db->query("SELECT COUNT(DISTINCT anbiyam) FROM families WHERE substation = " . $db->quote($selected_substation) . " AND anbiyam != '' AND anbiyam IS NOT NULL")->fetchColumn()
    : $db->query("SELECT COUNT(DISTINCT anbiyam) FROM families WHERE anbiyam != '' AND anbiyam IS NOT NULL")->fetchColumn();

// --- Sacrament Statistics Filter ---
$selected_year = $_GET['year'] ?? '';
$year_where = "";
$death_where = "is_deceased = 1";
if ($selected_year) {
    $year_where = " AND strftime('%Y', {field}) = '" . (int) $selected_year . "'";
    $death_where = "is_deceased = 1 AND strftime('%Y', death_date) = '" . (int) $selected_year . "'";
}

// Add substation filter to sacrament queries
$sac_sub_filter = $selected_substation ? " AND p.family_id IN (SELECT id FROM families WHERE substation = " . $db->quote($selected_substation) . ")" : "";

$sacraments = [
    'Baptisms' => $db->query("SELECT COUNT(*) FROM parishioners p WHERE baptism_date IS NOT NULL AND baptism_date != ''" . str_replace('{field}', 'baptism_date', $year_where) . $sac_sub_filter)->fetchColumn(),
    'First Communions' => $db->query("SELECT COUNT(*) FROM parishioners p WHERE communion_date IS NOT NULL AND communion_date != ''" . str_replace('{field}', 'communion_date', $year_where) . $sac_sub_filter)->fetchColumn(),
    'Confirmations' => $db->query("SELECT COUNT(*) FROM parishioners p WHERE confirmation_date IS NOT NULL AND confirmation_date != ''" . str_replace('{field}', 'confirmation_date', $year_where) . $sac_sub_filter)->fetchColumn(),
    'Marriages' => $db->query("SELECT COUNT(*) FROM parishioners p WHERE marriage_date IS NOT NULL AND marriage_date != ''" . str_replace('{field}', 'marriage_date', $year_where) . $sac_sub_filter)->fetchColumn(),
    'Deaths' => $db->query("SELECT COUNT(*) FROM parishioners p WHERE $death_where" . $sac_sub_filter)->fetchColumn()
];

// Get available years for dropdown (e.g., from baptism_date or just last 10 years)
$current_year = date('Y');
$years = range($current_year, $current_year - 20);


// --- Age Groups ---
$age_sub_filter = $selected_substation ? " AND p.family_id IN (SELECT id FROM families WHERE substation = " . $db->quote($selected_substation) . ")" : "";
$under_18 = $db->query("SELECT COUNT(*) FROM parishioners p WHERE is_deceased = 0 AND dob != '' AND (strftime('%Y', 'now') - strftime('%Y', dob)) < 18" . $age_sub_filter)->fetchColumn();
$above_18 = $db->query("SELECT COUNT(*) FROM parishioners p WHERE is_deceased = 0 AND dob != '' AND (strftime('%Y', 'now') - strftime('%Y', dob)) >= 18" . $age_sub_filter)->fetchColumn();
$above_30 = $db->query("SELECT COUNT(*) FROM parishioners p WHERE is_deceased = 0 AND dob != '' AND (strftime('%Y', 'now') - strftime('%Y', dob)) >= 30" . $age_sub_filter)->fetchColumn();
$above_60 = $db->query("SELECT COUNT(*) FROM parishioners p WHERE is_deceased = 0 AND dob != '' AND (strftime('%Y', 'now') - strftime('%Y', dob)) >= 60" . $age_sub_filter)->fetchColumn();

// --- Anbiyam Wise Details ---
$anbiyam_where = $selected_substation ? "WHERE f.substation = " . $db->quote($selected_substation) . " AND f.anbiyam != '' AND f.anbiyam IS NOT NULL" : "WHERE f.anbiyam != '' AND f.anbiyam IS NOT NULL";
$anbiyam_stats = $db->query("
    SELECT 
        f.anbiyam, 
        COUNT(DISTINCT f.id) as family_count, 
        COUNT(p.id) as person_count 
    FROM families f
    LEFT JOIN parishioners p ON f.id = p.family_id
    $anbiyam_where
    GROUP BY f.anbiyam
    ORDER BY f.anbiyam ASC
")->fetchAll();

// --- Substation Wise Details ---
$substation_stats = $db->query("
    SELECT 
        f.substation, 
        COUNT(DISTINCT f.id) as family_count, 
        COUNT(p.id) as person_count 
    FROM families f
    LEFT JOIN parishioners p ON f.id = p.family_id
    WHERE f.substation != '' AND f.substation IS NOT NULL
    GROUP BY f.substation
    ORDER BY f.substation ASC
")->fetchAll();

// Get substation details from profile for display
$profile_data = $db->query("SELECT church_name, place, substations FROM parish_profile LIMIT 1")->fetch();
$substation_details = [];
$all_substations = [];
if (!empty($profile_data['substations'])) {
    $decoded = json_decode($profile_data['substations'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $sub) {
            $substation_details[$sub['name']] = $sub;
            $all_substations[] = $sub['name'];
        }
    }
}
// Add Main Station
$all_substations[] = 'Main Station';

?>

<!-- Print Header -->
<div class="print-header" style="display: none;">
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
        <h2 style="margin: 0; font-size: 18pt; font-weight: bold;"><?php echo htmlspecialchars($profile_data['church_name'] ?? 'Parish'); ?></h2>
        <p style="margin: 2px 0; font-size: 11pt;"><?php echo htmlspecialchars($profile_data['place'] ?? ''); ?></p>
        <h3 style="margin: 10px 0 5px 0; font-size: 14pt; font-weight: bold;">PARISH STATISTICS REPORT</h3>
        <?php if ($selected_substation): ?>
            <p style="margin: 0; font-size: 10pt; font-weight: 600;">
                <?php 
                if ($selected_substation === 'Main Station') {
                    echo 'Main Station Only';
                } else {
                    $sub_display = $substation_details[$selected_substation]['name'] ?? $selected_substation;
                    $sub_place = $substation_details[$selected_substation]['place'] ?? '';
                    echo 'Substation: ' . htmlspecialchars($sub_display . ($sub_place ? ' - ' . $sub_place : ''));
                }
                ?>
            </p>
        <?php else: ?>
            <p style="margin: 0; font-size: 10pt; font-weight: 600;">Whole Parish Report</p>
        <?php endif; ?>
        <p style="margin: 5px 0 0 0; font-size: 9pt;">Generated on: <?php echo date('d-m-Y h:i A'); ?></p>
    </div>
</div>

<div class="statistics-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="margin:0; font-weight: 800; letter-spacing: -0.025em; color: var(--text-main);">📊 Sacraments &
                Parish Statistics</h1>
            <p style="color: var(--secondary); margin: 5px 0 0 0;">Comprehensive overview of your parish metrics.</p>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <form method="GET" class="no-print" style="display: flex; gap: 0.5rem;">
                <select name="substation" onchange="this.form.submit()" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--border);">
                    <option value="" <?php echo $selected_substation === '' ? 'selected' : ''; ?>>🌍 Whole Parish</option>
                    <option value="Main Station" <?php echo $selected_substation === 'Main Station' ? 'selected' : ''; ?>>🏛️ Main Station</option>
                    <?php foreach ($all_substations as $sub): 
                        if ($sub === 'Main Station') continue;
                        $sub_place = isset($substation_details[$sub]) ? $substation_details[$sub]['place'] : '';
                        $display = $sub . ($sub_place ? ' - ' . $sub_place : '');
                    ?>
                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php echo $selected_substation === $sub ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($display); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="year" value="<?php echo htmlspecialchars($selected_year); ?>">
            </form>
            <button onclick="window.print()" class="btn btn-primary no-print">
                📥 Export / Print Report
            </button>
        </div>
    </div>

    <!-- Top Summary Cards -->
    <div class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="stat-card" style="background: linear-gradient(135deg, #6366f1, #4338ca);">
            <div class="stat-icon">🏡</div>
            <div class="stat-info">
                <h3><?php echo $selected_substation ? 'Families in ' . ($selected_substation === 'Main Station' ? 'Main Station' : $selected_substation) : 'Total Families'; ?></h3>
                <div class="stat-value">
                    <?php echo $total_families; ?>
                </div>
            </div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo $selected_substation ? 'Parishioners in ' . ($selected_substation === 'Main Station' ? 'Main Station' : $selected_substation) : 'Total Parishioners'; ?></h3>
                <div class="stat-value">
                    <?php echo $total_parishioners; ?>
                </div>
            </div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="stat-icon">⛪</div>
            <div class="stat-info">
                <h3><?php echo $selected_substation ? 'Anbiyams in ' . ($selected_substation === 'Main Station' ? 'Main Station' : $selected_substation) : 'Active Anbiyams'; ?></h3>
                <div class="stat-value">
                    <?php echo $total_anbiyams; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">

        <!-- Sacraments Section -->
        <div class="card" style="padding: 2rem;">
            <h3
                style="margin-top:0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--primary-dark);">
                🕊️ Sacrament Records
                <?php if ($selected_substation): ?>
                    <span class="print-only" style="font-size: 0.75rem; font-weight: 600; color: #64748b;">
                        (<?php echo htmlspecialchars($selected_substation === 'Main Station' ? 'Main Station' : ($substation_details[$selected_substation]['name'] ?? $selected_substation) . ($substation_details[$selected_substation]['place'] ? ' - ' . $substation_details[$selected_substation]['place'] : '')); ?>)
                    </span>
                <?php endif; ?>
                <form method="GET" class="no-print"
                    style="margin-left: auto; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="hidden" name="substation" value="<?php echo htmlspecialchars($selected_substation); ?>">
                    <select name="year" onchange="this.form.submit()"
                        style="padding: 0.25rem 0.5rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.85rem;">
                        <option value="">All Time</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </h3>
            <div class="sacrament-grid">
                <?php foreach ($sacraments as $name => $count): ?>
                    <div class="sacrament-box">
                        <span class="label">
                            <?php echo $name; ?>
                        </span>
                        <span class="count">
                            <?php echo $count; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Age Demographics Section -->
        <div class="card" style="padding: 2rem;">
            <h3
                style="margin-top:0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--primary-dark);">
                🎂 Age Demographics
                <?php if ($selected_substation): ?>
                    <span class="print-only" style="font-size: 0.75rem; font-weight: 600; color: #64748b;">
                        (<?php echo htmlspecialchars($selected_substation === 'Main Station' ? 'Main Station' : ($substation_details[$selected_substation]['name'] ?? $selected_substation) . ($substation_details[$selected_substation]['place'] ? ' - ' . $substation_details[$selected_substation]['place'] : '')); ?>)
                    </span>
                <?php endif; ?>
            </h3>
            <div class="age-stats">
                <div class="age-item">
                    <div class="label-group">
                        <span class="bullet" style="background: #3b82f6;"></span>
                        Children (Under 18)
                    </div>
                    <span class="value">
                        <?php echo $under_18; ?>
                    </span>
                </div>
                <div class="age-item">
                    <div class="label-group">
                        <span class="bullet" style="background: #10b981;"></span>
                        Adults (18+)
                    </div>
                    <span class="value">
                        <?php echo $above_18; ?>
                    </span>
                </div>
                <div class="age-item">
                    <div class="label-group">
                        <span class="bullet" style="background: #f59e0b;"></span>
                        Professionals (30+)
                    </div>
                    <span class="value">
                        <?php echo $above_30; ?>
                    </span>
                </div>
                <div class="age-item">
                    <div class="label-group">
                        <span class="bullet" style="background: #ef4444;"></span>
                        Senior Citizens (60+)
                    </div>
                    <span class="value">
                        <?php echo $above_60; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Anbiyam Breakdown Table -->
    <div class="card" style="margin-top: 2.5rem; padding: 2rem;">
        <h3
            style="margin-top:0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--primary-dark);">
            📍 Anbiyam-wise Breakdown
            <?php if ($selected_substation): ?>
                <span class="print-only" style="font-size: 0.75rem; font-weight: 600; color: #64748b;">
                    (<?php echo htmlspecialchars($selected_substation === 'Main Station' ? 'Main Station' : ($substation_details[$selected_substation]['name'] ?? $selected_substation) . ($substation_details[$selected_substation]['place'] ? ' - ' . $substation_details[$selected_substation]['place'] : '')); ?>)
                </span>
            <?php endif; ?>
        </h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Anbiyam Name</th>
                    <th style="text-align: center;">Families</th>
                    <th style="text-align: center;">Individual Persons</th>
                    <th>Engagement Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anbiyam_stats as $stat): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-main);">
                            <a href="reports.php?anbiyam=<?php echo urlencode($stat['anbiyam']); ?>&report_mode=families&group_by_anbiyam=1"
                                style="color: var(--primary-dark); text-decoration: none; border-bottom: 2px solid transparent; transition: border 0.2s;"
                                onmouseover="this.style.borderBottomColor='var(--primary)'"
                                onmouseout="this.style.borderBottomColor='transparent'">
                                <?php echo htmlspecialchars($stat['anbiyam']); ?>
                            </a>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #4338ca;">
                            <?php echo $stat['family_count']; ?>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #059669;">
                            <?php echo $stat['person_count']; ?>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar"
                                    style="width: <?php echo min(100, ($stat['person_count'] / ($total_parishioners ?: 1)) * 500); ?>%">
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Substation Breakdown Table -->
    <?php if (!empty($substation_stats)): ?>
    <div class="card" style="margin-top: 2.5rem; padding: 2rem;">
        <h3
            style="margin-top:0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--primary-dark);">
            🏛️ Substation-wise Breakdown
        </h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Substation Name</th>
                    <th style="text-align: center;">Families</th>
                    <th style="text-align: center;">Individual Persons</th>
                    <th>Distribution</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($substation_stats as $stat): 
                    $sub_name = $stat['substation'];
                    $sub_place = isset($substation_details[$sub_name]) ? $substation_details[$sub_name]['place'] : '';
                    $display_name = $sub_name . ($sub_place ? ' - ' . $sub_place : '');
                ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-main);">
                            <a href="report_substation.php?substation=<?php echo urlencode($stat['substation']); ?>"
                                style="color: var(--primary-dark); text-decoration: none; border-bottom: 2px solid transparent; transition: border 0.2s;"
                                onmouseover="this.style.borderBottomColor='var(--primary)'"
                                onmouseout="this.style.borderBottomColor='transparent'">
                                <?php echo htmlspecialchars($display_name); ?>
                            </a>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #4338ca;">
                            <?php echo $stat['family_count']; ?>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #059669;">
                            <?php echo $stat['person_count']; ?>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar"
                                    style="width: <?php echo min(100, ($stat['person_count'] / ($total_parishioners ?: 1)) * 500); ?>%">
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<style>
    .statistics-container {
        margin-bottom: 4rem;
    }

    .stat-card {
        padding: 1.5rem;
        border-radius: 1.25rem;
        color: white;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        font-size: 2.5rem;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.9;
        font-weight: 500;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.05em;
    }

    .sacrament-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .sacrament-box {
        background: #f8fafc;
        padding: 1.25rem;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .sacrament-box .label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
    }

    .sacrament-box .count {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main);
    }

    .age-stats {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .age-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .age-item .label-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        color: #475569;
    }

    .age-item .bullet {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .age-item .value {
        font-weight: 800;
        font-size: 1.25rem;
        color: var(--text-main);
    }

    .stats-table {
        width: 100%;
        border-collapse: collapse;
    }

    .stats-table th {
        text-align: left;
        padding: 1rem;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        border-bottom: 2px solid #f1f5f9;
    }

    .stats-table td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .progress-container {
        background: #f1f5f9;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: var(--primary);
        border-radius: 4px;
    }

    .print-only {
        display: none;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            background: white !important;
            font-size: 10pt !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .no-print {
            display: none !important;
        }

        .print-header {
            display: block !important;
        }

        .statistics-container {
            padding: 0 !important;
            margin: 0 !important;
        }

        .statistics-container > div:first-child {
            display: none !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            margin: 0 0 15px 0 !important;
            padding: 12px !important;
            page-break-inside: avoid;
        }

        .grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
            margin-bottom: 15px !important;
        }

        .stat-card {
            color: black !important;
            border: 2px solid #333 !important;
            background: #f5f5f5 !important;
            padding: 10px !important;
            page-break-inside: avoid;
        }

        .stat-icon {
            font-size: 1.5rem !important;
        }

        .stat-info h3 {
            font-size: 8pt !important;
            color: #333 !important;
        }

        .stat-value {
            font-size: 16pt !important;
            color: #000 !important;
        }

        .card h3 {
            font-size: 11pt !important;
            margin: 0 0 10px 0 !important;
            color: #000 !important;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }

        .sacrament-grid {
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
        }

        .sacrament-box {
            background: #fafafa !important;
            border: 1px solid #ccc !important;
            padding: 8px !important;
        }

        .sacrament-box .label {
            font-size: 7pt !important;
            color: #333 !important;
        }

        .sacrament-box .count {
            font-size: 14pt !important;
            color: #000 !important;
        }

        .age-stats {
            gap: 8px !important;
        }

        .age-item {
            padding-bottom: 6px !important;
            border-bottom: 1px solid #ddd !important;
        }

        .age-item .label-group {
            font-size: 9pt !important;
            color: #333 !important;
        }

        .age-item .value {
            font-size: 12pt !important;
            color: #000 !important;
        }

        .stats-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 9pt !important;
        }

        .stats-table th {
            background: #e5e5e5 !important;
            color: #000 !important;
            border: 1px solid #999 !important;
            padding: 6px 4px !important;
            font-size: 8pt !important;
        }

        .stats-table td {
            border: 1px solid #ccc !important;
            padding: 6px 4px !important;
            color: #000 !important;
        }

        .stats-table a {
            color: #000 !important;
            text-decoration: none !important;
            border: none !important;
        }

        .progress-container {
            background: #e0e0e0 !important;
            border: 1px solid #999 !important;
        }

        .progress-bar {
            background: #666 !important;
        }

        .print-only {
            display: inline !important;
        }

        /* Ensure tables don't break across pages */
        .card:has(.stats-table) {
            page-break-inside: auto;
        }

        .stats-table tbody tr {
            page-break-inside: avoid;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>