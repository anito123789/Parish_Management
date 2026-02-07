<?php
require_once 'db.php';
include 'includes/header.php';

// --- Improved Filtering Logic ---
$where = [];
$params = [];

// Handle Deceased status filter
$show_deceased = isset($_GET['show_deceased']) ? 1 : 0;
// If event type is death, we MUST show deceased
if (isset($_GET['event_type']) && $_GET['event_type'] === 'death') {
    $show_deceased = 1;
}

if ($show_deceased) {
    $where[] = "p.is_deceased = 1";
} else {
    $where[] = "p.is_deceased = 0";
}

// Search by name (Case insensitive)
if (!empty($_GET['search'])) {
    $where[] = "p.name LIKE LOWER(?)";
    $params[] = "%" . strtolower($_GET['search']) . "%";
}

// Handle Direct Parishioner ID (from Hub link)
if (!empty($_GET['parishioner_id'])) {
    $where[] = "p.id = ?";
    $params[] = $_GET['parishioner_id'];
}

// Filter by Anbiyam (Case insensitive for robustness)
if (!empty($_GET['anbiyam'])) {
    $where[] = "LOWER(f.anbiyam) = LOWER(?)";
    $params[] = $_GET['anbiyam'];
}

// Filter by Substation
if (!empty($_GET['substation']) && $_GET['substation'] !== 'all_substations') {
    $where[] = "LOWER(f.substation) = LOWER(?)";
    $params[] = $_GET['substation'];
}

// Filter by Pious Association
if (!empty($_GET['pious_association'])) {
    $where[] = "LOWER(p.pious_association) = LOWER(?)";
    $params[] = $_GET['pious_association'];
}

// Filter by Education (Fixed dropdown now)
if (!empty($_GET['education'])) {
    $where[] = "p.education = ?";
    $params[] = $_GET['education'];
}

// Filter by Occupation (New Filter)
if (!empty($_GET['occupation'])) {
    $where[] = "p.occupation = ?";
    $params[] = $_GET['occupation'];
}

// Age filtering
if (!empty($_GET['age_min'])) {
    $where[] = "CAST(strftime('%Y', 'now') - strftime('%Y', p.dob) AS INTEGER) >= ?";
    $params[] = $_GET['age_min'];
}
if (!empty($_GET['age_max'])) {
    $where[] = "CAST(strftime('%Y', 'now') - strftime('%Y', p.dob) AS INTEGER) <= ?";
    $params[] = $_GET['age_max'];
}

// Event Filtering (Date)
$event_field = $_GET['event_type'] ?? '';
$event_month = $_GET['month'] ?? '';
$event_year = $_GET['year'] ?? '';

$field_map = [
    'dob' => 'p.dob',
    'baptism' => 'p.baptism_date',
    'communion' => 'p.communion_date',
    'confirmation' => 'p.confirmation_date',
    'marriage' => 'p.marriage_date',
    'death' => 'p.death_date'
];
$col = $field_map[$event_field] ?? null;

if ($col) {
    // Show only those who actually have a valid date for the selected event
    $where[] = "($col IS NOT NULL AND $col != '' AND $col != '0000-00-00')";

    if ($event_month || $event_year) {
        if ($event_month) {
            $where[] = "strftime('%m', $col) = ?";
            $params[] = str_pad($event_month, 2, '0', STR_PAD_LEFT);
        }
        if ($event_year) {
            $where[] = "strftime('%Y', $col) = ?";
            $params[] = $event_year;
        }
    }
}

