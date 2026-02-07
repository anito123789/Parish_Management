<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$item = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM parish_profile WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}
if (!$item)
    $item = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['church_name'];
    $place = $_POST['place'];
    $diocese = $_POST['diocese'];
    $vicar = $_POST['vicar'];
    $asst_vicar = $_POST['asst_vicar'];
    $year = $_POST['established_year'];

    $image_path = $item['church_image'] ?? '';
    $seal_path = $item['seal_image'] ?? '';
    $signature_path = $item['signature_image'] ?? '';

    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Process Church Logo
    if (isset($_FILES['church_image']) && $_FILES['church_image']['error'] === 0) {
        $ext = pathinfo($_FILES['church_image']['name'], PATHINFO_EXTENSION);
        $filename = 'parish_logo_' . time() . '.' . $ext;
        $full_dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($_FILES['church_image']['tmp_name'], $full_dest)) {
            $image_path = 'uploads/' . $filename;
        }
    } elseif (!empty($_POST['church_image_data'])) {
        $image_data = $_POST['church_image_data'];
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $decoded = base64_decode($image_data);
        $filename = 'parish_logo_' . time() . '_' . uniqid() . '.png';
        $full_dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($full_dest, $decoded)) {
            $image_path = 'uploads/' . $filename;
        }
    }

    // Process Seal Image
    if (isset($_FILES['seal_image']) && $_FILES['seal_image']['error'] === 0) {
        $ext = pathinfo($_FILES['seal_image']['name'], PATHINFO_EXTENSION);
        $filename = 'parish_seal_' . time() . '.' . $ext;
        $full_dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($_FILES['seal_image']['tmp_name'], $full_dest)) {
            $seal_path = 'uploads/' . $filename;
        }
    }

    // Process Signature Image
    if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] === 0) {
        $ext = pathinfo($_FILES['signature_image']['name'], PATHINFO_EXTENSION);
        $filename = 'parish_sig_' . time() . '.' . $ext;
        $full_dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($_FILES['signature_image']['tmp_name'], $full_dest)) {
            $signature_path = 'uploads/' . $filename;
        }
    }

    $msg_birthday = $_POST['msg_birthday'] ?? '';
    $msg_marriage = $_POST['msg_marriage'] ?? '';
    $msg_death = $_POST['msg_death'] ?? '';
    
    // Handle substations JSON
    $substations_json = '';
    if (isset($_POST['substations_data'])) {
        $substations_json = $_POST['substations_data'];
    }
    
    $enable_seal = isset($_POST['enable_seal']) ? 1 : 0;
    $enable_signature = isset($_POST['enable_signature']) ? 1 : 0;

    if ($id) {
        $stmt = $db->prepare("UPDATE parish_profile SET church_name=?, place=?, diocese=?, vicar=?, asst_vicar=?, established_year=?, church_image=?, msg_birthday=?, msg_marriage=?, msg_death=?, seal_image=?, signature_image=?, enable_seal=?, enable_signature=?, substations=? WHERE id=?");
        $stmt->execute([$name, $place, $diocese, $vicar, $asst_vicar, $year, $image_path, $msg_birthday, $msg_marriage, $msg_death, $seal_path, $signature_path, $enable_seal, $enable_signature, $substations_json, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO parish_profile (church_name, place, diocese, vicar, asst_vicar, established_year, church_image, msg_birthday, msg_marriage, msg_death, seal_image, signature_image, enable_seal, enable_signature, substations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $place, $diocese, $vicar, $asst_vicar, $year, $image_path, $msg_birthday, $msg_marriage, $msg_death, $seal_path, $signature_path, $enable_seal, $enable_signature, $substations_json]);
    }
    header("Location: parish_profile.php");
    exit;
}

