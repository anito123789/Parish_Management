<?php
require_once 'db.php';

// Save/Update Logic (Processed before any HTML output to avoid header warnings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {
    $v_id = $_GET['id'] ?? null;
    $v_type = 'receipt';
    $v_no = $_POST['v_no'];
    $v_date = $_POST['v_date'];
    $person = $_POST['person_name'];
    $towards = $_POST['towards'];
    $amount = $_POST['amount'];
    $words = $_POST['amount_words'];
    $mode = $_POST['payment_mode'];
    $cat = $_POST['category'] ?: 'General';

    if ($v_id) {
        $stmt = $db->prepare("UPDATE vouchers SET voucher_no=?, voucher_date=?, person_name=?, towards=?, amount=?, amount_words=?, payment_mode=?, category=? WHERE id=?");
        $stmt->execute([$v_no, $v_date, $person, $towards, $amount, $words, $mode, $cat, $v_id]);
        $msg = "Voucher Updated Successfully";
    } else {
        $stmt = $db->prepare("INSERT INTO vouchers (voucher_type, voucher_no, voucher_date, person_name, towards, amount, amount_words, payment_mode, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$v_type, $v_no, $v_date, $person, $towards, $amount, $words, $mode, $cat]);
        $v_id = $db->lastInsertId();
        $msg = "Voucher Saved Successfully";
    }
    header("Location: vouchers_list.php?msg=" . urlencode($msg));
    exit();
}

include 'includes/header.php';

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];
$c_name = $profile['church_name'] ?? 'Your Parish Name';
$c_place = $profile['place'] ?? 'Your Place';
$c_diocese = $profile['diocese'] ?? 'Your Diocese';

// Load Voucher if ID is provided
$v_id = $_GET['id'] ?? null;
$v_data = null;
if ($v_id) {
    $stmt = $db->prepare("SELECT * FROM vouchers WHERE id = ?");
    $stmt->execute([$v_id]);
    $v_data = $stmt->fetch();
}

// Generate Auto Number if not loading
if (!$v_data) {
    try {
        $max_id = $db->query("SELECT MAX(id) FROM vouchers")->fetchColumn() ?: 0;
        $v_no_default = 'RV-' . date('Ymd') . '-' . sprintf('%03d', $max_id + 1);
    } catch (Exception $e) {
        $v_no_default = 'RV-' . date('Ymd') . '-001';
    }
} else {
    $v_no_default = $v_data['voucher_no'];
}
?>

<div class="no-print"
    style="text-align: center; margin-bottom: 20px; display: flex; justify-content: center; gap: 10px;">
    <form method="POST" id="voucherForm" onsubmit="return syncVoucherData()">
        <input type="hidden" name="save_voucher" value="1">
        <input type="hidden" name="v_no" id="hidden_no">
        <input type="hidden" name="v_date" id="hidden_date">
        <input type="hidden" name="person_name" id="hidden_person">
        <input type="hidden" name="towards" id="hidden_towards">
        <input type="hidden" name="payment_mode" id="hidden_mode">
        <input type="hidden" name="amount_words" id="hidden_words">
        <input type="hidden" name="amount" id="hidden_amount">
        <input type="hidden" name="category" id="hidden_category">

        <button type="submit" class="btn btn-success" style="padding: 10px 30px; font-size: 1.1rem; cursor: pointer;">
            💾 <?php echo $v_id ? 'Update' : 'Save'; ?> Voucher
        </button>
    </form>

    <button onclick="window.print()" class="btn btn-primary"
        style="padding: 10px 30px; font-size: 1.1rem; cursor: pointer;">
        🖨️ Print Receipt
    </button>

    <a href="vouchers_list.php" class="btn btn-secondary"
        style="padding: 10px 25px; text-decoration: none; display: flex; align-items: center;">
        📋 History
    </a>
</div>

