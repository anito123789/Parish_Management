<?php
require_once 'db.php';
include 'includes/header.php';

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $db->prepare("DELETE FROM families WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='families.php';</script>";
    exit;
}

// Handle Delete All Request
if (isset($_POST['delete_all_families'])) {
    $db->query("DELETE FROM families");
    $db->query("DELETE FROM parishioners");
    $db->query("DELETE FROM subscriptions");
    echo "<script>alert('All families and associated data have been deleted.'); window.location.href='families.php';</script>";
    exit;
}

$search = $_GET['search'] ?? '';
$per_page = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
if ($per_page <= 0)
    $per_page = 20;

// Pagination
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

$where = "WHERE name LIKE ? OR anbiyam LIKE ? OR family_code LIKE ?";
$params = ["%$search%", "%$search%", "%$search%"];

$count = $db->prepare("SELECT COUNT(*) FROM families $where");
$count->execute($params);
$total = $count->fetchColumn();
$pages = ceil($total / $per_page);

// Search by code as well
$sql = "SELECT f.*, 
        (SELECT image FROM parishioners WHERE family_id = f.id AND (relationship = 'Head' OR relationship = 'Husband') LIMIT 1) as head_p_image,
        (SELECT image FROM parishioners WHERE family_id = f.id AND (relationship = 'Spouse' OR relationship = 'Wife') LIMIT 1) as spouse_p_image
        FROM families f $where ORDER BY f.name ASC LIMIT $per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$families = $stmt->fetchAll();
?>

