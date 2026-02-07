<?php
require_once 'db.php';
include 'includes/header.php';

$type = $_GET['type'] ?? 'recommendation'; // 'recommendation' or 'godparent'
$parishioner_id = $_GET['parishioner_id'] ?? null;

$p = null;
if ($parishioner_id) {
    $stmt = $db->prepare("SELECT p.*, f.name as family_name, f.address as family_address 
                          FROM parishioners p 
                          LEFT JOIN families f ON p.family_id = f.id 
                          WHERE p.id = ?");
    $stmt->execute([$parishioner_id]);
    $p = $stmt->fetch();
}

$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
$c_name = $profile['church_name'] ?? 'Parish Church';
$c_place = $profile['place'] ?? 'City';
$c_diocese = $profile['diocese'] ?? 'Diocese';

// Default sender address
$from_address = "The Parish Priest,\n" . $c_name . ",\n" . $c_place . " - " . ($profile['pincode'] ?? '');
?>

<div class="card" style="max-width: 900px; margin: 2rem auto; padding: 2rem;">
    <!-- Toolbar -->
    <div class="no-print"
        style="text-align: right; margin-bottom: 2rem; display: flex; gap: 1rem; justify-content: flex-end; align-items: center;">
        <div style="flex: 1; text-align: left; color: #64748b; font-size: 0.9rem;">
            💡 <strong>Tip:</strong> This layout leaves top space for your <u>Parish Letterhead</u>.
        </div>
        <select
            onchange="window.location.href='recommendation_letter.php?type='+this.value<?php echo $parishioner_id ? " + '&parishioner_id=$parishioner_id'" : ""; ?>"
            style="padding: 0.5rem 1rem; border-radius: 8px; border: 2px solid #e2e8f0;">
            <option value="recommendation" <?php echo $type === 'recommendation' ? 'selected' : ''; ?>>Recommendation
                Letter</option>
            <option value="godparent" <?php echo $type === 'godparent' ? 'selected' : ''; ?>>Godparent Eligibility
            </option>
        </select>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Letter</button>
    </div>

    <div id="letter-content" class="letter-paper">
        <!-- Letterhead Space (Hidden on screen, but provides top margin for printing) -->
        <div class="letterheads-space" style="height: 40mm;"></div>

        <!-- Date and Ref -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem;">
            <div contenteditable="true">Ref:
                <?php echo date('Y'); ?>/<?php echo ($type === 'recommendation' ? 'REC' : 'GP'); ?>/______
            </div>
            <div contenteditable="true">Date: <?php echo date('d-m-Y'); ?></div>
        </div>

        <!-- From Address -->
        <div style="margin-bottom: 2rem; text-align: left;">
            <strong style="text-decoration: underline;">FROM:</strong><br>
            <div contenteditable="true"
                style="white-space: pre-wrap; font-family: 'Times New Roman', serif; margin-top: 5px;">
                <?php echo htmlspecialchars($from_address); ?>
            </div>
        </div>

        <!-- To Address -->
        <div style="margin-bottom: 2rem; text-align: left;text-align: left;">
            <strong style="text-decoration: underline;">TO:</strong><br>
            <div contenteditable="true"
                style="white-space: pre-wrap; font-family: 'Times New Roman', serif; min-height: 3em; margin-top: 5px;">
                The Principal / To Whom It May Concern,<br>[Institution Name],<br>[Place].</div>
        </div>

        <!-- Subject Line -->
        <div
            style="margin-bottom: 1.5rem; text-transform: uppercase; font-weight: bold; text-decoration: underline; text-align: center;">
            Subject: <span contenteditable="true"><?php
            echo ($type === 'recommendation') ? "Recommendation Letter for Admission of " . ($p['name'] ?? '[NAME]') : "Letter of Eligibility / Suitability for Godparent";
            ?></span>
        </div>

        <!-- Greetings -->
        <div style="margin-bottom: 1.5rem;">
            <span contenteditable="true" style="font-size: 13pt;">Respected Sir / Madam,</span>
        </div>

        <?php if ($type === 'recommendation'): ?>
            <!-- Recommendation Letter Content -->
            <div class="letter-body"
                style="font-family: 'Times New Roman', serif; line-height: 1.8; font-size: 13pt; text-align: justify;">
                <p>This is to certify that
                    <strong contenteditable="true"><?php echo htmlspecialchars($p['name'] ?? '[NAME]'); ?></strong>,
                    <?php echo strtolower($p['gender'] ?? '') === 'female' ? 'daughter' : 'son'; ?> of
                    <strong
                        contenteditable="true"><?php echo htmlspecialchars($p['father_name'] ?? ($p['mother_name'] ?? '[PARENT NAME]')); ?></strong>,
                    is a bonafide member of this Parish, residing at
                    <span
                        contenteditable="true"><?php echo htmlspecialchars($p['family_address'] ?? '[ADDRESS]'); ?></span>.
                </p>

                <p>I have known the candidate personally.
                    <?php echo strtolower($p['gender'] ?? '') === 'female' ? 'She' : 'He'; ?> is a person of good moral
                    character and has been active in our parish activities. I strongly recommend
                    <?php echo strtolower($p['gender'] ?? '') === 'female' ? 'her' : 'him'; ?> for admission to <strong
                        contenteditable="true">[NAME OF INSTITUTION / COLLEGE]</strong> for the academic year <strong
                        contenteditable="true"><?php echo date('Y'); ?>-<?php echo date('Y') + 1; ?></strong>.
                </p>

                <p>I wish <?php echo strtolower($p['gender'] ?? '') === 'female' ? 'her' : 'him'; ?> all the best in all
                    future endeavors.</p>
            </div>
        <?php else: ?>
            <!-- Godparent Eligibility Content -->
            <div class="letter-body"
                style="font-family: 'Times New Roman', serif; line-height: 1.8; font-size: 13pt; text-align: justify;">
                <p>This is to certify that
                    <strong contenteditable="true"><?php echo htmlspecialchars($p['name'] ?? '[NAME]'); ?></strong>
                    is a practicing Catholic and a member of
                    <strong contenteditable="true"><?php echo htmlspecialchars($c_name); ?></strong>.
                </p>

                <p>According to our records and my personal knowledge, the above-mentioned person leads a life in harmony
                    with the Catholic faith and fulfills the requirements for the task of a Godparent (Sponsor) as per Canon
                    Law 874.</p>

                <p>Therefore, I testify that <strong
                        contenteditable="true"><?php echo htmlspecialchars($p['name'] ?? '[NAME]'); ?></strong> is suitable
                    to be a <strong contenteditable="true">Godparent / Sponsor</strong> for the sacrament of <strong
                        contenteditable="true">Baptism / Confirmation</strong> of <strong contenteditable="true">[NAME OF
                        CANDIDATE]</strong> at <strong contenteditable="true">[NAME OF CHURCH]</strong>.</p>
            </div>
        <?php endif; ?>

        <!-- Letter Footer -->
        <div style="margin-top: 5rem; display: flex; justify-content: space-between; align-items: flex-end;">
            <div style="text-align: center;">
                <div class="seal-area" onclick="document.getElementById('seal_upload').click()"
                    style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'border:none;' : ''; ?>">
                    <span id="seal_text"
                        style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'display:none;' : ''; ?>">Seal</span>
                    <img id="seal_preview" class="seal-img"
                        style="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? 'display:block;' : ''; ?>"
                        src="<?php echo (($profile['enable_seal'] ?? false) && !empty($profile['seal_image'])) ? htmlspecialchars($profile['seal_image']) : ''; ?>">
                </div>
                <div style="border-top: 1px solid #000; padding-top: 5px; width: 120px; font-weight: bold;">Parish Seal
                </div>
            </div>
            <div style="text-align: center;">
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                    onclick="document.getElementById('sig_upload').click()">
                    <img id="sig_preview" class="sig-img"
                        style="max-height: 60px; <?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? 'display:block;' : ''; ?>"
                        src="<?php echo (($profile['enable_signature'] ?? false) && !empty($profile['signature_image'])) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                </div>
                <div style="border-top: 1px solid #000; padding-top: 5px; width: 220px; font-weight: bold;">Parish
                    Priest</div>
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
                    img.parentElement.style.border = 'none';
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<style>
    .letter-paper {
        background: white;
        padding: 5mm 15mm;
        box-sizing: border-box;
        color: #000;
        font-family: 'Times New Roman', serif;
    }

    [contenteditable="true"] {
        padding: 0 4px;
        border-bottom: 1px dotted #e2e8f0;
        transition: all 0.2s;
        display: inline-block;
    }

    .no-print [contenteditable="true"]:hover {
        background: #f1f5f9;
        border-bottom-color: #cbd5e1;
    }

    [contenteditable="true"]:focus {
        background: #fff;
        outline: none;
        border-bottom-color: var(--primary);
    }

    .seal-area {
        width: 90px;
        height: 90px;
        border: 1px dashed #ccc;
        margin: 5px auto;
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
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            background: white;
        }

        .card {
            box-shadow: none;
            border: none;
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
        }

        .no-print {
            display: none !important;
        }

        .letter-paper {
            padding: 0 20mm;
        }

        [contenteditable="true"] {
            border-bottom: none !important;
            padding: 0 !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>