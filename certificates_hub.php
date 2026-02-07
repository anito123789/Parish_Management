<?php
require_once 'db.php';
include 'includes/header.php';

$search = $_GET['search'] ?? '';

// Search Query
$list = [];
if (!empty($search)) {
    $stmt = $db->prepare("SELECT p.*, f.id as family_id_num FROM parishioners p 
                          LEFT JOIN families f ON p.family_id = f.id 
                          WHERE p.name LIKE ? OR f.id LIKE ? OR f.family_code LIKE ?
                          ORDER BY p.name ASC LIMIT 50");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $list = $stmt->fetchAll();
}
?>

<div class="card" style="max-width: 900px; margin: 2rem auto;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; color: var(--primary-dark);">📜 Certificate Hub</h1>
        <p style="color: var(--secondary); font-size: 1.1rem;">Search and print official church certificates</p>
    </div>

    <form method="GET" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; position: relative;">
        <input type="text" name="search" placeholder="Type name or Family ID..."
            value="<?php echo htmlspecialchars($search); ?>"
            style="flex: 1; padding: 1.2rem; font-size: 1.1rem; border-radius: 15px; border: 2px solid #e2e8f0; outline: none; transition: border-color 0.2s;"
            onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
        <button type="submit" class="btn btn-primary"
            style="padding: 0 2rem; border-radius: 15px; font-weight: 700; font-size: 1rem;">🔍 Search</button>
        <button type="button" class="btn btn-secondary" onclick="startScanner()"
            style="padding: 0 1.5rem; border-radius: 15px; font-weight: 700; font-size: 1rem;">📷 Scan QR</button>
    </form>

    <?php if (!empty($search)): ?>
        <?php if (empty($list)): ?>
            <div style="text-align: center; padding: 3rem; color: #64748b; background: #f8fafc; border-radius: 15px;">
                No results found for "<strong><?php echo htmlspecialchars($search); ?></strong>"
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($list as $r): ?>
                    <div style="background: #fff; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;"
                        onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'"
                        onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        <div>
                            <h3 style="margin: 0; font-size: 1.25rem;"><?php echo htmlspecialchars($r['name']); ?></h3>
                            <div style="font-size: 0.9rem; color: #64748b; margin-top: 0.2rem;">
                                Relationship: <strong><?php echo htmlspecialchars($r['relationship']); ?></strong> |
                                Family ID: <strong><?php echo htmlspecialchars($r['family_id_num']); ?></strong>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end; max-width: 50%;">
                            <a href="family_view.php?id=<?php echo $r['family_id']; ?>" target="_blank" class="simple-btn"
                                style="background: white; border: 1px solid var(--primary); color: var(--primary);">
                                🏠 View Family
                            </a>
                            <?php if ($r['is_deceased']): ?>
                                <a href="report_death.php?id=<?php echo $r['id']; ?>" target="_blank" class="simple-btn"
                                    style="background: #1e293b; color: white;">Death Certificate</a>
                            <?php else: ?>
                                <?php if (!empty($r['baptism_date'])): ?>
                                    <a href="report_baptism.php?id=<?php echo $r['id']; ?>" target="_blank" class="simple-btn">Baptism</a>
                                <?php endif; ?>
                                <?php if (!empty($r['communion_date'])): ?>
                                    <a href="report_communion.php?id=<?php echo $r['id']; ?>" target="_blank"
                                        class="simple-btn">Communion</a>
                                <?php endif; ?>
                                <?php if (!empty($r['confirmation_date'])): ?>
                                    <a href="report_confirmation.php?id=<?php echo $r['id']; ?>" target="_blank"
                                        class="simple-btn">Confirmation</a>
                                <?php endif; ?>
                                <?php if (!empty($r['marriage_date'])): ?>
                                    <a href="report_marriage.php?id=<?php echo $r['id']; ?>" target="_blank" class="simple-btn">Marriage</a>
                                <?php endif; ?>
                                <a href="report_banns.php?id=<?php echo $r['id']; ?>" target="_blank" class="simple-btn"
                                    style="background: #fffbeb; color: #92400e; border-color: #fef3c7;">Banns</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; color: #cbd5e1;">
            <span style="font-size: 4rem;">📑</span>
            <p style="margin-top: 1rem; font-size: 1.1rem;">Search for a member to generate certificates</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .simple-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
        border-radius: 10px;
        background: #f0f7ff;
        color: #1d4ed8;
        border: 1px solid #dbeafe;
        transition: all 0.2s;
    }

    .simple-btn:hover {
        filter: brightness(0.95);
        transform: translateY(-1px);
    }
</style>

<!-- Scan Modal -->
<div id="scan_modal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; padding:1.5rem; border-radius:15px; width:90%; max-width:500px; text-align:center;">
        <h3>📷 Scan Family QR</h3>
        <div id="reader" style="width:100%; min-height:300px; background:#f1f5f9; margin-bottom:1rem;"></div>
        <button onclick="closeScanner()" class="btn btn-secondary" style="width:100%;">Close</button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    let html5QrcodeScanner = null;

    function startScanner() {
        document.getElementById('scan_modal').style.display = 'flex';

        if (html5QrcodeScanner) return; // Already initialized

        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: { width: 250, height: 250 } }, false);

        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Handle the scanned code
        // Expecting URL like: .../family_view.php?id=123
        try {
            const url = new URL(decodedText);
            const id = url.searchParams.get("id");
            if (id) {
                // Redirect directly to family view or search for it
                window.location.href = `family_view.php?id=${id}`;
            } else {
                alert("Invalid QR Code: No Family ID found.");
            }
        } catch (e) {
            // Fallback if not a URL, maybe just the ID or Code was scanned?
            // If it's just a number, assume ID
            if (!isNaN(decodedText)) {
                window.location.href = `family_view.php?id=${decodedText}`;
            } else {
                // Stick it in search
                document.querySelector('input[name="search"]').value = decodedText;
                document.querySelector('form').submit();
            }
        }
        closeScanner();
    }

    function onScanFailure(error) {
        // handle scan failure, usually better to ignore and keep scanning.
        // console.warn(`Code scan error = ${error}`);
    }

    function closeScanner() {
        document.getElementById('scan_modal').style.display = 'none';
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
                document.getElementById('reader').innerHTML = ""; // Clean up
            }).catch(error => {
                console.error("Failed to clear html5QrcodeScanner. ", error);
            });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>