include 'includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: auto;">
    <h2>
        <?php echo $id ? 'Edit' : 'Create'; ?> Parish Profile
    </h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Church Name</label>
            <input type="text" name="church_name" required
                value="<?php echo htmlspecialchars($item['church_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Place / City</label>
            <input type="text" name="place" required value="<?php echo htmlspecialchars($item['place'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Diocese</label>
            <input type="text" name="diocese" required value="<?php echo htmlspecialchars($item['diocese'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Parish Priest / Vicar</label>
            <input type="text" name="vicar" value="<?php echo htmlspecialchars($item['vicar'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Assistant Parish Priest</label>
            <input type="text" name="asst_vicar" value="<?php echo htmlspecialchars($item['asst_vicar'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Established Year</label>
            <input type="text" name="established_year"
                value="<?php echo htmlspecialchars($item['established_year'] ?? ''); ?>">
        </div>
        
        <!-- Substations Management -->
        <div class="form-group" style="margin-top: 2rem; border-top: 2px solid #e2e8f0; padding-top: 2rem;">
            <label style="font-size: 1.1rem; color: var(--primary-dark); display: flex; align-items: center; gap: 0.5rem;">
                🏛️ Substations Management
            </label>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">Add substations with church name and place</p>
            
            <div id="substations-container" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Substations will be added here dynamically -->
            </div>
            
            <button type="button" onclick="addSubstation()" class="btn btn-secondary" style="margin-top: 1rem;">
                + Add Substation
            </button>
            
            <input type="hidden" name="substations_data" id="substations_data">
        </div>
        <div class="form-group">
            <label>Church Logo / Image</label>
            <div id="photo_preview_container"
                style="margin-bottom: 1rem; position: relative; width: 300px; height: 180px; border-radius: 12px; overflow: hidden; border: 3px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm);">
                <?php if (!empty($item['church_image'])): ?>
                    <img id="current_photo" src="<?php echo htmlspecialchars($item['church_image']); ?>"
                        style="width: 100%; height: 100%; object-fit: contain;">
                <?php else: ?>
                    <span id="photo_placeholder" style="font-size: 3rem; color: #cbd5e1;">⛪</span>
                <?php endif; ?>
                <img id="captured_preview" style="display:none; width: 100%; height: 100%; object-fit: contain;">
                <video id="church_video" style="display:none; width: 100%; height: 100%; object-fit: cover;"></video>
            </div>

            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <label for="church_image_file" class="btn btn-secondary"
                    style="font-size: 0.85rem; padding: 0.5rem 1rem; cursor: pointer;">
                    📁 Upload File
                    <input type="file" name="church_image" id="church_image_file" accept="image/*" style="display:none;"
                        onchange="previewFile(this)">
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

            <input type="hidden" name="church_image_data" id="church_image_data">
            <canvas id="church_canvas" style="display:none;"></canvas>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 2rem;">
            <!-- Seal Section -->
            <div class="form-group"
                style="background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <label style="color: #6366f1; font-weight: 800;">Official Seal</label>
                <div
                    style="width: 100px; height: 100px; margin: 0.5rem 0; border: 2px dashed #cbd5e1; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: white;">
                    <?php if (!empty($item['seal_image'])): ?>
                        <img id="seal_form_preview" src="<?php echo htmlspecialchars($item['seal_image']); ?>"
                            style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <span id="seal_form_placeholder" style="color: #cbd5e1; font-size: 1.5rem;">⭕</span>
                        <img id="seal_form_preview" style="display:none; width: 100%; height: 100%; object-fit: contain;">
                    <?php endif; ?>
                </div>
                <input type="file" name="seal_image" accept="image/*"
                    onchange="previewThumbnail(this, 'seal_form_preview', 'seal_form_placeholder')">
                <div style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                        <input type="checkbox" name="enable_seal" <?php echo ($item['enable_seal'] ?? 0) ? 'checked' : ''; ?>>
                        Show Seal on Reports
                    </label>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="form-group"
                style="background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <label style="color: #6366f1; font-weight: 800;">Vicar Signature</label>
                <div
                    style="width: 100%; height: 60px; margin: 0.5rem 0; border: 2px dashed #cbd5e1; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <?php if (!empty($item['signature_image'])): ?>
                        <img id="sig_form_preview" src="<?php echo htmlspecialchars($item['signature_image']); ?>"
                            style="max-height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <span id="sig_form_placeholder" style="color: #cbd5e1; font-size: 1rem;">✍️</span>
                        <img id="sig_form_preview" style="display:none; max-height: 100%; object-fit: contain;">
                    <?php endif; ?>
                </div>
                <input type="file" name="signature_image" accept="image/*"
                    onchange="previewThumbnail(this, 'sig_form_preview', 'sig_form_placeholder')">
                <div style="margin-top: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                        <input type="checkbox" name="enable_signature" <?php echo ($item['enable_signature'] ?? 0) ? 'checked' : ''; ?>>
                        Show Signature on Reports
                    </label>
                </div>
            </div>
        </div>

</div>

<div style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
    <h3 style="margin-top: 0; color: #25d366;">💬 WhatsApp Message Templates</h3>
    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
        Use placeholders: <strong>[Name]</strong> for parishioner's name, <strong>[him/her]</strong>, <strong>[his/her]</strong>, <strong>[he/she]</strong> for gender-specific pronouns.
    </p>

    <div class="form-group">
        <label>Birthday Message</label>
        <textarea name="msg_birthday" rows="3" style="width: 100%; font-family: sans-serif;"><?php
        $church_name = $item['church_name'] ?? '[Parish Name]';
        $vicar = !empty($item['vicar']) ? " and Rev. Fr. " . $item['vicar'] : "";
        $verb = !empty($item['vicar']) ? "wish" : "wishes";
        $def_bday = "Dear [Name], the Parish of " . $church_name . $vicar . " " . $verb . " [him/her] a very Happy Birthday! May God bless [him/her] with abundant joy and health. Have a wonderful day! 🎂🙏";
        echo htmlspecialchars($item['msg_birthday'] ?? $def_bday);
        ?></textarea>
    </div>

    <div class="form-group">
        <label>Marriage Anniversary Message</label>
        <textarea name="msg_marriage" rows="3" style="width: 100%; font-family: sans-serif;"><?php
        $church_name = $item['church_name'] ?? '[Parish Name]';
        $vicar_pref = !empty($item['vicar']) ? "Rev. Fr. " . $item['vicar'] . " and " : "";
        $def_mar = "Happy Marriage Anniversary to [Name]! " . $vicar_pref . "the Parish of " . $church_name . " wish [him/her] many more years of love and togetherness. May God's grace always be upon [his/her] family. 💍✨";
        echo htmlspecialchars($item['msg_marriage'] ?? $def_mar);
        ?></textarea>
    </div>

    <div class="form-group">
        <label>Death Anniversary Message</label>
        <textarea name="msg_death" rows="3" style="width: 100%; font-family: sans-serif;"><?php
        $church_name = $item['church_name'] ?? '[Parish Name]';
        $vicar_pref = !empty($item['vicar']) ? "Rev. Fr. " . $item['vicar'] . " and " : "";
        $def_death = "Remembering [Name] on [his/her] Death Anniversary today. " . $vicar_pref . "the Parish of " . $church_name . " join you in prayer for the departed soul. May [he/she] rest in eternal peace. 🕯️🙏";
        echo htmlspecialchars($item['msg_death'] ?? $def_death);
        ?></textarea>
    </div>
