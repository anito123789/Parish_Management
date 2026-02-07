<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$family_id = $_GET['family_id'] ?? null;
$p = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM parishioners WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    $family_id = $p['family_id'];
}

// Fetch Family Head and Spouse names (for auto-filling parents)
$family_info = null;
if ($family_id) {
    $family_info = $db->query("SELECT head_name, spouse_name FROM families WHERE id = $family_id")->fetch();
}

// Fetch Spouse or Head's Marriage Info for auto-filling (to use in JS)
$existing_marriage = null;
if ($family_id) {
    $m_info = $db->query("SELECT p.marriage_date, m.minister, m.witness1, m.witness2, m.place 
                        FROM parishioners p 
                        INNER JOIN marriages m ON p.id = m.parishioner_id 
                        WHERE p.family_id = $family_id 
                        AND (p.relationship = 'Husband' OR p.relationship = 'Wife' OR p.relationship = 'Head') 
                        LIMIT 1")->fetch();
    if ($m_info) {
        $existing_marriage = $m_info;
    }
}

// Fetch Detailed Sacramental Data (if editing)
$baptism = [];
$communion = [];
$confirmation = [];
$marriage = [];
$death = [];

if ($id) {
    $baptism = $db->query("SELECT * FROM baptisms WHERE parishioner_id = $id")->fetch() ?: [];
    $communion = $db->query("SELECT * FROM first_communions WHERE parishioner_id = $id")->fetch() ?: [];
    $confirmation = $db->query("SELECT * FROM confirmations WHERE parishioner_id = $id")->fetch() ?: [];
    $marriage = $db->query("SELECT * FROM marriages WHERE parishioner_id = $id")->fetch() ?: [];
    $death = $db->query("SELECT * FROM deaths WHERE parishioner_id = $id")->fetch() ?: [];

    // --- Intelligent Marriage Data Pre-fill for Spouses ---
    if (empty($marriage) && (in_array(strtolower($p['relationship'] ?? ''), ['husband', 'wife', 'head']))) {
        $curr_rel = strtolower($p['relationship'] ?? '');
        $target_sql = (in_array($curr_rel, ['husband', 'head'])) ? "('Wife')" : "('Husband', 'Head')";
        $spouse_m = $db->query("SELECT p.id, p.marriage_date, m.minister, m.witness1, m.witness2, m.place 
                               FROM parishioners p 
                               INNER JOIN marriages m ON p.id = m.parishioner_id 
                               WHERE p.family_id = $family_id 
                               AND p.relationship IN $target_sql 
                               LIMIT 1")->fetch();
        if ($spouse_m && $spouse_m['marriage_date']) {
            $p['marriage_date'] = $spouse_m['marriage_date'];
            $marriage = [
                'minister' => $spouse_m['minister'] ?? '',
                'witness1' => $spouse_m['witness1'] ?? '',
                'witness2' => $spouse_m['witness2'] ?? '',
                'place' => $spouse_m['place'] ?? ''
            ];
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle image upload
    $image_path = $p['image'] ?? null;
    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

    // Create uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle image file upload
    if (isset($_FILES['parishioner_image']) && $_FILES['parishioner_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['parishioner_image']['name'], PATHINFO_EXTENSION);
        $file_name = 'parishioner_' . time() . '_' . uniqid() . '.' . $ext;
        $full_dest = $upload_dir . DIRECTORY_SEPARATOR . $file_name;
        if (move_uploaded_file($_FILES['parishioner_image']['tmp_name'], $full_dest)) {
            $image_path = 'uploads/' . $file_name;
        }
    } elseif (isset($_POST['parishioner_image_data']) && !empty($_POST['parishioner_image_data'])) {
        // Handle camera capture (base64 data)
        $image_data = $_POST['parishioner_image_data'];
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $decoded = base64_decode($image_data);
        $file_name = 'parishioner_' . time() . '_' . uniqid() . '.png';
        $full_dest = $upload_dir . DIRECTORY_SEPARATOR . $file_name;
        if (file_put_contents($full_dest, $decoded)) {
            $image_path = 'uploads/' . $file_name;
        }
    }

    $father_name = $_POST['father_name'] ?? null;
    $mother_name = $_POST['mother_name'] ?? null;

    // Automatic parent mapping for Son/Daughter if empty
    $relationship = $_POST['relationship'];
    if (($relationship === 'Son' || $relationship === 'Daughter') && empty($father_name) && empty($mother_name)) {
        if ($family_info) {
            $father_name = $family_info['head_name'];
            $mother_name = $family_info['spouse_name'];
        }
    }

    $data = [
        $_POST['family_id'],
        $_POST['name'],
        $image_path,
        $_POST['dob'],
        $_POST['gender'],
        $relationship,
        $_POST['education'],
        $_POST['pious_association'],
        $_POST['occupation'],
        $father_name,
        $mother_name,
        $_POST['baptism_date'] ?: null,
        $_POST['communion_date'] ?: null,
        $_POST['confirmation_date'] ?: null,
        $_POST['marriage_date'] ?: null,
        $_POST['death_date'] ?: null,
        isset($_POST['is_deceased']) ? 1 : 0,
        $_POST['whatsapp'] ?? null
    ];

    if ($id) {
        // Update Parishioner
        $sql = "UPDATE parishioners SET family_id=?, name=?, image=?, dob=?, gender=?, relationship=?, education=?,
pious_association=?, occupation=?, father_name=?, mother_name=?, baptism_date=?, communion_date=?, confirmation_date=?,
marriage_date=?, death_date=?, is_deceased=?, whatsapp=? WHERE id=?";
        $data[] = $id;
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
    } else {
        // Insert Parishioner
        $sql = "INSERT INTO parishioners (family_id, name, image, dob, gender, relationship, education, pious_association,
occupation, father_name, mother_name, baptism_date, communion_date, confirmation_date, marriage_date, death_date,
is_deceased, whatsapp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        $id = $db->lastInsertId();
    }

    // --- Handle Detailed Sacrament Tables ---

    // Helper to Upsert
    if (!function_exists('upsert')) {
        function upsert($db, $table, $id, $data)
        {
            $exists = $db->query("SELECT parishioner_id FROM $table WHERE parishioner_id = $id")->fetch();
            if ($exists) {
                $set = [];
                $values = [];
                foreach ($data as $k => $v) {
                    $set[] = "$k=?";
                    $values[] = $v;
                }
                $values[] = $id;
                $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE parishioner_id=?";
                $db->prepare($sql)->execute($values);
            } else {
                $cols = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_fill(0, count($data), '?'));
                $values = array_values($data);
                array_unshift($values, $id); // Add ID at start
                $sql = "INSERT INTO $table (parishioner_id, $cols) VALUES (?, $placeholders)";
                $db->prepare($sql)->execute($values);
            }
        }
    }

    // Baptism
    if (!empty($_POST['baptism_date'])) {
        upsert($db, 'baptisms', $id, [
            'minister' => $_POST['b_minister'],
            'godfather' => $_POST['b_godfather'],
            'godmother' => $_POST['b_godmother'],
            'place' => $_POST['b_place'] ?? ''
        ]);
    }

    // Communion
    if (!empty($_POST['communion_date'])) {
        upsert($db, 'first_communions', $id, [
            'minister' => $_POST['c_minister'],
            'place' => $_POST['c_place'] ?? ''
        ]);
    }

    // Confirmation
    if (!empty($_POST['confirmation_date'])) {
        upsert($db, 'confirmations', $id, [
            'minister' => $_POST['conf_minister'],
            'sponsor' => $_POST['conf_sponsor'],
            'place' => $_POST['conf_place'] ?? ''
        ]);
    }

    // Marriage
    if (!empty($_POST['marriage_date'])) {
        upsert($db, 'marriages', $id, [
            'minister' => $_POST['m_minister'],
            'witness1' => $_POST['m_witness1'],
            'witness2' => $_POST['m_witness2'],
            'place' => $_POST['m_place'] ?? ''
        ]);
    }

    // Death
    if (isset($_POST['is_deceased']) && $_POST['is_deceased'] == '1') {
        upsert($db, 'deaths', $id, [
            'date_of_death' => $_POST['death_date'],
            'cause' => $_POST['death_cause'],
            'place_of_burial' => $_POST['death_place'],
            'minister' => $_POST['death_minister'],
            'cemetery' => $_POST['death_cemetery']
        ]);
    }

    header("Location: family_view.php?id=$family_id");
    exit;
}

include 'includes/header.php';
?>

<div style="max-width: 1100px; margin: auto;">
    <div
        style="display:flex; justify-content:space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="family_view.php?id=<?php echo $family_id; ?>" class="btn btn-secondary"
                style="margin-bottom: 0.5rem; width: auto;">&larr; Back to Family</a>
            <h2
                style="margin:0; font-size: 2rem; background: -webkit-linear-gradient(45deg, #4f46e5, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <?php echo $id ? 'Edit' : 'Add'; ?> Parishioner
            </h2>
        </div>
        <?php if ($id): ?>
            <a href="reports.php?parishioner_id=<?php echo $id; ?>" class="btn btn-primary"
                style="background: var(--text-main); width: auto;">🖨️ Certificates</a>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="family_id" value="<?php echo $family_id; ?>">

        <div class="grid">

            <!-- Personal Info Card -->
            <div class="card" style="border-top: 5px solid #6366f1;">
                <h3 style="color: #6366f1; margin-top:0;">📝 Personal Information</h3>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($p['name'] ?? ''); ?>">
                </div>
                <div class="grid">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($p['dob'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male" <?php if (($p['gender'] ?? '') == 'Male')
                                echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if (($p['gender'] ?? '') == 'Female')
                                echo 'selected'; ?>>Female
                            </option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($p['whatsapp'] ?? ''); ?>"
                        placeholder="e.g. 919876543210">
                    <small style="color: #64748b;">Include country code (e.g., 91 for India) without '+'</small>
                </div>
                <div class="form-group">
                    <label>Relationship to Family Head</label>
                    <input type="text" name="relationship" list="relations" id="relationship_input"
                        value="<?php echo htmlspecialchars($p['relationship'] ?? ''); ?>"
                        onchange="toggleParentFields()">
                    <datalist id="relations">
                        <option value="Husband">
                        <option value="Wife">
                        <option value="Son">
                        <option value="Daughter">
                        <option value="Parent">
                        <option value="Other">
                    </datalist>
                </div>

                <div id="parent_names_section" style="display: none;">
                    <div class="grid">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" id="father_name"
                                value="<?php echo htmlspecialchars($p['father_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" id="mother_name"
                                value="<?php echo htmlspecialchars($p['mother_name'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Image Upload / Camera Capture -->
                <div class="form-group">
                    <label>Parishioner Photo</label>
                    <div id="photo_preview_container"
                        style="margin-bottom: 1rem; position: relative; width: 200px; height: 200px; border-radius: 16px; overflow: hidden; border: 3px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm);">
                        <?php if ($p['image'] ?? null): ?>
                            <img id="current_photo" src="<?php echo htmlspecialchars($p['image']); ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span id="photo_placeholder" style="font-size: 3rem; color: #cbd5e1;">👤</span>
                        <?php endif; ?>
                        <img id="captured_preview" style="display:none; width: 100%; height: 100%; object-fit: cover;">
                        <video id="parishioner_video"
                            style="display:none; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                    </div>

                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <label for="parishioner_image" class="btn btn-secondary"
                            style="font-size: 0.85rem; padding: 0.5rem 1rem; cursor: pointer;">
                            📁 Upload File
                            <input type="file" name="parishioner_image" id="parishioner_image" accept="image/*"
                                style="display:none;" onchange="previewFile(this)">
                        </label>

                        <button type="button" id="start_camera_btn" onclick="toggleCamera()" class="btn btn-secondary"
                            style="font-size: 0.85rem; padding: 0.5rem 1rem;">
                            📷 Use Camera
                        </button>

                        <button type="button" id="capture_btn" onclick="takeSnapshot()" class="btn btn-primary"
                            style="display:none; font-size: 0.85rem; padding: 0.5rem 1rem;">
                            🎯 Capture
                        </button>

                        <button type="button" id="retake_btn" onclick="retakePhoto()" class="btn btn-danger"
                            style="display:none; font-size: 0.85rem; padding: 0.5rem 1rem; background: #f43f5e;">
                            🔄 Retake
                        </button>
                    </div>

                    <input type="hidden" name="parishioner_image_data" id="parishioner_image_data">
                    <canvas id="parishioner_canvas" style="display:none;"></canvas>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="card" style="border-top: 5px solid #10b981;">
                <h3 style="color: #10b981; margin-top:0;">💼 Profile Details</h3>
                <div class="form-group">
                    <label>Education</label>
                    <input type="text" name="education" value="<?php echo htmlspecialchars($p['education'] ?? ''); ?>"
                        placeholder="e.g. B.Sc, 10th Std">
                </div>
                <div class="form-group">
                    <label>Occupation</label>
                    <input type="text" name="occupation" value="<?php echo htmlspecialchars($p['occupation'] ?? ''); ?>"
                        placeholder="e.g. Teacher, Engineer">
                </div>
                <div class="form-group">
                    <label>Pious Association</label>
                    <input type="text" name="pious_association"
                        value="<?php echo htmlspecialchars($p['pious_association'] ?? ''); ?>"
                        placeholder="e.g. Legion of Mary">
                </div>
            </div>
        </div>

        <!-- Sacraments Section -->
        <h3
            style="margin: 2rem 0 1rem 0; font-size: 1.5rem; text-transform: uppercase; letter-spacing: 1px; color: var(--secondary);">
            🕊️ Sacraments</h3>

        <div class="grid">

            <!-- Baptism -->
            <div class="card" style="border-top: 4px solid #3b82f6;">
                <h4 style="color:#3b82f6; margin:0 0 1rem 0;">Baptism</h4>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="baptism_date"
                        value="<?php echo htmlspecialchars($p['baptism_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Minister</label>
                    <input type="text" name="b_minister"
                        value="<?php echo htmlspecialchars($baptism['minister'] ?? ''); ?>" placeholder="Rev. Fr...">
                </div>
                <div class="form-group">
                    <label>Godfather</label>
                    <input type="text" name="b_godfather"
                        value="<?php echo htmlspecialchars($baptism['godfather'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Godmother</label>
                    <input type="text" name="b_godmother"
                        value="<?php echo htmlspecialchars($baptism['godmother'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Place</label>
                    <input type="text" name="b_place" value="<?php echo htmlspecialchars($baptism['place'] ?? ''); ?>"
                        placeholder="e.g. St. Mary's Church">
                </div>
            </div>

            <!-- First Communion -->
            <div class="card" style="border-top: 4px solid #f59e0b;">
                <h4 style="color:#f59e0b; margin:0 0 1rem 0;">First Communion</h4>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="communion_date"
                        value="<?php echo htmlspecialchars($p['communion_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Minister</label>
                    <input type="text" name="c_minister"
                        value="<?php echo htmlspecialchars($communion['minister'] ?? ''); ?>" placeholder="Rev. Fr...">
                </div>
                <div class="form-group">
                    <label>Place</label>
                    <input type="text" name="c_place" value="<?php echo htmlspecialchars($communion['place'] ?? ''); ?>"
                        placeholder="e.g. St. Peter's Church">
                </div>
            </div>

            <!-- Confirmation -->
            <div class="card" style="border-top: 4px solid #ef4444;">
                <h4 style="color:#ef4444; margin:0 0 1rem 0;">Confirmation</h4>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="confirmation_date"
                        value="<?php echo htmlspecialchars($p['confirmation_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Minister (Bishop)</label>
                    <input type="text" name="conf_minister"
                        value="<?php echo htmlspecialchars($confirmation['minister'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Sponsor / Godparent</label>
                    <input type="text" name="conf_sponsor"
                        value="<?php echo htmlspecialchars($confirmation['sponsor'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Place</label>
                    <input type="text" name="conf_place"
                        value="<?php echo htmlspecialchars($confirmation['place'] ?? ''); ?>"
                        placeholder="e.g. Cathedral">
                </div>
            </div>

            <!-- Marriage -->
            <div class="card" style="border-top: 4px solid #8b5cf6;">
                <h4 style="color:#8b5cf6; margin:0 0 1rem 0;">Marriage</h4>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="marriage_date"
                        value="<?php echo htmlspecialchars($p['marriage_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Minister</label>
                    <input type="text" name="m_minister"
                        value="<?php echo htmlspecialchars($marriage['minister'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Witness 1</label>
                    <input type="text" name="m_witness1"
                        value="<?php echo htmlspecialchars($marriage['witness1'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Witness 2</label>
                    <input type="text" name="m_witness2"
                        value="<?php echo htmlspecialchars($marriage['witness2'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Place</label>
                    <input type="text" name="m_place" value="<?php echo htmlspecialchars($marriage['place'] ?? ''); ?>">
                </div>
            </div>

        </div>

        <!-- Death Section -->
        <div class="card" style="margin-top: 2rem; background: #f8fafc; border: 2px dashed #cbd5e1;">
            <div class="form-group">
                <label
                    style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-size: 1.1rem; color: #475569;">
                    <input type="checkbox" name="is_deceased" value="1" id="is_deceased_check" <?php if (!empty($p['is_deceased']))
                        echo 'checked'; ?> onclick="toggleDeathFields()"
                        style="width: 20px; height: 20px;">
                    Is Deceased?
                </label>
            </div>

            <div id="death_fields"
                style="display: none; margin-top: 1.5rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
                <h4 style="margin: 0 0 1rem 0; color: #475569;">⚰️ Death Record</h4>
                <div class="grid">
                    <div class="form-group">
                        <label>Date of Death</label>
                        <input type="date" name="death_date"
                            value="<?php echo htmlspecialchars($p['death_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Cause of Death</label>
                        <input type="text" name="death_cause"
                            value="<?php echo htmlspecialchars($death['cause'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Place of Burial</label>
                        <input type="text" name="death_place"
                            value="<?php echo htmlspecialchars($death['place_of_burial'] ?? ''); ?>">
                    </div>
                </div>
                <div class="grid" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label>Priest Name</label>
                        <input type="text" name="death_minister"
                            value="<?php echo htmlspecialchars($death['minister'] ?? ''); ?>" placeholder="Rev. Fr...">
                    </div>
                    <div class="form-group">
                        <label>Cemetery</label>
                        <input type="text" name="death_cemetery"
                            value="<?php echo htmlspecialchars($death['cemetery'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div
            style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; position: sticky; bottom: 1rem; z-index: 50; background: rgba(255,255,255,0.9); padding: 1rem; border-radius: 12px; box-shadow: 0 -4px 12px rgba(0,0,0,0.05);">
            <a href="family_view.php?id=<?php echo $family_id; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 3rem; font-size: 1.1rem;">Save
                Parishioner</button>
        </div>
    </form>
</div>

<script>
    function toggleDeathFields() {
        var check = document.getElementById('is_deceased_check');
        var fields = document.getElementById('death_fields');
        fields.style.display = check.checked ? 'block' : 'none';
    }

    const existingMarriageData = <?php echo json_encode($existing_marriage); ?>;
    const familyInfo = <?php echo json_encode($family_info); ?>;

    function toggleParentFields() {
        const rel = document.getElementById('relationship_input').value;
        const section = document.getElementById('parent_names_section');
        const fNameInput = document.getElementById('father_name');
        const mNameInput = document.getElementById('mother_name');

        const showList = ['Husband', 'Wife', 'Parent', 'Other', 'Son', 'Daughter'];

        if (showList.includes(rel)) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }

        if ((rel === 'Son' || rel === 'Daughter') && familyInfo) {
            if (!fNameInput.value) fNameInput.value = familyInfo.head_name || '';
            if (!mNameInput.value) mNameInput.value = familyInfo.spouse_name || '';
        }

        if ((rel === 'Wife' || rel === 'Husband' || rel === 'Head') && existingMarriageData) {
            const mDate = document.querySelector('input[name="marriage_date"]');
            const mMinister = document.querySelector('input[name="m_minister"]');
            const mWitness1 = document.querySelector('input[name="m_witness1"]');
            const mWitness2 = document.querySelector('input[name="m_witness2"]');
            const mPlace = document.querySelector('input[name="m_place"]');

            if (!mDate.value) mDate.value = existingMarriageData.marriage_date || '';
            if (!mMinister.value) mMinister.value = existingMarriageData.minister || '';
            if (!mWitness1.value) mWitness1.value = existingMarriageData.witness1 || '';
            if (!mWitness2.value) mWitness2.value = existingMarriageData.witness2 || '';
            if (!mPlace.value) mPlace.value = existingMarriageData.place || '';
        }
    }

    toggleDeathFields();
    toggleParentFields();

    let parishionerStream = null;

    function previewFile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('captured_preview').src = e.target.result;
                document.getElementById('captured_preview').style.display = 'block';
                if (document.getElementById('current_photo')) document.getElementById('current_photo').style.display = 'none';
                if (document.getElementById('photo_placeholder')) document.getElementById('photo_placeholder').style.display = 'none';
                document.getElementById('parishioner_video').style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function toggleCamera() {
        const video = document.getElementById('parishioner_video');
        const startBtn = document.getElementById('start_camera_btn');
        const captureBtn = document.getElementById('capture_btn');
        const placeholder = document.getElementById('photo_placeholder');
        const currentPhoto = document.getElementById('current_photo');
        const preview = document.getElementById('captured_preview');

        try {
            parishionerStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
            video.srcObject = parishionerStream;
            video.style.display = 'block';
            video.play();

            if (placeholder) placeholder.style.display = 'none';
            if (currentPhoto) currentPhoto.style.display = 'none';
            preview.style.display = 'none';

            startBtn.style.display = 'none';
            captureBtn.style.display = 'inline-flex';
        } catch (err) {
            alert('Camera access denied or not available: ' + err.message);
        }
    }

    function takeSnapshot() {
        const video = document.getElementById('parishioner_video');
        const canvas = document.getElementById('parishioner_canvas');
        const preview = document.getElementById('captured_preview');
        const captureBtn = document.getElementById('capture_btn');
        const retakeBtn = document.getElementById('retake_btn');

        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // Mirror the image for the canvas to match the preview
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/png');
        document.getElementById('parishioner_image_data').value = imageData;

        preview.src = imageData;
        preview.style.display = 'block';
        video.style.display = 'none';

        captureBtn.style.display = 'none';
        retakeBtn.style.display = 'inline-flex';

        if (parishionerStream) {
            parishionerStream.getTracks().forEach(track => track.stop());
        }
    }

    function retakePhoto() {
        document.getElementById('retake_btn').style.display = 'none';
        document.getElementById('parishioner_image_data').value = '';
        toggleCamera();
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
            // Check if already added
            if (element.nextElementSibling && element.nextElementSibling.classList.contains('voice-input-btn')) return;
            // Check if element is visible
            if (element.offsetParent === null) return;

            // Ensure we are appending to a suitable container (form-group usually)
            // If parent is a label (like in file upload), skip
            if (element.parentElement.tagName === 'LABEL') return;

            element.parentElement.style.position = 'relative';

            const btn = document.createElement('span');
            btn.innerHTML = '🎤';
            btn.className = 'voice-input-btn';
            btn.title = 'Click to speak';
            btn.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem; filter: grayscale(100%); transition: all 0.2s; z-index: 10; opacity: 0.6;';

            // Add padding only if it doesn't break layout
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

                    // Simple append logic
                    if (element.value) {
                        // Check if last char is space
                        if (element.value.slice(-1) !== ' ') {
                            element.value += ' ';
                        }
                        element.value += transcript;
                    } else {
                        // Capitalize only if it's the start
                        element.value = transcript.charAt(0).toUpperCase() + transcript.slice(1);
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

            // Insert after input
            if (element.nextSibling) {
                element.parentNode.insertBefore(btn, element.nextSibling);
            } else {
                element.parentNode.appendChild(btn);
            }
        }

        // Run on load and also observe DOM changes if fields are dynamically shown
        function initVoice() {
            const inputs = document.querySelectorAll('input[type="text"], textarea');
            inputs.forEach(input => {
                if (input.type === 'hidden' || input.readOnly || input.disabled) return;
                addVoiceInput(input);
            });
        }

        initVoice();

        // Optional: Re-run if dynamic fields (like death fields) are shown
        const observer = new MutationObserver(function (mutations) {
            initVoice();
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
    });
</script>

<?php include 'includes/footer.php'; ?>