<div class="card">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin:0;">👨‍👩‍👧‍👦 Families Directory</h2>
            <p style="margin:0; color: var(--secondary);">Manage all registered families</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <form method="POST" onsubmit="return false;" style="display: inline-block;">
                <button type="button" onclick="confirmDeleteAllFamilies(this.form)" class="btn btn-danger"
                    style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;">🗑️ Delete All</button>
                <input type="hidden" name="delete_all_families" value="1">
            </form>
            <a href="import_google_csv.php" class="btn btn-success"
                style="background: #10b981; color: white; white-space: nowrap;">
                <i class="fas fa-file-csv"></i> Import Google CSV
            </a>
            <a href="family_form.php" class="btn btn-primary" style="white-space: nowrap;">+ Add New Family</a>
        </div>
    </div>

    <script>
        function confirmDeleteAllFamilies(form) {
            let confirmation = prompt("🚨 DANGER: You are about to delete EVERY FAMILY and ALL MEMBERS in the entire system.\n\nThis cannot be undone. To confirm, type EXACTLY: DELETE ALL FAMILIES");
            if (confirmation === "DELETE ALL FAMILIES") {
                form.submit();
            } else {
                alert("Deletion aborted. Type match failed.");
            }
        }
    </script>

    <form method="GET" id="search_form" class="form-group"
        style="display: flex; gap: 0.5rem; margin-bottom: 2rem; align-items: center; flex-wrap: wrap;">
        <input type="text" name="search" id="search_input" placeholder="Search by name, ID or Anbiyam..."
            value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">

        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label style="font-size: 0.9rem; color: var(--secondary);">Show:</label>
            <select name="limit" onchange="this.form.submit()"
                style="padding: 0.5rem; border-radius: 8px; border: 1px solid #ddd;">
                <?php foreach ([2, 5, 10, 20, 50, 100, 200] as $l): ?>
                    <option value="<?php echo $l; ?>" <?php echo $per_page == $l ? 'selected' : ''; ?>><?php echo $l; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">Search</button>
        <button type="button" onclick="startQRScanner()" class="btn btn-primary">📷 Scan QR</button>
    </form>

    <!-- QR Scanner Modal -->
    <div id="qr_scanner_modal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
        <div
            style="background: white; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%; position: relative;">
            <button onclick="stopQRScanner()"
                style="position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 1.2rem;">×</button>
            <h3 style="margin-top: 0;">Scan Family QR Code</h3>
            <div id="qr_reader" style="width: 100%;"></div>
            <p id="qr_result" style="margin-top: 1rem; color: #10b981; font-weight: bold;"></p>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Family Name</th>
                    <th>Anbiyam</th>
                    <th>Head & Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($families as $f): ?>
                    <tr>
                        <td><span
                                style="font-family: monospace; background: #e2e8f0; padding: 4px 8px; border-radius: 6px; font-weight:bold;"><?php echo htmlspecialchars($f['family_code'] ?? '-'); ?></span>
                        </td>
                        <td>
                            <a href="family_view.php?id=<?php echo $f['id']; ?>"
                                style="font-weight: 700; color: var(--primary); text-decoration:none; font-size:1.05rem;">
                                <?php echo htmlspecialchars($f['name'] ?? ''); ?>
                            </a>
                        </td>
                        <td><span class="status-badge"
                                style="background:#f1f5f9; color:#475569;"><?php echo htmlspecialchars($f['anbiyam'] ?? ''); ?></span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="display: flex; height: 35px;">
                                    <?php
                                    $h_img = $f['head_p_image'] ?: ($f['head_image'] ?? null);
                                    $s_img = $f['spouse_p_image'] ?: ($f['spouse_image'] ?? null);
                                    ?>
                                    <?php if ($h_img): ?>
                                        <img src="<?php echo htmlspecialchars($h_img ?? ''); ?>"
                                            style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; margin-right: -10px; position: relative; z-index: 2;"
                                            title="Head: <?php echo htmlspecialchars($f['head_name'] ?? ''); ?>">
                                    <?php endif; ?>
                                    <?php if ($s_img): ?>
                                        <img src="<?php echo htmlspecialchars($s_img ?? ''); ?>"
                                            style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; position: relative; z-index: 1;"
                                            title="Spouse: <?php echo htmlspecialchars($f['spouse_name'] ?? ''); ?>">
                                    <?php endif; ?>
                                    <?php if (!$h_img && !$s_img): ?>
                                        <div
                                            style="width: 35px; height: 35px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #94a3b8;">
                                            🏠</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <small style="color:var(--secondary);">Head:</small>
                                    <?php echo htmlspecialchars($f['head_name'] ?? 'N/A'); ?><br>
                                    <small style="color:var(--secondary);">Ph:</small>
                                    <?php echo htmlspecialchars($f['phone'] ?? ''); ?>
                                </div>
                            </div>
                        </td>
                        <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($f['address'] ?? ''); ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="family_view.php?id=<?php echo $f['id']; ?>" class="btn btn-secondary"
                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto;">View</a>
                            <a href="family_form.php?id=<?php echo $f['id']; ?>" class="btn btn-secondary"
                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto;">Edit</a>
                            <a href="families.php?delete_id=<?php echo $f['id']; ?>" class="btn btn-danger"
                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; width: auto;"
                                onclick="return confirm('⚠️ Warning: Deleting this family will PERMANENTLY delete all its members, sacrament records, and subscriptions.\n\nAre you sure you want to proceed?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&limit=<?php echo $per_page; ?>"
                    class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;

    function startQRScanner() {
        const modal = document.getElementById('qr_scanner_modal');
        const resultLabel = document.getElementById('qr_result');
        modal.style.display = 'flex';
        resultLabel.textContent = 'Initializing camera...';
        resultLabel.style.color = '#4f46e5';

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr_reader");
        }

        html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            (decodedText, decodedResult) => {
                resultLabel.textContent = '✔ Detected: ' + decodedText;
                resultLabel.style.color = '#10b981';

                // Fill search and submit
                document.getElementById('search_input').value = decodedText;

                setTimeout(() => {
                    stopQRScanner();
                    document.getElementById('search_form').submit();
                }, 800);
            },
            (errorMessage) => {
                // Ignore scanning failures
            }
        ).catch((err) => {
            console.error('QR Scanner Error:', err);
            resultLabel.textContent = 'Error: ' + err;
            resultLabel.style.color = '#ef4444';
        });
    }

    function stopQRScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                document.getElementById('qr_scanner_modal').style.display = 'none';
            }).catch((err) => {
                console.error('Error stopping scanner:', err);
                document.getElementById('qr_scanner_modal').style.display = 'none';
            });
        } else {
            document.getElementById('qr_scanner_modal').style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>