// Filter by Gender
if (!empty($_GET['gender'])) {
    $where[] = "LOWER(p.gender) = LOWER(?)";
    $params[] = $_GET['gender'];
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Pagination & Global Mode Settings
$per_page = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
if ($per_page <= 0)
    $per_page = 20;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;
$report_mode = $_GET['report_mode'] ?? 'standard';
$group_by_anbiyam = isset($_GET['group_by_anbiyam']) ? 1 : 0;
$group_by_substation = (!empty($_GET['substation']) && $_GET['substation'] === 'all_substations') ? 1 : 0;

// Pagination & Query Logic Switch
if ($report_mode === 'families') {
    // --- Family-centric Query ---
    $f_where = [];
    $f_params = [];

    if (!empty($_GET['anbiyam'])) {
        $f_where[] = "LOWER(anbiyam) = LOWER(?)";
        $f_params[] = $_GET['anbiyam'];
    }
    if (!empty($_GET['substation']) && $_GET['substation'] !== 'all_substations') {
        $f_where[] = "LOWER(substation) = LOWER(?)";
        $f_params[] = $_GET['substation'];
    }
    if (!empty($_GET['search'])) {
        $f_where[] = "name LIKE LOWER(?)";
        $f_params[] = "%" . strtolower($_GET['search']) . "%";
    }

    $f_where_sql = count($f_where) > 0 ? "WHERE " . implode(" AND ", $f_where) : "";

    // Count Families
    $total_results = $db->prepare("SELECT COUNT(*) FROM families $f_where_sql");
    $total_results->execute($f_params);
    $total_results = $total_results->fetchColumn();
    $total_pages = ceil($total_results / $per_page);

    // Fetch Families for current page
    $f_order = $group_by_substation ? "substation ASC, name ASC" : ($group_by_anbiyam ? "anbiyam ASC, name ASC" : "name ASC");
    $f_sql = "SELECT * FROM families $f_where_sql ORDER BY $f_order LIMIT $per_page OFFSET $offset";
    $f_stmt = $db->prepare($f_sql);
    $f_stmt->execute($f_params);
    $families_list = $f_stmt->fetchAll();

    $results = [];
    $families_grouped = [];
    if (!empty($families_list)) {
        $f_ids = array_column($families_list, 'id');
        $placeholders = implode(',', array_fill(0, count($f_ids), '?'));

        $p_order = $group_by_substation ? "f.substation ASC, f.name ASC" : ($group_by_anbiyam ? "f.anbiyam ASC, f.name ASC" : "f.name ASC");
        $sql = "SELECT p.*, f.name as family_name, f.anbiyam, f.phone as family_phone, f.family_code, f.address as family_address, f.subscription_type, f.subscription_amount, f.substation 
                FROM parishioners p 
                LEFT JOIN families f ON p.family_id = f.id 
                WHERE p.family_id IN ($placeholders)
                ORDER BY $p_order, CASE WHEN LOWER(p.relationship)='head' THEN 0 WHEN LOWER(p.relationship)='spouse' OR LOWER(p.relationship)='wife' THEN 1 ELSE 2 END, p.dob ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($f_ids);
        $results = $stmt->fetchAll();

        // --- Efficient Family Grouping ---
        $families_grouped = [];
        foreach ($results as $item) {
            $fid = $item['family_id'];
            if (!isset($families_grouped[$fid])) {
                $families_grouped[$fid] = [
                    'info' => $item,
                    'father' => '-',
                    'mother' => '-',
                    'sons' => [],
                    'daughters' => [],
                    'others' => [],
                    'educations' => [],
                    'occupations' => []
                ];
            }
            $rel = strtolower($item['relationship'] ?? '');
            $g = strtolower($item['gender'] ?? '');

            if (in_array($rel, ['head', 'husband', 'father'])) {
                $families_grouped[$fid]['father'] = $item['name'];
            } elseif (in_array($rel, ['wife', 'spouse', 'mother'])) {
                $families_grouped[$fid]['mother'] = $item['name'];
            } elseif ($rel === 'son' || ($rel === 'child' && $g === 'male')) {
                $families_grouped[$fid]['sons'][] = $item['name'];
            } elseif ($rel === 'daughter' || ($rel === 'child' && $g === 'female')) {
                $families_grouped[$fid]['daughters'][] = $item['name'];
            } else {
                $families_grouped[$fid]['others'][] = $item['name'] . " (" . $item['relationship'] . ")";
            }

            if (!empty($item['education']))
                $families_grouped[$fid]['educations'][] = $item['name'] . ": " . $item['education'];
            if (!empty($item['occupation']))
                $families_grouped[$fid]['occupations'][] = $item['name'] . ": " . $item['occupation'];
        }
    }

    // Stats for family mode (just total count)
    $total_filtered_count = $total_results;
    $male_count = '-';
    $female_count = '-';
} else {
    // --- Parishioner-centric Query (Standard/Detailed) ---
    // Count Total Results
    $count_sql = "SELECT COUNT(*) FROM parishioners p LEFT JOIN families f ON p.family_id = f.id $where_sql";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_results = $count_stmt->fetchColumn();
    $total_pages = ceil($total_results / $per_page);
    $total_filtered_count = $total_results;

    // Dynamic Join for Sacrament Details
    $join_sql = "LEFT JOIN families f ON p.family_id = f.id ";
    $select_fields = "p.*, f.name as family_name, f.anbiyam, f.phone as family_phone, f.family_code, f.address as family_address, f.subscription_type, f.subscription_amount, f.substation";

    if ($event_field === 'baptism') {
        $join_sql .= " LEFT JOIN baptisms s ON p.id = s.parishioner_id";
        $select_fields .= ", s.minister as s_minister, s.place as s_place";
    } elseif ($event_field === 'communion') {
        $join_sql .= " LEFT JOIN first_communions s ON p.id = s.parishioner_id";
        $select_fields .= ", s.minister as s_minister, s.place as s_place";
    } elseif ($event_field === 'confirmation') {
        $join_sql .= " LEFT JOIN confirmations s ON p.id = s.parishioner_id";
        $select_fields .= ", s.minister as s_minister, s.place as s_place";
    }

    $order_by = "p.name ASC";
    if ($col) {
        $order_by = "strftime('%m', $col) ASC, strftime('%d', $col) ASC, p.name ASC";
    }
    if ($group_by_substation) {
        $order_by = "f.substation ASC, " . $order_by;
    } elseif ($group_by_anbiyam) {
        $order_by = "f.anbiyam ASC, " . $order_by;
    }

    $sql = "SELECT $select_fields 
            FROM parishioners p 
            $join_sql 
            $where_sql 
            ORDER BY $order_by LIMIT $per_page OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Stats
    $male_count_sql = "SELECT COUNT(*) FROM parishioners p LEFT JOIN families f ON p.family_id = f.id $where_sql AND LOWER(p.gender) = 'male'";
    $male_stmt = $db->prepare($male_count_sql);
    $male_stmt->execute($params);
    $male_count = $male_stmt->fetchColumn();

    $female_count_sql = "SELECT COUNT(*) FROM parishioners p LEFT JOIN families f ON p.family_id = f.id $where_sql AND LOWER(p.gender) = 'female'";
    $female_stmt = $db->prepare($female_count_sql);
    $female_stmt->execute($params);
    $female_count = $female_stmt->fetchColumn();
}

// Get unique values for filters
$anbiyams = $db->query("SELECT DISTINCT anbiyam FROM families WHERE anbiyam IS NOT NULL AND anbiyam != '' ORDER BY anbiyam ASC")->fetchAll(PDO::FETCH_COLUMN);
$substations = $db->query("SELECT DISTINCT substation FROM families WHERE substation IS NOT NULL AND substation != '' ORDER BY substation ASC")->fetchAll(PDO::FETCH_COLUMN);

// Get substation details from parish profile for display
$profile_data = $db->query("SELECT substations FROM parish_profile LIMIT 1")->fetch();
$substation_details = [];
if (!empty($profile_data['substations'])) {
    $decoded = json_decode($profile_data['substations'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $sub) {
            $substation_details[$sub['name']] = $sub;
        }
    }
}

$pious = $db->query("SELECT DISTINCT pious_association FROM parishioners WHERE pious_association IS NOT NULL AND pious_association != '' ORDER BY pious_association ASC")->fetchAll(PDO::FETCH_COLUMN);
$educations = $db->query("SELECT DISTINCT education FROM parishioners WHERE education IS NOT NULL AND education != '' ORDER BY education ASC")->fetchAll(PDO::FETCH_COLUMN);
$occupations = $db->query("SELECT DISTINCT occupation FROM parishioners WHERE occupation IS NOT NULL AND occupation != '' ORDER BY occupation ASC")->fetchAll(PDO::FETCH_COLUMN);

// Statistics are already calculated above with total_filtered_count, male_count, female_count
?>

<div class="no-print">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="margin:0; font-weight: 800; letter-spacing: -0.025em; color: var(--text-main);">🖨️ Advanced
                Reports</h1>
            <p style="color: var(--secondary); margin: 5px 0 0 0;">Filter, analyze, and export parish data with ease.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" onclick="window.print()" class="btn btn-primary">
                <span style="font-size: 1.1rem;">📥</span> Download / Print PDF
            </button>
        </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid quick-stats-bar"
        style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="card" style="padding: 1.25rem; margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div
                style="background: var(--primary-light); color: var(--primary-dark); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                👥</div>
            <div>
                <div style="font-size: 0.8rem; color: var(--secondary); font-weight: 600;">Found Results</div>
                <div style="font-size: 1.25rem; font-weight: 800;"><?php echo $total_filtered_count; ?></div>
            </div>
        </div>
        <div class="card" style="padding: 1.25rem; margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div
                style="background: #e0f2fe; color: #0369a1; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                👨</div>
            <div>
                <div style="font-size: 0.8rem; color: var(--secondary); font-weight: 600;">Males</div>
                <div style="font-size: 1.25rem; font-weight: 800;"><?php echo $male_count; ?></div>
            </div>
        </div>
        <div class="card" style="padding: 1.25rem; margin-bottom: 0; display: flex; align-items: center; gap: 1rem;">
            <div
                style="background: #fdf2f8; color: #be185d; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                👩</div>
            <div>
                <div style="font-size: 0.8rem; color: var(--secondary); font-weight: 600;">Females</div>
                <div style="font-size: 1.25rem; font-weight: 800;"><?php echo $female_count; ?></div>
            </div>
        </div>
    </div>

    <!-- Modern Filter Form -->
    <form method="GET" class="card filter-card"
        style="padding: 2rem; border: 1px solid var(--border); background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
        <div class="filter-section">
            <h3
                style="margin: 0 0 1.5rem 0; font-size: 1rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.5rem;">
                🔍 Search & General Filters
            </h3>
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label><i class="icon">👤</i> Name</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                        placeholder="Search by name...">
                </div>
                <div class="form-group">
                    <label><i class="icon">⚥</i> Gender</label>
                    <select name="gender">
                        <option value="">All Genders</option>
                        <option value="male" <?php echo ($_GET['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male
                        </option>
                        <option value="female" <?php echo ($_GET['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">🏘️</i> Anbiyam</label>
                    <select name="anbiyam">
                        <option value="">All Anbiyams</option>
                        <?php foreach ($anbiyams as $a): ?>
                            <option value="<?php echo htmlspecialchars($a ?? ''); ?>" <?php echo ($_GET['anbiyam'] ?? '') == $a ? 'selected' : ''; ?>><?php echo htmlspecialchars($a ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">🏛️</i> Substation</label>
                    <select name="substation">
                        <option value="">No Filter</option>
                        <option value="all_substations" <?php echo ($_GET['substation'] ?? '') == 'all_substations' ? 'selected' : ''; ?>>📋 All Substations (Grouped)</option>
                        <?php foreach ($substations as $s): 
                            $sub_place = isset($substation_details[$s]) ? $substation_details[$s]['place'] : '';
                            $display = $s . ($sub_place ? ' - ' . $sub_place : '');
                        ?>
                            <option value="<?php echo htmlspecialchars($s ?? ''); ?>" <?php echo ($_GET['substation'] ?? '') == $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($display); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">🤝</i> Association</label>
                    <select name="pious_association">
                        <option value="">All Associations</option>
                        <?php foreach ($pious as $po): ?>
                            <option value="<?php echo htmlspecialchars($po ?? ''); ?>" <?php echo ($_GET['pious_association'] ?? '') == $po ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($po ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">🎓</i> Education</label>
                    <select name="education">
                        <option value="">All Educations</option>
                        <?php foreach ($educations as $edu): ?>
                            <option value="<?php echo htmlspecialchars($edu ?? ''); ?>" <?php echo ($_GET['education'] ?? '') == $edu ? 'selected' : ''; ?>><?php echo htmlspecialchars($edu ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">💼</i> Occupation</label>
                    <select name="occupation">
                        <option value="">All Occupations</option>
                        <?php foreach ($occupations as $occ): ?>
                            <option value="<?php echo htmlspecialchars($occ ?? ''); ?>" <?php echo ($_GET['occupation'] ?? '') == $occ ? 'selected' : ''; ?>><?php echo htmlspecialchars($occ ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="filter-divider"></div>

        <div class="filter-section">
            <h3
                style="margin: 0 0 1.5rem 0; font-size: 1rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.5rem;">
                📅 Event & Age Filters
            </h3>
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label><i class="icon">🎂</i> Age Range</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="number" name="age_min" value="<?php echo $_GET['age_min'] ?? ''; ?>"
                            placeholder="Min" style="padding: 0.75rem;">
                        <span style="color: var(--secondary); font-weight: 600;">-</span>
                        <input type="number" name="age_max" value="<?php echo $_GET['age_max'] ?? ''; ?>"
                            placeholder="Max" style="padding: 0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="icon">⭐</i> Filter By Event</label>
                    <select name="event_type">
                        <option value="">No Date Filter</option>
                        <option value="dob" <?php echo ($_GET['event_type'] ?? '') == 'dob' ? 'selected' : ''; ?>>Date of
                            Birth</option>
                        <option value="baptism" <?php echo ($_GET['event_type'] ?? '') == 'baptism' ? 'selected' : ''; ?>>
                            Baptism Date</option>
                        <option value="communion" <?php echo ($_GET['event_type'] ?? '') == 'communion' ? 'selected' : ''; ?>>First Communion</option>
                        <option value="confirmation" <?php echo ($_GET['event_type'] ?? '') == 'confirmation' ? 'selected' : ''; ?>>Confirmation</option>
                        <option value="marriage" <?php echo ($_GET['event_type'] ?? '') == 'marriage' ? 'selected' : ''; ?>>Marriage Date</option>
                        <option value="death" <?php echo ($_GET['event_type'] ?? '') == 'death' ? 'selected' : ''; ?>>
                            Death Date</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">🗓️</i> Month</label>
                    <select name="month">
                        <option value="">Any Month</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($_GET['month'] ?? '') == $m ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="icon">🔗</i> Year</label>
                    <input type="number" name="year" value="<?php echo $_GET['year'] ?? ''; ?>" placeholder="e.g. 2024">
                </div>
                <div class="form-group">
                    <label><i class="icon">📊</i> Show</label>
                    <select name="limit">
                        <?php foreach ([2, 5, 10, 20, 50, 100, 200, 500, 1000] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $per_page == $l ? 'selected' : ''; ?>>
                                <?php echo $l == 1000 ? 'All' : $l; ?> per page
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div
            style="display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <label class="custom-checkbox">
                    <input type="checkbox" name="show_deceased" value="1" <?php echo $show_deceased ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    Show Deceased
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" name="group_by_anbiyam" value="1" <?php echo $group_by_anbiyam ? 'checked' : ''; ?>>
                    <span class="checkmark"></span>
                    Group by Anbiyam
                </label>
                <div class="form-group" style="margin-bottom:0;">
                    <select name="report_mode"
                        style="padding: 0.5rem 1rem; border-radius: 12px; border: 1px solid var(--border);">
                        <option value="standard" <?php echo $report_mode == 'standard' ? 'selected' : ''; ?>>Standard List
                        </option>
                        <option value="detailed" <?php echo $report_mode == 'detailed' ? 'selected' : ''; ?>>Detailed
                            Directory</option>
                        <option value="families" <?php echo $report_mode == 'families' ? 'selected' : ''; ?>>Family-wise
                            Details</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="reports.php" class="btn btn-secondary"
                    style="border-radius: 12px; padding: 0.75rem 1.5rem;">Reset</a>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; border-radius: 12px;">Apply
                    Filters</button>
            </div>
        </div>
    </form>
</div>

<!-- Report Container -->
<div class="report-results card" style="border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
    <!-- Print Only Header -->
    <div class="print-header">
        <h2><?php echo htmlspecialchars($sidebar_profile['church_name'] ?? 'Parish'); ?></h2>
        <p><?php echo htmlspecialchars($sidebar_profile['place'] ?? ''); ?></p>
        <div class="report-title">
            <?php
            $title_map = [
                'dob' => 'BIRTHDAY LIST REPORT',
                'baptism' => 'BAPTISM LIST REPORT',
                'communion' => 'FIRST COMMUNION LIST REPORT',
                'confirmation' => 'CONFIRMATION LIST REPORT',
                'marriage' => 'MARRIAGE LIST REPORT',
                'death' => 'DEATH LIST REPORT'
            ];
            $report_title = $title_map[$event_field] ?? 'PARISHIONER DIRECTORY REPORT';
            
            // Add substation to title if filtered
            if (!empty($_GET['substation'])) {
                if ($_GET['substation'] === 'all_substations') {
                    $report_title .= ' - ALL SUBSTATIONS';
                } else {
                    $sub_name = $_GET['substation'];
                    $sub_place = isset($substation_details[$sub_name]) ? $substation_details[$sub_name]['place'] : '';
                    $sub_display = $sub_name . ($sub_place ? ' - ' . $sub_place : '');
                    $report_title .= ' - ' . strtoupper($sub_display);
                }
            }
            
            echo $report_title;
            ?>
        </div>
        <p style="font-size: 0.9rem; margin-top: 5px;">Generated on <?php echo date('d-m-Y H:i A'); ?></p>
    </div>

    <div class="table-responsive">
        <table class="modern-report-table">
            <thead>
                <tr>
                    <?php if ($report_mode === 'detailed'): ?>
                        <th style="width: 20%;">Name</th>
                        <th style="width: 20%;">Parents</th>
                        <th style="width: 12%;">DOB</th>
                        <th style="width: 12%;">Education</th>
                        <th style="width: 12%;">Occupation</th>
                        <th style="width: 24%;">Contact</th>
                    <?php elseif ($report_mode === 'families'): ?>
                        <th style="width: 3%;">S.No</th>
                        <th style="width: 5%;">FID</th>
                        <th style="width: 10%;">Family Name</th>
                        <th style="width: 10%;">Father's Name</th>
                        <th style="width: 10%;">Mother's Name</th>
                        <th style="width: 10%;">Sons / Daughters</th>
                        <th style="width: 8%;">Anbiyam</th>
                        <th style="width: 10%;">Education</th>
                        <th style="width: 10%;">Occupation</th>
                        <th style="width: 10%;">Address & Phone</th>
                    <?php elseif ($event_field === 'dob'): ?>
                        <th style="width: 20%;">Name</th>
                        <th style="width: 15%;">Parents</th>
                        <th style="width: 12%;">Date of Birth</th>
                        <th style="width: 15%;">Anbiyam</th>
                        <th style="text-align: center; width: 8%;">Age</th>
                        <th style="width: 30%;">Phone Number</th>
                    <?php elseif (in_array($event_field, ['baptism', 'communion', 'confirmation', 'marriage'])): ?>
                        <th style="width: 20%;">Name</th>
                        <th style="width: 20%;">Parents</th>
                        <th style="width: 15%;"><?php echo $event_field === 'marriage' ? 'Spouse' : 'Date'; ?></th>
                        <th style="width: 15%;"><?php echo $event_field === 'marriage' ? 'Date' : 'Place'; ?></th>
                        <th style="width: 15%;">Minister (Priest)</th>
                        <th style="width: 15%;" class="no-print">Certificate</th>
                    <?php else: ?>
                        <th style="width: 25%;">Member Information</th>
                        <th style="width: 20%;">Family & Contact</th>
                        <th style="text-align: center; width: 10%;">Age</th>
                        <th style="width: 25%;">Sacrament History</th>
                        <th style="width: 20%;">Profile Details</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 5rem 2rem;">
                            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🤷‍♂️</div>
                            <h3 style="margin:0; color: var(--text-main);">No Results Found</h3>
                            <p style="color: var(--secondary); margin-top: 0.5rem;">Adjust your filters to see more results.
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $current_group = '';
                    $counter = 0;
                    ?>

                    <?php if ($report_mode === 'families'): ?>
                        <?php foreach ($families_grouped as $fid => $f):
                            // --- Substation Grouping Row for Families ---
                            if ($group_by_substation) {
                                $substation = $f['info']['substation'] ?: 'No Substation';
                                if ($substation !== $current_group) {
                                    $current_group = $substation;
                                    $counter = 0; // Reset for new group
                                    $sub_place = isset($substation_details[$substation]) ? $substation_details[$substation]['place'] : '';
                                    $sub_display = $substation . ($sub_place ? ' - ' . $sub_place : '');
                                    ?>
                                    <tr class="group-header">
                                        <td colspan="10"
                                            style="background: #dbeafe; font-weight: 800; color: #1e40af; border-left: 5px solid #3b82f6; padding: 0.75rem 1rem;">
                                            🏛️ SUBSTATION: <?php echo htmlspecialchars($sub_display); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            // --- Anbiyam Grouping Row for Families ---
                            elseif ($group_by_anbiyam) {
                                $anbiyam = $f['info']['anbiyam'] ?: 'N/A';
                                if ($anbiyam !== $current_group) {
                                    $current_group = $anbiyam;
                                    $counter = 0; // Reset for new group
                                    ?>
                                    <tr class="group-header">
                                        <td colspan="10"
                                            style="background: #f1f5f9; font-weight: 800; color: #1e40af; border-left: 5px solid #1e40af; padding: 0.75rem 1rem;">
                                            ⛪ ANBIYAM: <?php echo htmlspecialchars($anbiyam); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }

                            $counter++;
                            $row_class = $counter % 2 == 0 ? 'row-even' : 'row-odd';
                            $display_sno = ($group_by_anbiyam || $group_by_substation) ? $counter : ($offset + $counter);
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td style="text-align: center; color: var(--secondary); font-weight: 600;">
                                    <?php echo $display_sno; ?>
                                </td>
                                <td><code
                                        style="background: #e2e8f0; padding: 2px 5px; border-radius: 4px; font-weight: bold;"><?php echo htmlspecialchars($f['info']['family_code'] ?? '-'); ?></code>
                                </td>
                                <td><strong><?php echo htmlspecialchars($f['info']['family_name'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($f['father']); ?></td>
                                <td><?php echo htmlspecialchars($f['mother']); ?></td>
                                <td>
                                    <div style="font-size: 0.75rem;">
                                        <strong>Sons:</strong>
                                        <?php echo !empty($f['sons']) ? implode(', ', array_map('htmlspecialchars', $f['sons'])) : '-'; ?><br>
                                        <strong>Dtrs:</strong>
                                        <?php echo !empty($f['daughters']) ? implode(', ', array_map('htmlspecialchars', $f['daughters'])) : '-'; ?>
                                        <?php if (!empty($f['others'])): ?>
                                            <br><strong>Others:</strong>
                                            <?php echo implode(', ', array_map('htmlspecialchars', $f['others'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge badge-anbiyam"
                                        style="font-size: 0.7rem;"><?php echo htmlspecialchars($f['info']['anbiyam'] ?? 'N/A'); ?></span>
                                </td>
                                <td style="font-size: 0.75rem;">
                                    <?php echo !empty($f['educations']) ? implode('<br>', array_map('htmlspecialchars', $f['educations'])) : '-'; ?>
                                </td>
                                <td style="font-size: 0.75rem;">
                                    <?php echo !empty($f['occupations']) ? implode('<br>', array_map('htmlspecialchars', $f['occupations'])) : '-'; ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.75rem; line-height: 1.3;">
                                        <?php echo htmlspecialchars($f['info']['family_address'] ?? '-'); ?><br>
                                        <strong style="color: var(--primary);">📞
                                            <?php echo htmlspecialchars($f['info']['family_phone'] ?? '-'); ?></strong>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($results as $r):
                            $age = $r['dob'] ? date_diff(date_create($r['dob']), date_create('today'))->y : '-';
                            $row_class = $r['is_deceased'] ? 'deceased-row' : '';

                            // --- Substation Grouping Row ---
                            if ($group_by_substation) {
                                $substation = $r['substation'] ?: 'No Substation';
                                if ($substation !== $current_group) {
                                    $current_group = $substation;
                                    $sub_place = isset($substation_details[$substation]) ? $substation_details[$substation]['place'] : '';
                                    $sub_display = $substation . ($sub_place ? ' - ' . $sub_place : '');
                                    ?>
                                    <tr class="group-header">
                                        <td colspan="10"
                                            style="background: #dbeafe; font-weight: 800; color: #1e40af; border-left: 4px solid #3b82f6; padding: 0.75rem 1rem;">
                                            🏛️ SUBSTATION: <?php echo htmlspecialchars($sub_display); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            // --- Anbiyam Grouping Row ---
                            elseif ($group_by_anbiyam) {
                                $anbiyam = $r['anbiyam'] ?: 'N/A';
                                if ($anbiyam !== $current_group) {
                                    $current_group = $anbiyam;
                                    ?>
                                    <tr class="group-header">
                                        <td colspan="10"
                                            style="background: #f8fafc; font-weight: 800; color: #1e40af; border-left: 4px solid #1e40af; padding: 0.75rem 1rem;">
                                            ⛪ ANBIYAM: <?php echo htmlspecialchars($anbiyam); ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }

                            // --- Month Grouping (if event filter) ---
                            if ($col && !$group_by_anbiyam && !$group_by_substation) {
                                $month_val = $r[str_replace('p.', '', $col)] ?? '';
                                if ($month_val && $month_val != '0000-00-00') {
                                    $month = date('F', strtotime($month_val));
                                    if ($month !== $current_group) {
                                        $current_group = $month;
                                        ?>
                                        <tr class="month-header">
                                            <td colspan="10">📅 <?php echo $month; ?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                            }
                            ?>

                            <?php if ($report_mode === 'detailed'): ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><strong><?php echo htmlspecialchars($r['name'] ?? ''); ?></strong></td>
                                    <td>
                                        <small>
                                            F: <?php echo htmlspecialchars($r['father_name'] ?? '-'); ?><br>
                                            M: <?php echo htmlspecialchars($r['mother_name'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><?php echo format_date($r['dob']); ?></td>
                                    <td><?php echo htmlspecialchars($r['education'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($r['occupation'] ?? '-'); ?></td>
                                    <td>
                                        <small>
                                            WhatsApp: <?php echo htmlspecialchars($r['whatsapp'] ?? '-'); ?><br>
                                            Family: <?php echo htmlspecialchars($r['family_phone'] ?? '-'); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php elseif ($event_field === 'dob'): ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><strong><?php echo htmlspecialchars($r['name'] ?? ''); ?></strong></td>
                                    <td>
                                        <small>
                                            F: <?php echo htmlspecialchars($r['father_name'] ?? '-'); ?><br>
                                            M: <?php echo htmlspecialchars($r['mother_name'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><?php echo format_date($r['dob']); ?></td>
                                    <td><span class="badge badge-anbiyam"><?php echo htmlspecialchars($r['anbiyam'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td style="text-align: center;"><?php echo $age; ?></td>
                                    <td>
                                        <small>
                                            Personal/WA: <?php echo htmlspecialchars($r['whatsapp'] ?? '-'); ?><br>
                                            Family: <?php echo htmlspecialchars($r['family_phone'] ?? '-'); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php elseif (in_array($event_field, ['baptism', 'communion', 'confirmation', 'marriage'])): ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><strong><?php echo htmlspecialchars($r['name'] ?? ''); ?></strong></td>
                                    <td>
                                        <small>
                                            F: <?php echo htmlspecialchars($r['father_name'] ?? '-'); ?><br>
                                            M: <?php echo htmlspecialchars($r['mother_name'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><?php
                                    if ($event_field === 'marriage') {
                                        $m_stmt = $db->prepare("SELECT spouse_name FROM marriages WHERE parishioner_id = ?");
                                        $m_stmt->execute([$r['id']]);
                                        echo htmlspecialchars(($m_stmt->fetchColumn() ?: '-') ?? '');
                                    } else {
                                        $d_field = ($event_field === 'baptism') ? 'baptism_date' : (($event_field === 'communion') ? 'communion_date' : 'confirmation_date');
                                        echo format_date($r[$d_field]);
                                    }
                                    ?></td>
                                    <td><?php
                                    if ($event_field === 'marriage') {
                                        echo format_date($r['marriage_date']);
                                    } else {
                                        echo htmlspecialchars($r['s_place'] ?? '-');
                                    }
                                    ?></td>
                                    <td><small><?php echo htmlspecialchars($r['s_minister'] ?? '-'); ?></small></td>
                                    <td class="no-print">
                                        <?php
                                        $cert_page = ($event_field === 'baptism') ? 'report_baptism.php' : (($event_field === 'communion') ? 'report_communion.php' : (($event_field === 'confirmation') ? 'report_confirmation.php' : 'report_marriage.php'));
                                        ?>
                                        <a href="<?php echo $cert_page; ?>?id=<?php echo $r['id']; ?>" class="badge badge-anbiyam"
                                            style="text-decoration: none;">📄 Certificate</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?php if (!empty($r['image']) && file_exists($r['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($r['image'] ?? ''); ?>"
                                                    style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border);">
                                            <?php else: ?>
                                                <div
                                                    style="width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1px solid var(--border);">
                                                    <?php echo strtolower($r['gender'] ?? '') == 'female' ? '👩' : '👨'; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <a href="parishioner_view.php?id=<?php echo $r['id']; ?>"
                                                    style="display: block; font-weight: 700; color: var(--primary-dark); text-decoration: none; font-size: 1rem;">
                                                    <?php echo htmlspecialchars($r['name'] ?? ''); ?>
                                                </a>
                                                <span
                                                    style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--secondary); letter-spacing: 0.025em;">
                                                    <?php echo htmlspecialchars($r['gender'] ?? 'Unspecified'); ?>
                                                    <?php if ($r['is_deceased']): ?>
                                                        <span style="margin-left: 5px; color: #dc2626;">• DECEASED</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            <?php echo htmlspecialchars($r['family_name'] ?? 'No Family'); ?>
                                        </div>
                                        <div style="margin-top: 4px; display: flex; flex-direction: column; gap: 4px;">
                                            <span class="badge badge-anbiyam">📍
                                                <?php echo htmlspecialchars($r['anbiyam'] ?? 'N/A'); ?></span>
                                            <?php if ($r['family_phone']): ?>
                                                <span style="font-size: 0.8rem; color: var(--secondary); font-weight: 500;">📞
                                                    <?php echo htmlspecialchars($r['family_phone'] ?? ''); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);"><?php echo $age; ?>
                                        </div>
                                        <div
                                            style="font-size: 0.7rem; color: var(--secondary); font-weight: 600; text-transform: uppercase;">
                                            Years</div>
                                    </td>
                                    <td>
                                        <div class="sacrament-list">
                                            <?php if ($r['baptism_date']): ?>
                                                <div class="sacrament-item" title="Baptism"><strong>Baptism:</strong>
                                                    <?php echo format_date($r['baptism_date']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($r['communion_date']): ?>
                                                <div class="sacrament-item" title="First Communion"><strong>Communion:</strong>
                                                    <?php echo format_date($r['communion_date']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($r['confirmation_date']): ?>
                                                <div class="sacrament-item" title="Confirmation"><strong>Confirmation:</strong>
                                                    <?php echo format_date($r['confirmation_date']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($r['marriage_date']): ?>
                                                <div class="sacrament-item" title="Marriage"><strong>Marriage:</strong>
                                                    <?php echo format_date($r['marriage_date']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($r['death_date']): ?>
                                                <div class="sacrament-item" title="Death"><strong>Death:</strong>
                                                    <?php echo format_date($r['death_date']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 6px;">
                                            <strong
                                                style="font-size: 0.75rem; color: var(--secondary); display: block;">EDUCATION</strong>
                                            <span
                                                style="font-size: 0.85rem; font-weight: 500;"><?php echo htmlspecialchars($r['education'] ?? '-'); ?></span>
                                        </div>
                                        <div style="margin-bottom: 6px;">
                                            <strong
                                                style="font-size: 0.75rem; color: var(--secondary); display: block;">ASSOCIATION</strong>
                                            <span
                                                class="badge-assoc"><?php echo htmlspecialchars($r['pious_association'] ?? '-'); ?></span>
                                        </div>
                                        <div>
                                            <strong
                                                style="font-size: 0.75rem; color: var(--secondary); display: block;">OCCUPATION</strong>
                                            <span
                                                style="font-size: 0.85rem;"><?php echo htmlspecialchars($r['occupation'] ?? '-'); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Links -->
    <?php if ($total_pages > 1): ?>
        <div class="no-print"
            style="margin-top: 2rem; display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center; padding-bottom: 1rem;">
            <?php
            $query_params = $_GET;
            for ($i = 1; $i <= $total_pages; $i++):
                $query_params['page'] = $i;
                $url = '?' . http_build_query($query_params);
                ?>
                <a href="<?php echo htmlspecialchars($url ?? ''); ?>"
                    class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"
                    style="padding: 0.5rem 1rem; border-radius: 8px;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Modern Report Aesthetics */
    .filter-card {
        transition: all 0.3s ease;
    }

    .filter-divider {
        height: 1px;
        background: linear-gradient(to right, transparent, var(--border), transparent);
        margin: 2rem 0;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        color: #475569;
    }

    .form-group label .icon {
        font-style: normal;
        font-size: 1.1rem;
    }

    /* Modern Table Styles */
    .table-responsive {
        overflow-x: auto;
    }

    .modern-report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-report-table th {
        background: #f8fafc;
        padding: 1.25rem 1rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
    }

    .modern-report-table td {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
        transition: background 0.2s;
    }

    .modern-report-table tr:hover td {
        background: #fcfdfe;
    }

    .deceased-row td {
        background: #fffcfc !important;
    }

    .deceased-row:hover td {
        background: #fff5f5 !important;
    }

    /* Badges & Items */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-anbiyam {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #dbeafe;
    }

    .badge-assoc {
        color: var(--primary-dark);
        font-weight: 700;
        font-size: 0.85rem;
    }

    .sacrament-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sacrament-item {
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--text-main);
        font-weight: 500;
    }

    .sacrament-item strong {
        min-width: 90px;
        display: inline-block;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
    }

    /* Custom Checkbox */
    .custom-checkbox {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid var(--border);
        transition: all 0.2s;
        font-weight: 600;
        color: var(--secondary);
    }

    .custom-checkbox:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .custom-checkbox input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    /* Print Tweaks optimized for A4 Landscape */
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            background: white !important;
            color: black !important;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }

        .no-print,
        .sidebar-toggle,
        .btn,
        .filter-card,
        .quick-stats-bar,
        .top-bar {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .content-wrapper {
            padding: 0 !important;
            max-width: none !important;
        }

        .card {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .print-header {
            display: block !important;
            margin-bottom: 20px;
            text-align: center;
        }

        .print-header h2 {
            font-size: 16pt;
            margin: 0;
        }

        .print-header p {
            font-size: 10pt;
            margin: 2px 0;
        }

        .print-header .report-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding-bottom: 2px;
        }

        .modern-report-table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border: 1px solid #000 !important;
        }

        .modern-report-table th {
            background: #f0f0f0 !important;
            color: black !important;
            border: 1px solid #000 !important;
            font-size: 9pt;
            padding: 6px 4px !important;
            text-transform: uppercase;
        }

        .modern-report-table td {
            border: 1px solid #ccc !important;
            font-size: 8.5pt;
            padding: 6px 4px !important;
            word-wrap: break-word;
            vertical-align: top;
        }

        .month-header td {
            background: #eee !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            font-size: 10pt !important;
        }

        /* Standardized Column Behavior */
        .modern-report-table th,
        .modern-report-table td {
            word-wrap: break-word;
        }

        /* Professional & Other */

        .badge {
            border: 1px solid #ccc !important;
            background: transparent !important;
            color: black !important;
            padding: 0 4px !important;
            font-size: 7.5pt !important;
        }

        .sacrament-item {
            font-size: 8pt !important;
            margin-bottom: 1px;
        }

        .deceased-row td {
            background: #f9f9f9 !important;
            color: #666 !important;
        }

        a {
            text-decoration: none !important;
            color: black !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>