</div>


<div style="margin-top: 1rem; text-align: right;">
    <button type="submit" class="btn btn-primary">Save Profile</button>
</div>
</form>
</div>

<script>
    let substations = <?php echo !empty($item['substations']) ? $item['substations'] : '[]'; ?>;
    let substationIndex = 0;

    function addSubstation(name = '', place = '') {
        const container = document.getElementById('substations-container');
        const index = substationIndex++;
        
        const div = document.createElement('div');
        div.className = 'substation-item';
        div.id = 'substation-' + index;
        div.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.5rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;';
        
        div.innerHTML = `
            <input type="text" placeholder="Church Name" value="${name}" 
                onchange="updateSubstations()" 
                style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            <input type="text" placeholder="Place" value="${place}" 
                onchange="updateSubstations()" 
                style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            <button type="button" onclick="removeSubstation(${index})" 
                style="padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer;">
                ×
            </button>
        `;
        
        container.appendChild(div);
        updateSubstations();
    }

    function removeSubstation(index) {
        const element = document.getElementById('substation-' + index);
        if (element) {
            element.remove();
            updateSubstations();
        }
    }

    function updateSubstations() {
        const container = document.getElementById('substations-container');
        const items = container.querySelectorAll('.substation-item');
        const data = [];
        
        items.forEach(item => {
            const inputs = item.querySelectorAll('input');
            const name = inputs[0].value.trim();
            const place = inputs[1].value.trim();
            if (name || place) {
                data.push({ name, place });
            }
        });
        
        document.getElementById('substations_data').value = JSON.stringify(data);
    }

    // Load existing substations on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (Array.isArray(substations) && substations.length > 0) {
            substations.forEach(sub => {
                addSubstation(sub.name || '', sub.place || '');
            });
        }
    });
</script>

<script>
    let churchStream = null;

    function previewFile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('captured_preview').src = e.target.result;
                document.getElementById('captured_preview').style.display = 'block';
                if (document.getElementById('current_photo')) document.getElementById('current_photo').style.display = 'none';
                if (document.getElementById('photo_placeholder')) document.getElementById('photo_placeholder').style.display = 'none';
                document.getElementById('church_video').style.display = 'none';
                document.getElementById('start_camera_btn').style.display = 'inline-flex';
                document.getElementById('capture_btn').style.display = 'none';
                document.getElementById('retake_btn').style.display = 'none';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function toggleCamera() {
        const video = document.getElementById('church_video');
        const startBtn = document.getElementById('start_camera_btn');
        const captureBtn = document.getElementById('capture_btn');
        const placeholder = document.getElementById('photo_placeholder');
        const currentPhoto = document.getElementById('current_photo');
        const preview = document.getElementById('captured_preview');

        try {
            churchStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
            video.srcObject = churchStream;
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
        const video = document.getElementById('church_video');
        const canvas = document.getElementById('church_canvas');
        const preview = document.getElementById('captured_preview');
        const captureBtn = document.getElementById('capture_btn');
        const retakeBtn = document.getElementById('retake_btn');

        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/png');
        document.getElementById('church_image_data').value = imageData;

        preview.src = imageData;
        preview.style.display = 'block';
        video.style.display = 'none';

        captureBtn.style.display = 'none';
        retakeBtn.style.display = 'inline-flex';

        if (churchStream) {
            churchStream.getTracks().forEach(track => track.stop());
        }
    }

    function retakePhoto() {
        document.getElementById('retake_btn').style.display = 'none';
        document.getElementById('church_image_data').value = '';
        toggleCamera();
    }
    function previewThumbnail(input, previewId, placeholderId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById(previewId);
                img.src = e.target.result;
                img.style.display = 'block';
                if (placeholderId) {
                    document.getElementById(placeholderId).style.display = 'none';
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>