<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$family = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
    $stmt->execute([$id]);
    $family = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_new = !$id;
    $head = $_POST['head_name'];
    $spouse = $_POST['spouse_name'];
    $address = $_POST['address'];
    $anbiyam = $_POST['anbiyam'];
    $substation = $_POST['substation'] ?? '';
    $phone = $_POST['phone'];
    $sub_type = $_POST['subscription_type'] ?? 'yearly';
    $sub_amount = $_POST['subscription_amount'] ?? 1200;
    $sub_start = $_POST['subscription_start_date'] ?: null;

    // Display Name usually Head & Spouse or just Head
    $display_name = $head . ($spouse ? " & $spouse" : "");

    $manual_code = $_POST['family_code'] ?? '';

    // Logic to generate code if empty
    if (empty($manual_code)) {
        // Only generate if new or strictly empty
        $next = $db->query("SELECT seq FROM sqlite_sequence WHERE name='families'")->fetchColumn();
        $next_id = ($next ?: 0) + 1;
        $serial = sprintf('%03d', $next_id);

        $h_part = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $head), 0, 3));
        $s_part = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $spouse), 0, 3));
        $code = $h_part . $s_part . $serial;
    } else {
        $code = $manual_code;
    }

    if ($id) {
        $stmt = $db->prepare("UPDATE families SET name = ?, head_name = ?, spouse_name = ?, address = ?, anbiyam = ?, substation = ?, phone = ?,
subscription_type = ?, subscription_amount = ?, subscription_start_date = ?, family_code = ? WHERE id = ?");
        $stmt->execute([
            $display_name,
            $head,
            $spouse,
            $address,
            $anbiyam,
            $substation,
            $phone,
            $sub_type,
            $sub_amount,
            $sub_start,
            $code,
            $id
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO families (family_code, name, head_name, spouse_name, address, anbiyam, substation, phone,
subscription_type, subscription_amount, subscription_start_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $display_name, $head, $spouse, $address, $anbiyam, $substation, $phone, $sub_type, $sub_amount, $sub_start]);
        $id = $db->lastInsertId();
    }

    // Automatically generate and save QR code for the new/updated family
    $_GET['id'] = $id;
    $_GET['preview'] = 1;
    $silent_qr = true; // Use variable to prevent headers/exit in generate_qr.php
    include 'generate_qr.php';

    // If it's a new family, redirect to add the first parishioner (Head)
    if ($is_new) {
        header("Location: parishioner_form.php?family_id=$id");
    } else {
        header("Location: family_view.php?id=$id");
    }
    exit;
}

include 'includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: auto;">
    <h2><?php echo $id ? 'Edit' : 'Add New'; ?> Family</h2>
    <form method="POST">
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Head of Family Name</label>
                <input type="text" name="head_name" required
                    value="<?php echo htmlspecialchars($family['head_name'] ?? $family['name'] ?? ''); ?>"
                    placeholder="e.g. John">
            </div>
            <div class="form-group">
                <label>Spouse Name</label>
                <input type="text" name="spouse_name"
                    value="<?php echo htmlspecialchars($family['spouse_name'] ?? ''); ?>"
                    placeholder="e.g. Mary (Optional)">
            </div>
        </div>

        <div class="form-group">
            <label>Family ID Code (Unique)</label>
            <input type="text" name="family_code" value="<?php echo htmlspecialchars($family['family_code'] ?? ''); ?>"
                placeholder="Leave empty to auto-generate">
            <small style="color: var(--secondary);">Enter manual ID if required (e.g. ALLSEL004)</small>
            <?php if ($id && !empty($family['family_code'])): ?>
                <div
                    style="margin-top: 0.5rem; display: flex; align-items: center; gap: 1rem; background: #f8fafc; padding: 0.5rem; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    <img src="generate_qr.php?id=<?php echo $id; ?>&preview=1"
                        style="width: 40px; height: 40px; border-radius: 4px;">
                    <a href="generate_qr.php?id=<?php echo $id; ?>" download="QR_<?php echo $family['family_code']; ?>.png"
                        style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">Download QR Code</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Subscription Settings -->
        <div class="grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div class="form-group">
                <label>Subscription Type</label>
                <select name="subscription_type" id="subscription_type" onchange="updateSubscriptionAmount()">
                    <option value="yearly" <?php echo ($family['subscription_type'] ?? 'yearly') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                    <option value="monthly" <?php echo ($family['subscription_type'] ?? 'yearly') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subscription Amount (₹)</label>
                <input type="number" name="subscription_amount" id="subscription_amount" step="0.01"
                    value="<?php echo htmlspecialchars($family['subscription_amount'] ?? 1200); ?>"
                    placeholder="e.g. 1200">
            </div>
            <div class="form-group">
                <label>Subscription Start Date</label>
                <input type="date" name="subscription_start_date"
                    value="<?php echo htmlspecialchars($family['subscription_start_date'] ?? date('Y-m-d')); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Anbiyam Number/Name</label>
            <input type="text" name="anbiyam" value="<?php echo htmlspecialchars($family['anbiyam'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Main/Sub-Station</label>
            <?php
            $profile = $db->query("SELECT church_name, place, substations FROM parish_profile LIMIT 1")->fetch();
            $substations = [];
            if (!empty($profile['substations'])) {
                $decoded = json_decode($profile['substations'], true);
                if (is_array($decoded)) {
                    $substations = $decoded;
                }
            }
            ?>
            <select name="substation">
                <option value="">-- Select Substation --</option>
                <?php 
                // Add Main Station option
                $main_station = ($profile['church_name'] ?? 'Main Parish') . ' - ' . ($profile['place'] ?? '');
                ?>
                <option value="Main Station" <?php echo ($family['substation'] ?? '') === 'Main Station' ? 'selected' : ''; ?>>
                    🏛️ Main Station (<?php echo htmlspecialchars($main_station); ?>)
                </option>
                <?php foreach ($substations as $sub): 
                    $display = $sub['name'] . ($sub['place'] ? ' - ' . $sub['place'] : '');
                    $value = $sub['name'];
                ?>
                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($family['substation'] ?? '') === $value ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($display); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Phone / WhatsApp</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($family['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars($family['address'] ?? ''); ?></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="families.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Family</button>
        </div>
    </form>
</div>

<script>
    function updateSubscriptionAmount() {
        const type = document.getElementById('subscription_type').value;
        const amountField = document.getElementById('subscription_amount');
        if (type === 'monthly' && amountField.value == 1200) {
            amountField.value = 100;
        } else if (type === 'yearly' && amountField.value == 100) {
            amountField.value = 1200;
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.log("Web Speech API not supported.");
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        function addVoiceInput(element) {
            if (element.nextElementSibling && element.nextElementSibling.classList.contains('voice-input-btn')) return;

            element.parentElement.style.position = 'relative';

            const btn = document.createElement('span');
            btn.innerHTML = '🎤';
            btn.className = 'voice-input-btn';
            btn.title = 'Click to speak';
            btn.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; filter: grayscale(100%); transition: all 0.2s; z-index: 10; opacity: 0.6;';

            element.style.paddingRight = '40px';

            btn.onclick = function () {
                const recognition = new SpeechRecognition();
                recognition.lang = 'en-US';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                btn.style.filter = 'grayscale(0%)';
                btn.style.opacity = '1';
                btn.style.transform = 'translateY(-50%) scale(1.2)';

                recognition.start();

                recognition.onresult = function (event) {
                    let transcript = event.results[0][0].transcript;
                    // Remove trailing period if present
                    if (transcript.endsWith('.')) {
                        transcript = transcript.slice(0, -1);
                    }

                    // Auto-capitalize first letter
                    const formattedTranscript = transcript.charAt(0).toUpperCase() + transcript.slice(1);

                    if (element.value) {
                        element.value += ' ' + formattedTranscript;
                    } else {
                        element.value = formattedTranscript;
                    }
                    element.dispatchEvent(new Event('change'));
                    element.dispatchEvent(new Event('input'));
                };

                recognition.onspeechend = function () {
                    recognition.stop();
                    resetBtn();
                };

                recognition.onerror = function (event) {
                    console.error('Speech recognition error', event.error);
                    resetBtn();
                };

                function resetBtn() {
                    btn.style.filter = 'grayscale(100%)';
                    btn.style.opacity = '0.6';
                    btn.style.transform = 'translateY(-50%) scale(1)';
                }
            };

            element.parentNode.insertBefore(btn, element.nextSibling);
        }

        const inputs = document.querySelectorAll('input[type="text"], textarea');
        inputs.forEach(input => {
            if (input.type === 'hidden' || input.readOnly || input.disabled) return;
            addVoiceInput(input);
        });
    });
</script>

<?php include 'includes/footer.php'; ?>