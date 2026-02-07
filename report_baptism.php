<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$family_id = $_GET['family_id'] ?? null;

if (!$id && !$family_id)
    exit("ID or Family ID Missing");

// --- START FAMILY REPORT VIEW ---
if ($family_id && !$id) {
    $stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
    $stmt->execute([$family_id]);
    $family = $stmt->fetch();
    if (!$family)
        exit("Family not found");

    $stmt = $db->prepare("SELECT p.name, p.dob, p.relationship, p.gender, p.father_name, p.mother_name, p.baptism_date as p_baptism_date, b.* 
                          FROM parishioners p 
                          LEFT JOIN baptisms b ON p.id = b.parishioner_id 
                          WHERE p.family_id = ? AND (p.baptism_date IS NOT NULL AND p.baptism_date != '' AND p.baptism_date != '0000-00-00') 
                          ORDER BY p.dob ASC");
    $stmt->execute([$family_id]);
    $members = $stmt->fetchAll();

    $profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Family Baptism Report - <?php echo htmlspecialchars($family['name']); ?></title>
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
                margin-bottom: 20px;
                border-bottom: 2px solid #1e293b;
                padding-bottom: 10px;
            }

            .header h1 {
                margin: 0;
                color: #1e293b;
                text-transform: uppercase;
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
                border: 1px solid #cbd5e1;
                padding: 6px 4px;
                text-align: left;
                overflow: hidden;
                word-wrap: break-word;
                vertical-align: top;
            }

            th {
                background: #f8fafc;
                font-weight: 700;
                color: #475569;
                text-transform: uppercase;
                font-size: 8pt;
            }

            tr:nth-child(even) {
                background: #f9fafb;
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
                style="padding: 8px 16px; background: #1e293b; color: white; border: none; border-radius: 4px; cursor: pointer;">Print
                Report</button>
        </div>
        <div class="header">
            <h1><?php echo htmlspecialchars($profile['church_name'] ?? 'Parish'); ?></h1>
            <p><?php echo htmlspecialchars(($profile['place'] ?? '') . ' | ' . ($profile['diocese'] ?? '')); ?></p>
            <h2 style="margin-top: 5px; font-size: 14pt; color: #64748b;">Family Baptismal Records</h2>
            <p>Family: <strong><?php echo htmlspecialchars($family['name']); ?></strong>
                (<?php echo htmlspecialchars($family['family_code']); ?>)</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 18%;">Member Name</th>
                    <th style="width: 10%;">Relation</th>
                    <th style="width: 18%;">Parents</th>
                    <th style="width: 10%;">DOB</th>
                    <th style="width: 10%;">Baptism Date</th>
                    <th style="width: 12%;">Place</th>
                    <th style="width: 10%;">Minister</th>
                    <th style="width: 12%;">Godparents</th>
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
                        <td><?php echo format_date($m['dob']); ?></td>
                        <td><?php echo format_date($m['date_of_baptism'] ?: $m['p_baptism_date']); ?></td>
                        <td><?php echo htmlspecialchars($m['place'] ?: '-'); ?></td>
                        <td><small><?php echo $m['minister'] ? 'Rev.Fr. ' . htmlspecialchars($m['minister']) : '-'; ?></small>
                        </td>
                        <td>
                            <small>
                                <?php if ($m['godfather'])
                                    echo "GF: " . htmlspecialchars($m['godfather']) . "<br>"; ?>
                                <?php if ($m['godmother'])
                                    echo "GM: " . htmlspecialchars($m['godmother']); ?>
                                <?php if (!$m['godfather'] && !$m['godmother'])
                                    echo "-"; ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top: 40px; font-size: 0.8rem; color: #94a3b8; text-align: right;">
            Generated on: <?php echo date('d-m-Y H:i A'); ?>
        </div>
    </body>

    </html>
    <?php
    exit;
}
// --- END FAMILY REPORT VIEW ---

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

// If names are empty and person is a child, pull from family head/spouse
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

// Fetch Detailed Sacramental Data
$baptism = $db->query("SELECT * FROM baptisms WHERE parishioner_id = $id")->fetch() ?: [];
$marriage = $db->query("SELECT * FROM marriages WHERE parishioner_id = $id")->fetch() ?: [];

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch();

$c_name = $profile['church_name'] ?? 'Your Parish Name';
$c_place = $profile['place'] ?? 'Your Place';
$c_diocese = $profile['diocese'] ?? 'Your Diocese';
$c_vicar = $profile['vicar'] ?? 'Rev. Fr. Name';

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
    <title>Baptism Certificate - <?php echo htmlspecialchars($p['name']); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
            /* Removing margin hides browser headers/footers */
        }

        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #000;
            line-height: 1.6;
        }

        /* Dashboard for Editing */
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
            min-height: 270mm;
            margin: 20px auto;
            background: #fff;
            padding: 10mm;
            position: relative;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Border fits text area exactly */
        .border-wrapper {
            border: 4px double #000;
            padding: 12mm;
            height: auto;
            min-height: 245mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* Ensure nothing goes out */
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24pt;
            margin: 0;
            text-transform: uppercase;
        }

        .header h2 {
            font-size: 16pt;
            margin: 5px 0;
        }

        .header p {
            font-size: 11pt;
            margin: 0;
            font-style: italic;
        }

        .title {
            text-align: center;
            border-bottom: 2px solid #000;
            margin: 15px 0;
            padding-bottom: 6px;
        }

        .title h3 {
            font-size: 18pt;
            margin: 0;
            text-transform: uppercase;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12pt;
            margin-top: 10px;
        }

        .content-table td {
            padding: 6px 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 35%;
        }

        .colon {
            width: 5%;
            text-align: center;
        }

        .value {
            width: 60%;
            border-bottom: 1px dotted #888;
            font-weight: 500;
            outline: none;
            /* For contenteditable */
        }

        .value:hover {
            background: #f8fafc;
        }

        .value:focus {
            background: #fff7ed;
            border-bottom: 1px solid #f59e0b;
        }

        .certification-text {
            margin-top: 25px;
            text-align: center;
            font-size: 12pt;
            font-style: italic;
        }

        .footer {
            margin-top: auto;
            padding-top: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* Changed to center for parallel labels */
        }

        .footer-item {
            text-align: center;
            width: 32%;
        }

        .seal-area {
            width: 90px;
            height: 90px;
            border: 1px dashed #ccc;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 9pt;
            border-radius: 50%;
            overflow: hidden;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 10px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 11pt;
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
                /* Center on page */
                width: 210mm;
                /* Exact A4 width */
                height: 297mm;
                /* Exact A4 height */
                padding: 10mm;
                /* Internal padding for safe margins */
                border: none;
            }

            .border-wrapper {
                border: 4px double #000;
            }

            .value {
                border-bottom: 1px dotted #000;
            }

            .seal-area {
                border: none !important;
            }

            #seal_text {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="no-print" style="text-align:center; padding: 20px; background: #1e293b; color: white;">
        <h2 style="margin: 0 0 10px 0;">Baptism Certificate - Preview & Edit</h2>
        <p style="margin: 0 0 15px 0; font-size: 0.9rem; opacity: 0.8;">Click on any dotted line to edit the text before
            printing.</p>

        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 15px;">
            <button onclick="window.print()"
                style="padding: 10px 25px; font-size: 14px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: bold;">🖨️
                Print Certificate</button>
            <button onclick="document.getElementById('seal_upload').click()"
                style="padding: 10px 20px; font-size: 14px; cursor: pointer; background: #6366f1; color: white; border: none; border-radius: 6px;">🏷️
                Insert Seal</button>
            <button onclick="document.getElementById('sig_upload').click()"
                style="padding: 10px 20px; font-size: 14px; cursor: pointer; background: #f59e0b; color: white; border: none; border-radius: 6px;">✍️
                Insert Signature</button>
            <a href="parishioner_view.php?id=<?php echo $id; ?>"
                style="padding: 10px 20px; font-size: 14px; background: #475569; color: white; text-decoration: none; border-radius: 6px;">&larr;
                Back</a>
        </div>

        <input type="file" id="seal_upload" accept="image/*" style="display:none"
            onchange="previewImg(this, 'seal_preview', 'seal_text')">
        <input type="file" id="sig_upload" accept="image/*" style="display:none"
            onchange="previewImg(this, 'sig_preview', null)">
    </div>

    <div class="edit-notice">
        <strong>💡 Tip:</strong> This is a <strong>Live Preview</strong>. Click on any text to edit, or click on the
        <strong>Seal</strong> or <strong>Parish Priest</strong> areas to upload an image for printing.
    </div>

    <div class="container">
        <div class="border-wrapper">
            <div class="header">
                <h1 contenteditable="true"><?php echo htmlspecialchars($c_name); ?></h1>
                <h2 contenteditable="true"><?php echo htmlspecialchars($c_place); ?></h2>
                <p contenteditable="true"><?php echo htmlspecialchars($c_diocese); ?></p>
            </div>

            <div class="title">
                <h3>Extract From Registrar of Baptism</h3>
            </div>

            <table class="content-table">
                <tr>
                    <td class="label">Christian Name</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($p['name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Sex</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($p['gender'] ?: '-'); ?></td>
                </tr>
                <tr>
                    <td class="label">Date of Baptism</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo smart_date($p['baptism_date'], $use_words); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Place of Baptism</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">


                        <?php echo htmlspecialchars(($baptism['place'] ?? null) ?: ($c_name . ', ' . $c_place)); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo smart_date($p['dob'], $use_words); ?></td>
                </tr>
                <tr>
                    <td class="label">Father’s Name</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($father_name); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Mother’s Name</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true"><?php echo htmlspecialchars($mother_name); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Residence of Parents</td>
                    <td class="colon">:</td>


                    <td class="value" contenteditable="true">
                        <?php echo nl2br(htmlspecialchars($p['family_address'] ?: '-')); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Godfather Name</td>

                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">
                        <?php echo htmlspecialchars($baptism['godfather'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>

                    <td class="label">Godmother Name</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">
                        <?php echo htmlspecialchars($baptism['godmother'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>

                    <td class="label">Minister of Baptism</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">Rev. Fr.
                        <?php echo htmlspecialchars($baptism['minister'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Married To</td>
                    <td class="colon">:</td>
                    <td class="value" contenteditable="true">
                        <?php
                        // if ($p['marriage_date']) {
                        //     echo htmlspecialchars($marriage['spouse_name'] ?: '') . " on " . smart_date($p['marriage_date'], $use_words) . (!empty($marriage['place']) ? " at " . htmlspecialchars($marriage['place']) : "");
                        // } else {
                        //     echo "";
                        // }
                        ?>
                    </td>
                </tr>
            </table>

            <div class="certification-text">
                I certify that the above extract is a true copy from the Registrar of Baptism of this Church.
            </div>

            <div class="footer">
                <div class="footer-item" style="text-align: left; width: 45%;">
                    <div style="margin-bottom: 25px;">Place: <span
                            contenteditable="true"><?php echo htmlspecialchars($c_place); ?></span></div>
                    <div style="margin-bottom: 25px;">Date: <span
                            contenteditable="true"><?php echo date('d-m-Y'); ?></span></div>
                </div>

                <div class="footer-item" style="width: 45%; text-align: center;">
                    <div style="height: 60px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                        onclick="document.getElementById('sig_upload').click()">
                        <img id="sig_preview" class="sig-img"
                            style="max-height: 60px; <?php echo ($profile['enable_signature'] && !empty($profile['signature_image'])) ? 'display:block;' : ''; ?>"
                            src="<?php echo ($profile['enable_signature'] && !empty($profile['signature_image'])) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                    </div>
                    <div class="sig-line" onclick="document.getElementById('sig_upload').click()">Parish Priest</div>

                    <div class="seal-area"
                        style="margin-top: 15px; cursor: pointer; <?php echo ($profile['enable_seal'] && !empty($profile['seal_image'])) ? 'border:none;' : ''; ?>"
                        onclick="document.getElementById('seal_upload').click()">
                        <span id="seal_text"
                            style="<?php echo ($profile['enable_seal'] && !empty($profile['seal_image'])) ? 'display:none;' : ''; ?>">Seal</span>
                        <img id="seal_preview" class="seal-img"
                            style="<?php echo ($profile['enable_seal'] && !empty($profile['seal_image'])) ? 'display:block;' : ''; ?>"
                            src="<?php echo ($profile['enable_seal'] && !empty($profile['seal_image'])) ? htmlspecialchars($profile['seal_image']) : ''; ?>">
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