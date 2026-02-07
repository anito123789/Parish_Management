<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;

// --- Handle Saving to Database ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banns'])) {
    $parishioner_id = $_POST['parishioner_id'] ?: null;
    $ref_no = $_POST['ref_no'] ?? '';

    // Prepare data
    $data = [
        'parishioner_id' => $parishioner_id,
        'ref_no' => $ref_no,
        'date_issued' => date('Y-m-d'),
        'checkbox1' => isset($_POST['checkbox1']) ? 1 : 0,
        'checkbox2' => isset($_POST['checkbox2']) ? 1 : 0,
        'checkbox3' => isset($_POST['checkbox3']) ? 1 : 0,
        'groom_name' => $_POST['groom_name'] ?? '',
        'groom_father' => $_POST['groom_father'] ?? '',
        'groom_mother' => $_POST['groom_mother'] ?? '',
        'groom_place' => $_POST['groom_place'] ?? '',
        'groom_parish' => $_POST['groom_parish'] ?? '',
        'groom_diocese' => $_POST['groom_diocese'] ?? '',
        'groom_dob' => $_POST['groom_dob'] ?? '',
        'groom_baptism_place' => $_POST['groom_baptism_place'] ?? '',
        'groom_baptism_date' => $_POST['groom_baptism_date'] ?? '',
        'bride_name' => $_POST['bride_name'] ?? '',
        'bride_father' => $_POST['bride_father'] ?? '',
        'bride_mother' => $_POST['bride_mother'] ?? '',
        'bride_place' => $_POST['bride_place'] ?? '',
        'bride_parish' => $_POST['bride_parish'] ?? '',
        'bride_diocese' => $_POST['bride_diocese'] ?? '',
        'bride_dob' => $_POST['bride_dob'] ?? '',
        'bride_baptism_place' => $_POST['bride_baptism_place'] ?? '',
        'bride_baptism_date' => $_POST['bride_baptism_date'] ?? '',
        'impediment' => $_POST['impediment'] ?? '',
        'banns1' => $_POST['banns1'] ?? '',
        'banns2' => $_POST['banns2'] ?? '',
        'banns3' => $_POST['banns3'] ?? '',
        'marriage_date' => $_POST['marriage_date'] ?? '',
        'marriage_place' => $_POST['marriage_place'] ?? ''
    ];

    $cols = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));

    // Use REPLACE INTO or similar check
    $stmt = $db->prepare("INSERT INTO banns ($cols) VALUES ($placeholders)");
    $stmt->execute($data);

    echo "<script>alert('Banns saved to database successfully!'); window.location.href='report_banns.php?id=$parishioner_id';</script>";
    exit;
}

