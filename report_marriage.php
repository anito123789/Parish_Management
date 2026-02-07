<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$family_id = $_GET['family_id'] ?? null;

if (!$id && !$family_id)
    exit("ID or Family ID Missing");

if ($family_id && !$id) {
    $stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
    $stmt->execute([$family_id]);
    $family = $stmt->fetch();
    if (!$family)
        exit("Family not found");

    $stmt = $db->prepare("SELECT p.name, p.dob, p.relationship, p.marriage_date, p.father_name, p.mother_name, m.* 
                          FROM parishioners p 
                          LEFT JOIN marriages m ON p.id = m.parishioner_id 
                          WHERE p.family_id = ? AND (p.marriage_date IS NOT NULL AND p.marriage_date != '' AND p.marriage_date != '0000-00-00') 
                          ORDER BY p.dob ASC");
    $stmt->execute([$family_id]);
    $members = $stmt->fetchAll();

    $profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Family Marriage Report - <?php echo htmlspecialchars($family['name']); ?></title>
        <style>
            @page {
                size: A4 portrait;
                margin: 15mm;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                padding: 0;
                background: #fff;
                color: #333;
                font-size: 10pt;
            }

            .header {
                text-align: center;
                border-bottom: 2px solid #0d9488;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .header h1 {
                margin: 0;
                font-size: 18pt;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                table-layout: fixed;
            }

            th,
            td {
                border: 1px solid #e2e8f0;
                padding: 8px 4px;
                text-align: left;
                vertical-align: top;
                word-wrap: break-word;
                overflow: hidden;
            }

            th {
                background: #f0fdfa;
                color: #0f766e;
                font-size: 8pt;
                text-transform: uppercase;
            }

            @media print {
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>

    <body>
        <div class="no-print" style="margin: 20px; text-align: right;">
            <button onclick="window.print()"
                style="padding: 8px 16px; background: #0d9488; color: white; border: none; border-radius: 6px; cursor: pointer;">Print
                Report</button>
        </div>
        <div class="header">
            <h1><?php echo htmlspecialchars($profile['church_name'] ?? 'Parish'); ?></h1>
            <h2 style="color: #0d9488; margin-top: 5px; font-size: 14pt;">Family Marriage Records</h2>
            <p>Family: <strong><?php echo htmlspecialchars($family['name']); ?></strong></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Member Name</th>
                    <th style="width: 10%;">Relation</th>
                    <th style="width: 20%;">Parents</th>
                    <th style="width: 12%;">Marriage Date</th>
                    <th style="width: 15%;">Place</th>
                    <th style="width: 12%;">Minister</th>
                    <th style="width: 16%;">Witnesses</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($m['relationship']); ?></td>
                        <td>
                            <small>
                                F: <?php echo htmlspecialchars($m['father_name'] ?: '-'); ?><br>
                                M: <?php echo htmlspecialchars($m['mother_name'] ?: '-'); ?>
                            </small>
                        </td>
                        <td><?php echo format_date($m['marriage_date']); ?></td>
                        <td><?php echo htmlspecialchars($m['place'] ?: '-'); ?></td>
                        <td><small><?php echo htmlspecialchars($m['minister'] ?: '-'); ?></small></td>
                        <td>
                            <small>
                                <?php if ($m['witness1'])
                                    echo "W1: " . htmlspecialchars($m['witness1']) . "<br>"; ?>
                                <?php if ($m['witness2'])
                                    echo "W2: " . htmlspecialchars($m['witness2']); ?>
                                <?php if (!$m['witness1'] && !$m['witness2'])
                                    echo "-"; ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>

    </html>
    <?php
    exit;
}

// Fetch Subject (the parishioner who got married)
$stmt = $db->prepare("SELECT p.*, f.id as family_id, f.name as family_name, f.address as family_address 
                      FROM parishioners p 
                      LEFT JOIN families f ON p.family_id = f.id 
                      WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p)
    exit("Person not found");

// Fetch Family Context Members (Husband and Wife)
$family_id_ctx = $p['family_id'];
$husband = $db->query("SELECT * FROM parishioners WHERE family_id = $family_id_ctx AND (relationship = 'Husband' OR relationship = 'Head') LIMIT 1")->fetch() ?: [];
$wife = $db->query("SELECT * FROM parishioners WHERE family_id = $family_id_ctx AND (relationship = 'Wife' OR relationship = 'Spouse') LIMIT 1")->fetch() ?: [];

// Fetch Marriage Details (from the primary subject)
$marriage = $db->query("SELECT * FROM marriages WHERE parishioner_id = $id")->fetch() ?: [];

// --- Groom (Husband) Data ---
$groom_p = (!empty($husband)) ? $husband : (($p['gender'] == 'Male') ? $p : []);
$groom_name = $groom_p['name'] ?? ($marriage['husband_name'] ?? '-');

$groom_dob_obj = ($groom_p && !empty($groom_p['dob'])) ? date_create($groom_p['dob']) : false;
$marriage_date_obj = !empty($p['marriage_date']) ? date_create($p['marriage_date']) : date_create('now');

$groom_age = ($groom_dob_obj && $marriage_date_obj) ? date_diff($groom_dob_obj, $marriage_date_obj)->y : '-';
$groom_domicile = $p['family_address'] ?? '-';
$groom_father = $groom_p['father_name'] ?? '-';
$groom_mother = $groom_p['mother_name'] ?? '-';
$groom_status = 'Bachelor';

// --- Bride (Wife) Data ---
$bride_p = (!empty($wife)) ? $wife : (($p['gender'] == 'Female') ? $p : []);
$bride_name = $bride_p['name'] ?? ($marriage['spouse_name'] ?? '-');

$bride_dob_obj = ($bride_p && !empty($bride_p['dob'])) ? date_create($bride_p['dob']) : false;
// $marriage_date_obj is already defined above

$bride_age = ($bride_dob_obj && $marriage_date_obj) ? date_diff($bride_dob_obj, $marriage_date_obj)->y : '-';
$bride_domicile = $p['family_address'] ?? '-';
$bride_father = $bride_p['father_name'] ?? '-';
$bride_mother = $bride_p['mother_name'] ?? '-';
$bride_status = 'Spinster';

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
$c_name = $profile['church_name'] ?? 'Your Parish Name';
$c_place = $profile['place'] ?? 'Your Place';
$c_diocese = $profile['diocese'] ?? 'Your Diocese';

// --- Format Toggle ---
$use_words = (isset($_GET['format']) && $_GET['format'] === 'words');

function smart_date($date, $use_words)
{
    if (!$date || $date == '0000-00-00')
        return '-';
    return format_date($date);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Marriage Certificate - <?php echo htmlspecialchars($p['name']); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #000;
            line-height: 1.4;
            font-size: 11pt;
        }

        .edit-notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            padding: 0.75rem;
            max-width: 800px;
            margin: 1rem auto;
            border-radius: 8px;
            text-align: center;
            font-family: sans-serif;
        }

        .container {
            width: 190mm;
            min-height: 275mm;
            margin: 10px auto;
            background: #fff;
            padding: 8mm;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .border-wrapper {
            border: 4px double #000;
            padding: 8mm;
            min-height: 255mm;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 18pt;
            margin: 0;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 14pt;
            margin: 3px 0;
        }

        .title {
            text-align: center;
            border-bottom: 1px solid #000;
            margin: 10px 0;
            padding-bottom: 3px;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .content-table td {
            padding: 4px 5px;
            vertical-align: top;
            border: 0.5px solid #eee;
        }

        .section-header {
            background: #f8fafc;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 10pt;
        }

        .label {
            font-weight: bold;
            width: 30%;
            font-size: 10pt;
        }

        .value {
            width: 70%;
            border-bottom: 1px dotted #888;
            outline: none;
            min-height: 1.2em;
        }

        .value:hover {
            background: #f8fafc;
        }

        .certification-text {
            margin-top: 20px;
            text-align: center;
            font-size: 10.5pt;
            font-style: italic;
            line-height: 1.5;
        }

        .footer {
            margin-top: auto;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-item {
            text-align: center;
            width: 30%;
        }

        .sig-block {
            width: 45%;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            margin-top: 5px;
        }

        .seal-area {
            width: 90px;
            height: 90px;
            border: 1px dashed #ccc;
            margin: 10px auto 0 auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 8pt;
            text-transform: uppercase;
            cursor: pointer;
            overflow: hidden;
        }

        .seal-img,
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
                background: white;
            }

            .container {
                box-shadow: none;
                margin: 0 auto;
                width: 210mm;
                height: 297mm;
                padding: 10mm;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align:center; padding: 10px;">
        <button onclick="window.print()"
            style="padding: 10px 30px; font-size: 1rem; cursor: pointer; background: #0d9488; color: white; border: none; border-radius: 8px;">🖨️
            Print Marriage Certificate</button>
    </div>

    <div class="edit-notice">
        <strong>💡 Live Preview:</strong> You can edit any dotted field. Click on <strong>Seal</strong> or
        <strong>Parish Priest</strong> to upload an image.
    </div>

    <div class="container">
        <div class="border-wrapper">
            <div class="header">
                <h1 contenteditable="true">The Roman Catholic Diocese of <?php echo htmlspecialchars($c_diocese); ?>
                </h1>
                <h2 contenteditable="true"><?php echo htmlspecialchars($c_name); ?></h2>
                <p contenteditable="true"><?php echo htmlspecialchars($c_place); ?></p>
            </div>

            <div class="title">Extract from the Register of Marriages</div>

            <table class="content-table">
                <tr>
                    <td class="label">Date of Marriage</td>
                    <td class="value" contenteditable="true"><?php echo format_date($p['marriage_date']); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Place of Marriage</td>
                    <td class="value" contenteditable="true">
                        <?php echo htmlspecialchars($marriage['place'] ?? $c_name); ?>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="section-header">Details of Bridegroom</td>
                </tr>
                <tr>
                    <td class="label">Bridegroom's Name</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($groom_name); ?></td>
                </tr>
                <tr>
                    <td class="label">Bachelor/Widower</td>
                    <td class="value" contenteditable="true"><?php echo $groom_status; ?></td>
                </tr>
                <tr>
                    <td class="label">Age</td>
                    <td class="value" contenteditable="true"><?php echo $groom_age; ?></td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td>
                    <td class="value" contenteditable="true">
                        <?php echo $groom_p && $groom_p['dob'] ? format_date($groom_p['dob']) : '-'; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Domicile </td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($groom_domicile); ?></td>
                </tr>
                <tr>
                    <td class="label">Father's Name</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($groom_father); ?></td>
                </tr>
                <tr>
                    <td class="label">Mother's Name</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($groom_mother); ?></td>
                </tr>

                <tr>
                    <td colspan="2" class="section-header">Details of Bride</td>
                </tr>
                <tr>
                    <td class="label">Bride's Name</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($bride_name); ?></td>
                </tr>
                <tr>
                    <td class="label">Spinster/Widow</td>
                    <td class="value" contenteditable="true"><?php echo $bride_status; ?></td>
                </tr>
                <tr>
                    <td class="label">Age</td>
                    <td class="value" contenteditable="true"><?php echo $bride_age; ?></td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td>
                    <td class="value" contenteditable="true">
                        <?php echo $bride_p && $bride_p['dob'] ? format_date($bride_p['dob']) : '-'; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Domicile </td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($bride_domicile); ?></td>
                </tr>
                <tr>
                    <td class="label">Father's Name</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($bride_father); ?></td>
                </tr>
                <tr>
                    <td class="label">Mother's Name</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($bride_mother); ?></td>
                </tr>

                <tr>
                    <td colspan="2" class="section-header">Witnesses & Minister</td>
                </tr>
                <tr>
                    <td class="label">Witness 1</td>
                    <td class="value" contenteditable="true">
                        <?php echo htmlspecialchars($marriage['witness1'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Witness 2</td>
                    <td class="value" contenteditable="true">
                        <?php echo htmlspecialchars($marriage['witness2'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Officiating Minister</td>
                    <td class="value" contenteditable="true">Rev. Fr.
                        <?php echo htmlspecialchars($marriage['minister'] ?? '-'); ?>
                    </td>
                </tr>
            </table>

            <div class="certification-text">
                I certify that this is a true copy of the entry of marriage kept in the Marriage Register at <br>
                <strong contenteditable="true"><?php echo htmlspecialchars($c_name . ', ' . $c_place); ?></strong>.
            </div>

            <div class="footer">
                <div class="footer-item" style="text-align: left; width: 45%;">
                    <div style="margin-bottom: 20px;">Place: <span
                            contenteditable="true"><?php echo htmlspecialchars($c_place); ?></span></div>
                    <div>Date: <span contenteditable="true"><?php echo date('d-m-Y'); ?></span></div>
                </div>

                <div class="sig-block">
                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                        onclick="document.getElementById('sig_upload').click()">
                        <img id="sig_preview" class="sig-img"
                            style="max-height: 50px; <?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? 'display:block;' : ''; ?>"
                            src="<?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                    </div>
                    <div class="sig-line" onclick="document.getElementById('sig_upload').click()">Parish Priest</div>

                    <div class="seal-area" onclick="document.getElementById('seal_upload').click()"
                        style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'border:none;' : ''; ?>">
                        <span id="seal_text"
                            style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'display:none;' : ''; ?>">Seal</span>
                        <img id="seal_preview" class="seal-img"
                            style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'display:block;' : ''; ?>"
                            src="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? htmlspecialchars($profile['seal_image']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Uploads -->
    <input type="file" id="seal_upload" style="display:none" accept="image/*"
        onchange="previewImg(this, 'seal_preview', 'seal_text')">
    <input type="file" id="sig_upload" style="display:none" accept="image/*" onchange="previewImg(this, 'sig_preview')">

    <script>
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