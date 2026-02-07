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

    $stmt = $db->prepare("SELECT p.name, p.death_date, p.relationship, d.* 
                          FROM parishioners p 
                          LEFT JOIN deaths d ON p.id = d.parishioner_id 
                          WHERE p.family_id = ? AND p.is_deceased = 1 ORDER BY p.death_date DESC");
    $stmt->execute([$family_id]);
    $deceased_members = $stmt->fetchAll();

    $profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Family Death Report - <?php echo htmlspecialchars($family['name']); ?></title>
        <style>
            body {
                font-family: sans-serif;
                padding: 40px;
                background: #fff;
                color: #333;
            }

            .header {
                text-align: center;
                border-bottom: 3px solid #334155;
                padding-bottom: 20px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            th,
            td {
                border: 1px solid #e2e8f0;
                padding: 12px;
                text-align: left;
            }

            th {
                background: #f1f5f9;
                color: #334155;
            }

            @media print {
                .no-print {
                    display: none;
                }
            }
        </style>
    </head>

    <body>
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()"
                style="padding: 10px 20px; background: #334155; color: white; border: none; border-radius: 6px;">Print
                Report</button>
        </div>
        <div class="header">
            <h1><?php echo htmlspecialchars($profile['church_name'] ?? 'Parish'); ?></h1>
            <h2 style="color: #334155;">Family Death Records</h2>
            <p>Family: <strong><?php echo htmlspecialchars($family['name']); ?></strong></p>
        </div>
        <?php if (empty($deceased_members)): ?>
            <p style="text-align: center; margin-top: 50px; color: #64748b;">No death records found for this family.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Member Name</th>
                        <th>Relation</th>
                        <th>Date of Death</th>
                        <th>Cause</th>
                        <th>Priest</th>
                        <th>Cemetery / Burial Place</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deceased_members as $m): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($m['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['relationship']); ?></td>
                            <td><?php echo format_date($m['death_date']); ?></td>
                            <td><?php echo htmlspecialchars($m['cause'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($m['minister'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($m['cemetery'] ?: ($m['place_of_burial'] ?: '-')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </body>

    </html>
    <?php
    exit;
}

// Fetch Subject
$stmt = $db->prepare("SELECT p.*, f.name as family_name, f.address as family_address FROM parishioners p LEFT JOIN families f ON p.family_id = f.id WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p)
    exit("Person not found");

// Fetch Death Details
$details = $db->query("SELECT * FROM deaths WHERE parishioner_id = $id")->fetch() ?: [];

// Fetch Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch();
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
    <title>Death Certificate - <?php echo htmlspecialchars($p['name']); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
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
            padding: 0.5rem;
            max-width: 800px;
            margin: 0.5rem auto;
            border-radius: 8px;
            text-align: center;
            font-family: sans-serif;
            font-size: 0.9rem;
        }

        .container {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 10mm;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .border-wrapper {
            border: 5px double #000;
            padding: 10mm;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 22pt;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header h2 {
            font-size: 14pt;
            margin: 5px 0;
            font-weight: bold;
        }

        .title {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            font-size: 16pt;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .content {
            margin-top: 30px;
            font-size: 13pt;
            line-height: 2.0;
        }

        .fill {
            border-bottom: 1px dotted #000;
            font-weight: bold;
            padding: 0 5px;
            display: inline-block;
            min-width: 150px;
            text-align: center;
            outline: none;
        }

        .certification-text {
            margin-top: 30px;
            text-align: center;
            font-style: italic;
            font-size: 11pt;
            border-top: 1px solid #eee;
            padding-top: 15px;
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
            width: 45%;
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
            margin: 15px auto 0 auto;
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
                margin: 0;
                width: 210mm;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align:center; padding: 10px;">
        <button onclick="window.print()"
            style="padding: 10px 30px; font-size: 1rem; cursor: pointer; background: #334155; color: white; border: none; border-radius: 8px;">🖨️
            Print Death Certificate</button>
    </div>

    <div class="edit-notice">
        <strong>💡 Live Preview:</strong> You can edit any dotted field. Click on <strong>Seal</strong> or
        <strong>Parish Priest</strong> to upload an image.
    </div>

    <div class="container">
        <div class="border-wrapper">
            <div class="header">
                <h1 contenteditable="true"><?php echo htmlspecialchars($c_name); ?></h1>
                <h2 contenteditable="true"><?php echo htmlspecialchars($c_place); ?></h2>
                <p contenteditable="true">The Roman Catholic Diocese of <?php echo htmlspecialchars($c_diocese); ?></p>
            </div>

            <div class="title">Certificate of Death</div>

            <div class="content">
                This is to certify that <br>
                <span class="fill" contenteditable="true"><?php echo htmlspecialchars($p['name']); ?></span> <br>
                <?php
                $father = !empty($p['father_name']) ? $p['father_name'] : $p['family_name'];
                $mother = !empty($p['mother_name']) ? $p['mother_name'] : '________________';
                ?>
                Son / Daughter of <span class="fill"
                    contenteditable="true"><?php echo htmlspecialchars($father); ?></span> &
                <span class="fill" contenteditable="true"><?php echo htmlspecialchars($mother); ?></span> <br>

                Residing at <span class="fill"
                    contenteditable="true"><?php echo htmlspecialchars($p['family_address']); ?></span> <br>

                Departed this life on <span class="fill"
                    contenteditable="true"><?php echo smart_date($p['death_date'], $use_words); ?></span> <br>

                Cause of Death: <span class="fill"
                    contenteditable="true"><?php echo htmlspecialchars($details['cause'] ?? 'Natural Causes'); ?></span>
                <br>

                Place of Burial / Cemetery: <span class="fill"
                    contenteditable="true"><?php echo htmlspecialchars($details['cemetery'] ?? ($details['place_of_burial'] ?? 'Parish Cemetery')); ?></span>
                <br>

                Officiating Priest Rev.Fr.: <span class="fill"
                    contenteditable="true"><?php echo htmlspecialchars($details['minister'] ?? 'Parish Priest'); ?></span>
            </div>

            <div class="certification-text">
                This is a true extract from the Register of Deaths kept at this Parish.
            </div>

            <div class="footer">
                <div class="footer-item" style="text-align: left;">
                    <div style="margin-bottom: 30px;" contenteditable="true">Place:
                        <?php echo htmlspecialchars($c_place); ?>
                    </div>
                    <div contenteditable="true">Date: <?php echo date('d-m-Y'); ?></div>
                </div>

                <div class="footer-item">
                    <div style="height: 60px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                        onclick="document.getElementById('sig_upload').click()">
                        <img id="sig_preview" class="sig-img"
                            style="max-height: 60px; <?php echo ($profile['enable_signature'] && !empty($profile['signature_image'])) ? 'display:block;' : ''; ?>"
                            src="<?php echo ($profile['enable_signature'] && !empty($profile['signature_image'])) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                    </div>
                    <div class="sig-line" onclick="document.getElementById('sig_upload').click()">Parish Priest</div>

                    <div class="seal-area" onclick="document.getElementById('seal_upload').click()"
                        style="<?php echo ($profile['enable_seal'] && !empty($profile['seal_image'])) ? 'border:none;' : ''; ?>">
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