$p = [];
if ($id) {
    // Fetch Subject
    $stmt = $db->prepare("SELECT p.*, f.name as family_name, f.address as family_address, f.id as family_id_num 
                          FROM parishioners p 
                          LEFT JOIN families f ON p.family_id = f.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch() ?: [];
}

// Fetch Existing Banns if available
$eb = $db->query("SELECT * FROM banns WHERE parishioner_id = '$id' ORDER BY id DESC LIMIT 1")->fetch() ?: [];

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
$c_name = $profile['church_name'] ?? 'Your Parish Name';
$c_place = $profile['place'] ?? 'Your Place';
$c_diocese = $profile['diocese'] ?? 'Your Diocese';

// Calculate Count for Ref No (Default to empty as requested)
$ref_no_default = "";

// --- Logic to determine initial data ---
$rel = $p['relationship'] ?? '';
$is_male = (strtolower($p['gender'] ?? '') === 'male');

$groom_init = [];
$bride_init = [];

// Fetch Family Head and Spouse (to use as father/mother for children)
$f_husband = $db->query("SELECT * FROM parishioners WHERE family_id = '{$p['family_id']}' AND (relationship = 'Husband' OR relationship = 'Head') LIMIT 1")->fetch() ?: [];
$f_wife = $db->query("SELECT * FROM parishioners WHERE family_id = '{$p['family_id']}' AND (relationship = 'Wife' OR relationship = 'Spouse') LIMIT 1")->fetch() ?: [];

if ($rel === 'Son') {
    $groom_init = $p;
    // For Son, override father/mother with family husband/wife names
    $groom_init['father_name'] = $f_husband['name'] ?? '';
    $groom_init['mother_name'] = $f_wife['name'] ?? '';
    // Bride remains empty for Son
    $bride_init = [];
} else if ($rel === 'Daughter') {
    $bride_init = $p;
    // For Daughter, override father/mother with family husband/wife names
    $bride_init['father_name'] = $f_husband['name'] ?? '';
    $bride_init['mother_name'] = $f_wife['name'] ?? '';
    // Groom remains empty for Daughter
    $groom_init = [];
} else if ($rel === 'Husband' || $rel === 'Head') {
    $groom_init = $p;
    $bride_init = $f_wife;
} else if ($rel === 'Wife' || $rel === 'Spouse') {
    $bride_init = $p;
    $groom_init = $f_husband;
} else {
    if ($is_male)
        $groom_init = $p;
    else
        $bride_init = $p;
}

$groom_baptism = ($groom_init && !empty($groom_init['id'])) ? ($db->query("SELECT * FROM baptisms WHERE parishioner_id = {$groom_init['id']}")->fetch() ?: []) : [];
$bride_baptism = ($bride_init && !empty($bride_init['id'])) ? ($db->query("SELECT * FROM baptisms WHERE parishioner_id = {$bride_init['id']}")->fetch() ?: []) : [];

// --- ASSIGN FINAL VALUES ---
$ref_no = $eb['ref_no'] ?? $ref_no_default;
$cb1 = $eb['checkbox1'] ?? 0;
$cb2 = $eb['checkbox2'] ?? 0;
$cb3 = $eb['checkbox3'] ?? 0;

$g_name = $eb['groom_name'] ?? ($groom_init['name'] ?? '');
$g_father = $eb['groom_father'] ?? ($groom_init['father_name'] ?? '');
$g_mother = $eb['groom_mother'] ?? ($groom_init['mother_name'] ?? '');
$g_place = $eb['groom_place'] ?? ($groom_init['family_address'] ?? '');

// If SON is selected, groom parish is current parish, but if DAUGHTER is selected, groom parish is empty
$g_parish = $eb['groom_parish'] ?? (($rel === 'Daughter') ? '' : $c_name);
$g_diocese = $eb['groom_diocese'] ?? (($rel === 'Daughter') ? '' : $c_diocese);

$g_dob = $eb['groom_dob'] ?? ($groom_init['dob'] ?? '');
$g_dob = ($g_dob && $g_dob !== '0000-00-00') ? date('d-m-Y', strtotime($g_dob)) : '';

$g_baptism_place = (!empty($eb['groom_baptism_place'])) ? $eb['groom_baptism_place'] : ($groom_baptism['place'] ?? '');
$g_baptism_date = (!empty($eb['groom_baptism_date'])) ? $eb['groom_baptism_date'] : ($groom_baptism['date_of_baptism'] ?? ($groom_init['baptism_date'] ?? ''));
$g_baptism_date = ($g_baptism_date && $g_baptism_date !== '0000-00-00') ? date('d-m-Y', strtotime($g_baptism_date)) : '';

$b_name = $eb['bride_name'] ?? ($bride_init['name'] ?? '');
$b_father = $eb['bride_father'] ?? ($bride_init['father_name'] ?? '');
$b_mother = $eb['bride_mother'] ?? ($bride_init['mother_name'] ?? '');
$b_place = $eb['bride_place'] ?? ($bride_init['family_address'] ?? '');

// If DAUGHTER is selected, bride parish is current parish, but if SON is selected, bride parish is empty
$b_parish = $eb['bride_parish'] ?? (($rel === 'Son') ? '' : $c_name);
$b_diocese = $eb['bride_diocese'] ?? (($rel === 'Son') ? '' : $c_diocese);

$b_dob = $eb['bride_dob'] ?? ($bride_init['dob'] ?? '');
$b_dob = ($b_dob && $b_dob !== '0000-00-00') ? date('d-m-Y', strtotime($b_dob)) : '';

$b_baptism_place = (!empty($eb['bride_baptism_place'])) ? $eb['bride_baptism_place'] : ($bride_baptism['place'] ?? '');
$b_baptism_date = (!empty($eb['bride_baptism_date'])) ? $eb['bride_baptism_date'] : ($bride_baptism['date_of_baptism'] ?? ($bride_init['baptism_date'] ?? ''));
$b_baptism_date = ($b_baptism_date && $b_baptism_date !== '0000-00-00') ? date('d-m-Y', strtotime($b_baptism_date)) : '';

$imp_val = $eb['impediment'] ?? '';
$banns1 = $eb['banns1'] ?? '';
$banns2 = $eb['banns2'] ?? '';
$banns3 = $eb['banns3'] ?? '';
$m_date = $eb['marriage_date'] ?? ($p['marriage_date'] ?? '');
$m_date = ($m_date && $m_date !== '0000-00-00') ? date('d-m-Y', strtotime($m_date)) : '';

$m_place = $eb['marriage_place'] ?? '';

// Age Calcs
$groom_age = $g_dob ? date_diff(date_create($g_dob), date_create('today'))->y : 0;
$bride_age = $b_dob ? date_diff(date_create($b_dob), date_create('today'))->y : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Banns of Marriage - Procedure</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm;
        }

        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 20px 0;
            color: #000;
            font-size: 11pt;
            line-height: 1.3;
            background: #f1f5f9;
            /* Soft background for screen */
        }

        .edit-notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            padding: 8px;
            text-align: center;
            margin-bottom: 5px;
            font-family: sans-serif;
            position: fixed;
            top: 0;
            width: 100%;
            border-radius: 0 0 10px 10px;
            z-index: 100;
            opacity: 0.9;
        }

        .container {
            width: 270mm;
            max-width: 95vw;
            /* Fit to screen width */
            min-height: 180mm;
            margin: 40px auto;
            background: #fff;
            padding: 5mm;
            border: 4px double #000;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .header-center {
            text-align: center;
            flex: 1;
        }

        .header h1 {
            font-size: 18pt;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-left,
        .header-right {
            width: 25%;
        }

        .ref-area {
            font-style: italic;
            font-weight: bold;
            margin: 5px 0;
        }

        .greeting {
            margin: 5px 0;
            font-weight: bold;
            font-size: 12pt;
        }

        .checkbox-row {
            margin-bottom: 5px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
        }

        input[type="checkbox"] {
            transform: scale(1.3);
            cursor: pointer;
        }

        .parallel-cols {
            display: flex;
            gap: 20px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 10px 0;
        }

        .col {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .col-header {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        .field-row {
            display: flex;
            gap: 5px;
        }

        .label {
            font-weight: bold;
            white-space: nowrap;
            width: 150px;
        }

        .value {
            border-bottom: 1px dotted #888;
            flex: 1;
            outline: none;
            min-height: 1.2em;
            display: inline-block;
        }

        .age-warning {
            color: #dc2626;
            font-weight: bold;
            border: 1px solid #dc2626;
            padding: 0 4px;
            border-radius: 4px;
            margin-left: 5px;
            font-size: 9pt;
        }

        .footer-details {
            margin-top: 5px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .banns-dates {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .signature-area {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 5px;
        }

        .sig-block {
            text-align: center;
            width: 200px;
        }

        .sig-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            margin-top: 5px;
        }

        .seal-area {
            width: 100px;
            height: 100px;
            border: 1px dashed #ccc;
            margin: 2px auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 10pt;
            text-transform: uppercase;
            cursor: pointer;
            overflow: hidden;
        }

        .seal-img {
            width: 100%;
            height: 100%;
            display: none;
            object-fit: contain;
            padding: 2px;
        }

        .sig-img {
            max-width: 100%;
            max-height: 100%;
            display: none;
            object-fit: contain;
        }

        @media print {

            .no-print,
            .edit-notice {
                display: none !important;
            }

            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .container {
                border: 4px double #000 !important;
                padding: 4mm !important;
                margin: 0 auto !important;
                width: 270mm !important;
                min-height: 180mm !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="edit-notice no-print">
        <strong>💡 Live Preview:</strong> Any field with a dotted line is editable. Click <strong>Save to
            Database</strong> to permanently store these details.
    </div>

    <!-- Toolbar -->
    <div class="no-print"
        style="position: sticky; top: 0; padding: 10px 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(5px); border-bottom: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 1rem; z-index: 1000; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <button type="button" onclick="submitBanns()"
            style="padding: 10px 30px; font-size: 1rem; cursor: pointer; background: #5850ec; color: white; border: none; border-radius: 8px; font-weight:bold;">💾
            Save to Database</button>
        <button type="button" onclick="window.print()"
            style="padding: 10px 30px; font-size: 1rem; cursor: pointer; background: #0d9488; color: white; border: none; border-radius: 8px;">🖨️
            Print Banns (Landscape)</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="ref-area">Ref No: <span id="ref_no" class="value"
                        contenteditable="true"><?php echo $ref_no; ?></span></div>
                <div style="font-weight: bold;">Parish: <?php echo htmlspecialchars($c_name); ?></div>
            </div>
            <div class="header-center">
                <h1>Procedure Before Marriage</h1>
                <p style="margin: 5px 0; font-size: 10pt;">The Roman Catholic Diocese of
                    <?php echo htmlspecialchars($c_diocese); ?>
                </p>
            </div>
            <div class="header-right" style="text-align: right;">
                <div style="font-weight: bold;">Date: <?php echo date('d-m-Y'); ?></div>
                <div style="font-weight: bold;">Diocese: <?php echo htmlspecialchars($c_diocese); ?></div>
            </div>
        </div>

        <div class="greeting">Dear Rev. Father,</div>

        <div class="checkbox-row">
            <div class="checkbox-item"><input type="checkbox" id="cb1" <?php echo $cb1 ? 'checked' : ''; ?>> 1. You may
                write the Banns for</div>
            <div class="checkbox-item"><input type="checkbox" id="cb2" <?php echo $cb2 ? 'checked' : ''; ?>> 2. You may
                publish the banns for</div>
            <div class="checkbox-item"><input type="checkbox" id="cb3" <?php echo $cb3 ? 'checked' : ''; ?>> 3. You may
                assist at the marriage of</div>
        </div>

        <div class="parallel-cols">
            <div class="col">
                <div class="col-header">Bridegroom</div>

                <div class="field-row"><span class="label">Name:</span> <span id="g_name" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_name); ?></span></div>
                <div class="field-row"><span class="label">Father's Name:</span> <span id="g_father" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_father); ?></span></div>
                <div class="field-row"><span class="label">Mother's Name:</span> <span id="g_mother" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_mother); ?></span></div>
                <div class="field-row"><span class="label">From (Place):</span> <span id="g_place" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_place); ?></span></div>
                <div class="field-row"><span class="label">Parish:</span> <span id="g_parish" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_parish); ?></span></div>
                <div class="field-row"><span class="label">Diocese:</span> <span id="g_diocese" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_diocese); ?></span></div>
                <div class="field-row"><span class="label">Born on (DOB):</span> <span id="g_dob" class="value"
                        contenteditable="true"><?php echo $g_dob; ?></span>
                    <?php if ($groom_age > 0 && $groom_age < 18): ?> <span class="age-warning">⚠️ UNDERAGE
                            (<?php echo $groom_age; ?>)</span> <?php endif; ?>
                </div>
                <div class="field-row"><span class="label">Baptized at:</span> <span id="g_baptism_place" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($g_baptism_place); ?></span></div>
                <div class="field-row"><span class="label">Baptism Date:</span> <span id="g_baptism_date" class="value"
                        contenteditable="true"><?php echo $g_baptism_date; ?></span></div>
            </div>

            <div class="col">
                <div class="col-header">Bride</div>

                <div class="field-row"><span class="label">Name:</span> <span id="b_name" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_name); ?></span></div>
                <div class="field-row"><span class="label">Father's Name:</span> <span id="b_father" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_father); ?></span></div>
                <div class="field-row"><span class="label">Mother's Name:</span> <span id="b_mother" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_mother); ?></span></div>
                <div class="field-row"><span class="label">From (Place):</span> <span id="b_place" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_place); ?></span></div>
                <div class="field-row"><span class="label">Parish:</span> <span id="b_parish" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_parish); ?></span></div>
                <div class="field-row"><span class="label">Diocese:</span> <span id="b_diocese" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_diocese); ?></span></div>
                <div class="field-row"><span class="label">Born on (DOB):</span> <span id="b_dob" class="value"
                        contenteditable="true"><?php echo $b_dob; ?></span>
                    <?php if ($bride_age > 0 && $bride_age < 18): ?> <span class="age-warning">⚠️ UNDERAGE
                            (<?php echo $bride_age; ?>)</span> <?php endif; ?>
                </div>
                <div class="field-row"><span class="label">Baptized at:</span> <span id="b_baptism_place" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($b_baptism_place); ?></span></div>
                <div class="field-row"><span class="label">Baptism Date:</span> <span id="b_baptism_date" class="value"
                        contenteditable="true"><?php echo $b_baptism_date; ?></span></div>
            </div>
        </div>

        <div class="footer-details">
            <div class="banns-dates">
                <div class="field-row"><span class="label">Impediment:</span> <span id="impediment" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($imp_val); ?></span></div>
                <div style="margin-top: 10px; font-weight: bold;">Banns will be published on:</div>
                <div class="field-row"><span class="label">1-st Banns Date:</span> <span id="banns1" class="value"
                        contenteditable="true"><?php echo $banns1; ?></span></div>
                <div class="field-row"><span class="label">2-nd Banns Date:</span> <span id="banns2" class="value"
                        contenteditable="true"><?php echo $banns2; ?></span></div>
                <div class="field-row"><span class="label">3-rd Banns Date:</span> <span id="banns3" class="value"
                        contenteditable="true"><?php echo $banns3; ?></span></div>
            </div>
            <div>
                <div class="field-row"><span class="label" style="width: auto;">Marriage is to be celebrated on:</span>
                    <span id="marriage_date" class="value" contenteditable="true"><?php echo $m_date; ?></span>
                </div>
                <div class="field-row" style="margin-top: 5px;"><span class="label" style="width: auto;">at
                        (Place):</span> <span id="marriage_place" class="value"
                        contenteditable="true"><?php echo htmlspecialchars($m_place); ?></span></div>
            </div>
        </div>

        <div class="signature-area"
            style="padding-bottom: 2mm; display: grid; grid-template-columns: 1fr 1fr 1fr; align-items: flex-end;">
            <div></div> <!-- Left balance -->
            <div class="seal-area" onclick="document.getElementById('seal_upload').click()"
                style="margin: 0 auto; <?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'border:none;' : ''; ?>">
                <span id="seal_text"
                    style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'display:none;' : ''; ?>">Seal</span>
                <img id="seal_preview" class="seal-img"
                    style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'display:block;' : ''; ?>"
                    src="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? htmlspecialchars($profile['seal_image']) : ''; ?>">
            </div>
            <div class="sig-block" style="justify-self: end;">
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                    onclick="document.getElementById('sig_upload').click()">
                    <img id="sig_preview" class="sig-img"
                        style="max-height: 60px; <?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? 'display:block;' : ''; ?>"
                        src="<?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                </div>
                <div class="sig-line">Parish Priest</div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Saving -->
    <form id="save_form" method="POST" style="display:none;">
        <input type="hidden" name="save_banns" value="1">
        <input type="hidden" name="parishioner_id" value="<?php echo $id; ?>">
        <input type="hidden" name="ref_no" id="form_ref_no">
        <input type="hidden" name="cb1" id="form_cb1">
        <input type="hidden" name="cb2" id="form_cb2">
        <input type="hidden" name="cb3" id="form_cb3">
        <input type="hidden" name="groom_name" id="form_groom_name">
        <input type="hidden" name="groom_father" id="form_groom_father">
        <input type="hidden" name="groom_mother" id="form_groom_mother">
        <input type="hidden" name="groom_place" id="form_groom_place">
        <input type="hidden" name="groom_parish" id="form_groom_parish">
        <input type="hidden" name="groom_diocese" id="form_groom_diocese">
        <input type="hidden" name="groom_dob" id="form_groom_dob">
        <input type="hidden" name="groom_baptism_place" id="form_groom_baptism_place">
        <input type="hidden" name="groom_baptism_date" id="form_groom_baptism_date">
        <input type="hidden" name="bride_name" id="form_bride_name">
        <input type="hidden" name="bride_father" id="form_bride_father">
        <input type="hidden" name="bride_mother" id="form_bride_mother">
        <input type="hidden" name="bride_place" id="form_bride_place">
        <input type="hidden" name="bride_parish" id="form_bride_parish">
        <input type="hidden" name="bride_diocese" id="form_bride_diocese">
        <input type="hidden" name="bride_dob" id="form_bride_dob">
        <input type="hidden" name="bride_baptism_place" id="form_bride_baptism_place">
        <input type="hidden" name="bride_baptism_date" id="form_bride_baptism_date">
        <input type="hidden" name="impediment" id="form_impediment">
        <input type="hidden" name="banns1" id="form_banns1">
        <input type="hidden" name="banns2" id="form_banns2">
        <input type="hidden" name="banns3" id="form_banns3">
        <input type="hidden" name="marriage_date" id="form_marriage_date">
        <input type="hidden" name="marriage_place" id="form_marriage_place">
    </form>

    <input type="file" id="seal_upload" style="display:none" accept="image/*"
        onchange="previewImg(this, 'seal_preview', 'seal_text')">
    <input type="file" id="sig_upload" style="display:none" accept="image/*" onchange="previewImg(this, 'sig_preview')">

    <script>
        function submitBanns() {
            document.getElementById('form_ref_no').value = document.getElementById('ref_no').innerText;
            document.getElementById('form_cb1').value = document.getElementById('cb1').checked ? 1 : 0;
            document.getElementById('form_cb2').value = document.getElementById('cb2').checked ? 1 : 0;
            document.getElementById('form_cb3').value = document.getElementById('cb3').checked ? 1 : 0;

            document.getElementById('form_groom_name').value = document.getElementById('g_name').innerText;
            document.getElementById('form_groom_father').value = document.getElementById('g_father').innerText;
            document.getElementById('form_groom_mother').value = document.getElementById('g_mother').innerText;
            document.getElementById('form_groom_place').value = document.getElementById('g_place').innerText;
            document.getElementById('form_groom_parish').value = document.getElementById('g_parish').innerText;
            document.getElementById('form_groom_diocese').value = document.getElementById('g_diocese').innerText;
            document.getElementById('form_groom_dob').value = document.getElementById('g_dob').innerText;
            document.getElementById('form_groom_baptism_place').value = document.getElementById('g_baptism_place').innerText;
            document.getElementById('form_groom_baptism_date').value = document.getElementById('g_baptism_date').innerText;

            document.getElementById('form_bride_name').value = document.getElementById('b_name').innerText;
            document.getElementById('form_bride_father').value = document.getElementById('b_father').innerText;
            document.getElementById('form_bride_mother').value = document.getElementById('b_mother').innerText;
            document.getElementById('form_bride_place').value = document.getElementById('b_place').innerText;
            document.getElementById('form_bride_parish').value = document.getElementById('b_parish').innerText;
            document.getElementById('form_bride_diocese').value = document.getElementById('b_diocese').innerText;
            document.getElementById('form_bride_dob').value = document.getElementById('b_dob').innerText;
            document.getElementById('form_bride_baptism_place').value = document.getElementById('b_baptism_place').innerText;
            document.getElementById('form_bride_baptism_date').value = document.getElementById('b_baptism_date').innerText;

            document.getElementById('form_impediment').value = document.getElementById('impediment').innerText;
            document.getElementById('form_banns1').value = document.getElementById('banns1').innerText;
            document.getElementById('form_banns2').value = document.getElementById('banns2').innerText;
            document.getElementById('form_banns3').value = document.getElementById('banns3').innerText;
            document.getElementById('form_marriage_date').value = document.getElementById('marriage_date').innerText;
            document.getElementById('form_marriage_place').value = document.getElementById('marriage_place').innerText;

            document.getElementById('save_form').submit();
        }

        function previewImg(input, previewId, textId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.getElementById(previewId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if (textId) {
                        document.getElementById(textId).style.display = 'none';
                        const area = img.parentElement;
                        area.style.border = 'none';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>