<div class="voucher-print-wrapper">
    <div class="voucher-box">
        <!-- Minimalist Header -->
        <div class="voucher-header">
            <h1 contenteditable="true"><?php echo htmlspecialchars($c_name); ?></h1>
            <p contenteditable="true"><?php echo htmlspecialchars($c_place . ', ' . $c_diocese); ?></p>
            <div class="voucher-title">RECEIPT VOUCHER</div>
        </div>

        <div class="voucher-body">
            <!-- Top Row: No & Date -->
            <div class="voucher-row">
                <div class="v-cell one-half">
                    <span class="label-text">No:</span>
                    <span id="ui_no" class="border-b"
                        contenteditable="true"><?php echo htmlspecialchars($v_no_default); ?></span>
                </div>
                <div class="v-cell one-half">
                    <span class="label-text" style="margin-left: 10px;">Date:</span>
                    <span id="ui_date" class="border-b"
                        contenteditable="true"><?php echo $v_data['voucher_date'] ?? date('d-m-Y'); ?></span>
                </div>
            </div>

            <!-- Receiver Row -->
            <!-- Receiver Row -->
            <div class="voucher-row mt-10">
                <div class="v-cell w-full">
                    <span class="label-text">Received with thanks from:</span>
                    <span id="ui_person" class="border-b"
                        contenteditable="true"><?php echo htmlspecialchars($v_data['person_name'] ?? ''); ?></span>
                </div>
            </div>

            <!-- Towards Row -->
            <!-- Towards Row -->
            <div class="voucher-row mt-10">
                <div class="v-cell w-full">
                    <span class="label-text">Towards:</span>
                    <span id="ui_towards" class="border-b"
                        contenteditable="true"><?php echo htmlspecialchars($v_data['towards'] ?? ''); ?></span>
                </div>
            </div>

            <!-- Words Row -->
            <!-- Words Row -->
            <div class="voucher-row mt-10">
                <div class="v-cell w-full">
                    <span class="label-text">The Sum of Rupees:</span>
                    <span id="amount_words" class="border-b italic"
                        contenteditable="true"><?php echo htmlspecialchars($v_data['amount_words'] ?? 'Zero Only'); ?></span>
                </div>
            </div>

            <!-- Mid Row: Mode & Category -->
            <div class="voucher-row mt-10">
                <div class="v-cell one-half">
                    <span class="label-text">Mode:</span>
                    <span id="ui_mode" class="border-b"
                        contenteditable="true"><?php echo htmlspecialchars($v_data['payment_mode'] ?? ''); ?></span>
                </div>
                <div class="v-cell one-half">
                    <span class="label-text" style="margin-left: 10px;">Category:</span>
                    <span id="ui_category" class="border-b"
                        contenteditable="true"><?php echo htmlspecialchars($v_data['category'] ?? 'Receipt'); ?></span>
                </div>
            </div>
        </div>

        <!-- Footer Area -->
        <div class="voucher-footer">
            <div class="footer-grid">
                <!-- Amount Box -->
                <div class="amount-wrap">
                    <div class="amount-border-box">
                        Rs. <span id="amount_val" contenteditable="true"
                            onfocus="if(this.innerText==='0.00')this.innerText=''"><?php echo htmlspecialchars($v_data['amount'] ?? '0.00'); ?></span>
                        /-
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="sig-wrap">
                    <div class="sig-item">
                        <div class="seal-container" onclick="document.getElementById('seal_upload').click()">
                            <?php if (($profile['enable_seal'] ?? 0) && !empty($profile['seal_image'] ?? '')): ?>
                                <img id="seal_preview" src="<?php echo htmlspecialchars($profile['seal_image']); ?>"
                                    class="seal-image">
                            <?php else: ?>
                                <div id="seal_text">Seal</div>
                                <img id="seal_preview" class="seal-image" style="display:none;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="sig-item" style="text-align: right;">
                        <div class="sig-container" onclick="document.getElementById('sig_upload').click()">
                            <img id="sig_preview" class="sig-image"
                                style="<?php echo (($profile['enable_signature'] ?? 0) && !empty($profile['signature_image'] ?? '')) ? 'display:block;' : 'display:none;'; ?>"
                                src="<?php echo (($profile['enable_signature'] ?? 0) && !empty($profile['signature_image'] ?? '')) ? htmlspecialchars($profile['signature_image']) : ''; ?>">
                        </div>
                        <div class="sig-line">Authorised Signature</div>
                    </div>
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
                if (textId) document.getElementById(textId).style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function numberToWordsIndian(num) {
        if (num == 0) return 'Zero';
        var a = ['', 'one ', 'two ', 'three ', 'four ', 'five ', 'six ', 'seven ', 'eight ', 'nine ', 'ten ', 'eleven ', 'twelve ', 'thirteen ', 'fourteen ', 'fifteen ', 'sixteen ', 'seventeen ', 'eighteen ', 'nineteen '];
        var b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        num = Math.floor(num);
        let n_str = num.toString();
        if (n_str.length > 9) return 'Limit Exceeded';
        n_str = ("000000000" + n_str).slice(-9);
        var n = n_str.match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
        if (!n) return '';
        var str = '';
        if (n[1] != 0) str += (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + 'crore ';
        if (n[2] != 0) str += (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + 'lakh ';
        if (n[3] != 0) str += (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + 'thousand ';
        if (n[4] != 0) str += a[Number(n[4])] + 'hundred ';
        if (n[5] != 0) {
            if (str != '') str += 'and ';
            str += (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]);
        }
        return str.trim() + ' Only';
    }

    function updateWords() {
        let amtInput = document.getElementById('amount_val').textContent.trim();
        let cleanAmt = amtInput.replace(/[^0-9.]/g, '');
        let num = parseFloat(cleanAmt);
        if (!isNaN(num)) {
            let words = numberToWordsIndian(num);
            document.getElementById('amount_words').innerText = words.charAt(0).toUpperCase() + words.slice(1);
        } else {
            document.getElementById('amount_words').innerText = "Zero Only";
        }
    }

    function syncVoucherData() {
        document.getElementById('hidden_no').value = document.getElementById('ui_no').innerText;
        document.getElementById('hidden_date').value = document.getElementById('ui_date').innerText;
        document.getElementById('hidden_person').value = document.getElementById('ui_person').innerText;
        document.getElementById('hidden_towards').value = document.getElementById('ui_towards').innerText;
        document.getElementById('hidden_mode').value = document.getElementById('ui_mode').innerText;
        document.getElementById('hidden_words').value = document.getElementById('amount_words').innerText;
        document.getElementById('hidden_amount').value = document.getElementById('amount_val').innerText.replace(/[^\d.]/g, '');
        document.getElementById('hidden_category').value = document.getElementById('ui_category').innerText;
        return true;
    }

    const amtElem = document.getElementById('amount_val');
    amtElem.addEventListener('input', updateWords);
    amtElem.addEventListener('keyup', updateWords);
    amtElem.addEventListener('blur', updateWords);
</script>

<style>
    /* Minimalist Design System */
    .voucher-print-wrapper {
        max-width: 210mm;
        margin: 20px auto;
        padding: 0;
        box-sizing: border-box;
    }

    .voucher-box {
        background: #fff;
        border: 1.5pt solid #000;
        padding: 10mm;
        height: 135mm;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
        position: relative;
        overflow: hidden;
    }

    .voucher-header {
        text-align: center;
        margin-bottom: 35px;
        /* Increased space after title */
    }

    .voucher-header h1 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .voucher-header p {
        margin: 2px 0;
        font-size: 0.85rem;
    }

    .voucher-title {
        display: inline-block;
        margin-top: 5px;
        font-weight: 800;
        border-bottom: 1pt solid #000;
        padding: 2px 10px;
        font-size: 0.9rem;
    }

    .voucher-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 10px;
    }

    .voucher-row {
        display: flex;
        align-items: flex-end;
        /* Align to bottom for underlining */
        font-size: 1.1rem;
        width: 100%;
    }

    .mt-10 {
        margin-top: 15px;
    }

    .v-cell {
        display: flex;
        align-items: flex-end;
        /* Ensure label and input line up at baseline */
    }

    .w-full {
        flex: 1;
    }

    .one-half {
        width: 50%;
        display: flex;
    }

    .label-text {
        white-space: nowrap;
        font-weight: 600;
        margin-right: 5px;
        padding-bottom: 2px;
        /* Visual tweak */
    }

    .border-b {
        flex: 1;
        /* This is the magic: grow to fill space */
        border-bottom: 1px dotted #000;
        display: block;
        /* Flex item behavior */
        min-width: 50px;
        outline: none;
        padding-left: 5px;
        margin-bottom: 1px;
        /* Align with text baseline */
    }

    .italic {
        font-style: italic;
    }

    .voucher-footer {
        margin-top: auto;
        padding-top: 10px;
    }

    .footer-grid {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .amount-border-box {
        border: 1.5pt solid #000;
        padding: 5px 15px;
        font-size: 1.3rem;
        font-weight: 800;
        min-width: 150px;
        text-align: center;
    }

    .sig-wrap {
        display: flex;
        gap: 40px;
        align-items: flex-end;
    }

    .seal-container {
        width: 70px;
        height: 70px;
        border: 1px dashed #ccc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: #999;
    }

    .seal-image {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .sig-container {
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 150px;
    }

    .sig-image {
        max-width: 150px;
        max-height: 50px;
        object-fit: contain;
    }

    .sig-line {
        border-top: 1pt solid #000;
        padding-top: 5px;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    @media print {
        @page {
            size: A5 landscape;
            margin: 0;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body * {
            display: none !important;
        }

        html,
        body,
        .app-container,
        .main-content,
        .content-wrapper,
        .voucher-print-wrapper,
        .voucher-print-wrapper * {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            visibility: visible !important;
        }

        html,
        body {
            height: 148mm;
            width: 210mm;
            overflow: hidden !important;
            background: #fff !important;
        }

        .voucher-print-wrapper {
            display: flex !important;
            width: 210mm;
            height: 148mm;
            align-items: center;
            justify-content: center;
        }

        .voucher-box {
            display: flex !important;
            flex-direction: column !important;
            width: 204mm;
            height: 140mm;
            border: 1.5pt solid #000 !important;
            /* MATCHED SCREEN BORDER */
            padding: 10mm !important;
            /* MATCHED SCREEN PADDING */
            box-sizing: border-box !important;
            background: #fff !important;
            position: relative !important;
        }

        /* Force Flex Layouts in Print */
        .voucher-header,
        .voucher-body,
        .voucher-footer,
        .voucher-row,
        .v-cell,
        .amount-border-box,
        .sig-line,
        .label-text,
        .border-b,
        .sig-wrap,
        .sig-item,
        .footer-grid,
        .seal-container,
        .sig-container {
            display: flex !important;
            visibility: visible !important;
        }

        .voucher-header {
            display: block !important;
            margin-bottom: 35px !important; /* Force print spacing */
        }

        /* Exception for header block */

        .voucher-row {
            display: flex !important;
            align-items: flex-end !important;
            width: 100% !important;
            margin-top: 15px !important;
            /* MATCHED SCREEN MARGIN */
        }

        /* Specific fix for top row margin */
        .voucher-row:first-child {
            margin-top: 0 !important;
        }

        .one-half {
            width: 50% !important;
            display: flex !important;
        }

        .w-full {
            width: 100% !important;
            flex: 1 !important;
            display: flex !important;
        }

        .border-b {
            border-bottom: 1px dotted #000 !important;
            /* MATCHED SCREEN BORDER STYLE */
            flex: 1 !important;
            display: block !important;
            min-width: 50px !important;
        }

        .footer-grid {
            justify-content: space-between !important;
        }

        .sig-wrap {
            gap: 40px !important;
        }

        /* Ensure signature lines render */
        .sig-line {
            border-top: 1pt solid #000 !important;
            display: block !important;
            text-align: center !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>