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

    $stmt = $db->prepare("SELECT p.name, p.dob, p.relationship, p.father_name, p.mother_name, p.confirmation_date as p_confirmation_date, c.* 
                          FROM parishioners p 
                          LEFT JOIN confirmations c ON p.id = c.parishioner_id 
                          WHERE p.family_id = ? AND (p.confirmation_date IS NOT NULL AND p.confirmation_date != '' AND p.confirmation_date != '0000-00-00') 
                          ORDER BY p.dob ASC");
    $stmt->execute([$family_id]);
    $members = $stmt->fetchAll();

    $profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Family Confirmation Report - <?php echo htmlspecialchars($family['name']); ?></title>
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
                border-bottom: 2px solid #dc2626;
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
                background: #fef2f2;
                color: #991b1b;
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
                style="padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer;">Print
                Report</button>
        </div>
        <div class="header">
            <h1><?php echo htmlspecialchars($profile['church_name'] ?? 'Parish'); ?></h1>
            <p><?php echo htmlspecialchars(($profile['place'] ?? '') . ' | ' . ($profile['diocese'] ?? '')); ?></p>
            <h2 style="color: #dc2626; margin-top: 5px; font-size: 14pt;">Family Confirmation Records</h2>
            <p>Family: <strong><?php echo htmlspecialchars($family['name']); ?></strong></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Member Name</th>
                    <th style="width: 12%;">Relation</th>
                    <th style="width: 25%;">Parents</th>
                    <th style="width: 12%;">Confirmation Date</th>
                    <th style="width: 15%;">Place</th>
                    <th style="width: 16%;">Minister / Sponsor</th>
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
                        <td><?php echo format_date($m['p_confirmation_date']); ?></td>
                        <td><?php echo htmlspecialchars($m['place'] ?: '-'); ?></td>
                        <td>
                            <small>
                                Min: <?php echo htmlspecialchars($m['minister'] ?: '-'); ?><br>
                                Sp: <?php echo htmlspecialchars($m['sponsor'] ?: '-'); ?>
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

// Fetch Parishioner basic info
$stmt = $db->prepare("SELECT p.*, f.name as family_name, f.address as family_address, f.head_name, f.spouse_name 
                      FROM parishioners p 
                      LEFT JOIN families f ON p.family_id = f.id 
                      WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p)
    exit("Person not found");

// --- Intelligent Parent Detection ---
$father_name = $p['father_name'];
$mother_name = $p['mother_name'];

if (in_array(strtolower($p['relationship'] ?? ''), ['son', 'daughter'])) {
    if (empty($father_name))
        $father_name = $p['head_name'] ?? '';
    if (empty($mother_name))
        $mother_name = $p['spouse_name'] ?? '';
}
if (empty($father_name))
    $father_name = '-';
if (empty($mother_name))
    $mother_name = '-';

// Fetch Sacramental Data (Baptism, Communion, Confirmation)
$baptism = $db->query("SELECT * FROM baptisms WHERE parishioner_id = $id")->fetch() ?: [];
$communion = $db->query("SELECT * FROM first_communions WHERE parishioner_id = $id")->fetch() ?: [];
$confirmation = $db->query("SELECT * FROM confirmations WHERE parishioner_id = $id")->fetch() ?: [];

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
    <title>Confirmation Certificate - <?php echo htmlspecialchars($p['name']); ?></title>
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
            line-height: 1.5;
        }

        .edit-notice {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            padding: 1rem;
            max-width: 800px;
            margin: 1rem auto;
            border-radius: 8px;
            text-align: center;
            font-family: sans-serif;
        }

        .container {
            width: 190mm;
            min-height: 275mm;
            margin: 20px auto;
            background: #fff;
            padding: 8mm;
            position: relative;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .border-wrapper {
            border: 4px double #000;
            padding: 10mm;
            min-height: 255mm;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 22pt;
            margin: 0;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 15pt;
            margin: 5px 0;
        }

        .header p {
            font-size: 10pt;
            margin: 0;
            font-style: italic;
        }

        .title {
            text-align: center;
            border-bottom: 2px solid #000;
            margin: 10px 0;
            padding-bottom: 5px;
        }

        .title h3 {
            font-size: 17pt;
            margin: 0;
            text-transform: uppercase;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
            margin-top: 5px;
        }

        .content-table td {
            padding: 6px 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 38%;
        }

        .colon {
            width: 4%;
            text-align: center;
        }

        .value {
            width: 58%;
            border-bottom: 1px dotted #888;
            font-weight: 500;
            outline: none;
        }

        .value:hover {
            background: #f8fafc;
        }

        .certification-text {
            margin-top: 30px;
            text-align: center;
            font-size: 11pt;
            font-style: italic;
            line-height: 1.6;
        }

        .footer {
            margin-top: auto;
            padding-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-item {
            text-align: center;
            width: 30%;
        }

        .seal-area {
            width: 90px;
            height: 90px;
            border: 1px dashed #ccc;
            margin: 0 auto 10px auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 8pt;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .seal-img,
        .sig-img {
            max-width: 100%;
            max-height: 100%;
            display: none;
            object-fit: contain;
        }

        .sig-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            cursor: pointer;
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
    <div class="no-print" style="text-align:center; padding: 15px;">
        <button onclick="window.print()" class="btn btn-primary"
            style="padding: 10px 30px; font-size: 1.1rem; cursor: pointer; background: #dc2626; color: white; border: none; border-radius: 8px;">🖨️
            Print Confirmation Certificate</button>
    </div>

    <div class="edit-notice">
        <strong>💡 Live Preview:</strong> You can edit any dotted field before printing. Click on <strong>Seal</strong>
        or <strong>Parish Priest</strong> to upload an image.
    </div>

    <div class="container">
        <div class="border-wrapper">
            <div class="header">
                <h1 contenteditable="true"><?php echo htmlspecialchars($c_name); ?></h1>
                <h2 contenteditable="true"><?php echo htmlspecialchars($c_place); ?></h2>
                <p contenteditable="true"><?php echo htmlspecialchars($c_diocese); ?></p>
            </div>

            <div class="title">
                <h3>Certificate of Confirmation</h3>
            </div>

            <table class="content-table">
                <tr>
                    <td class="label">Name of Candidate</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($p['name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Father's Name</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($father_name); ?></td>
                </tr>
                <tr>
                    <td class="label">Mother's Name</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($mother_name); ?></td>
                </tr>
                <tr>
                    <td class="label">Baptized On (Date)</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo smart_date($p['baptism_date'], $use_words); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Baptized At (Place)</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($baptism['place'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Received First Communion On</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo smart_date($p['communion_date'], $use_words); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Place of First Communion</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($communion['place'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Minister of Communion</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">Rev. Fr.
                        <?php echo htmlspecialchars($communion['minister'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Date of Confirmation</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">
                        <?php echo smart_date($p['confirmation_date'], $use_words); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Place of Confirmation</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">
                        <?php echo htmlspecialchars($confirmation['place'] ?? $c_name); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Minister of Confirmation</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">Rev. Fr.
                        <?php echo htmlspecialchars($confirmation['minister'] ?? '-'); ?>
                    </td>
                </tr>
            </table>

            <div class="certification-text">
                This is a true copy of the entry kept in the Confirmation Register at <br>
                <strong contenteditable="true"><?php echo htmlspecialchars($c_name . ', ' . $c_place); ?></strong>.
            </div>

            <div class="footer">
                <div class="footer-item" style="text-align: left; width: 45%;">
                    <div style="margin-bottom: 35px;" contenteditable="true">Place:
                        <?php echo htmlspecialchars($c_place); ?>
                    </div>
                    <div contenteditable="true">Date: <?php echo date('d-m-Y'); ?></div>
                </div>

                <div class="footer-item" style="width: 45%; text-align: center;">
                    <div style="height: 60px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                        onclick="document.getElementById('sig_upload').click()">
                        <img id="sig_preview" class="sig-img"
                            style="max-height: 60px; <?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? 'display:block;' : ''; ?>"
                            src="<?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                    </div>
                    <div class="sig-line" onclick="document.getElementById('sig_upload').click()">Parish Priest</div>

                    <div class="seal-area"
                        style="margin-top: 15px; cursor: pointer; <?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'border:none;' : ''; ?>"
                        onclick="document.getElementById('seal_upload').click()">
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