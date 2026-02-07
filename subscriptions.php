<?php
require_once 'db.php';
include 'includes/header.php';

$year_filter = $_GET['year'] ?? date('Y');
$family_id_filter = $_GET['family_id'] ?? null;
$search = $_GET['search'] ?? '';

// Handle search by family code or name
if (!empty($search) && !$family_id_filter) {
    $stmt = $db->prepare("SELECT id FROM families WHERE family_code LIKE ? OR name LIKE ? LIMIT 1");
    $stmt->execute(["%$search%", "%$search%"]);
    $result = $stmt->fetch();
    if ($result) {
        $family_id_filter = $result['id'];
    }
}

// If viewing specific family
if ($family_id_filter) {
    $stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
    $stmt->execute([$family_id_filter]);
    $selected_family = $stmt->fetch();

    if ($selected_family) {
        // Get husband and wife names
        $stmt = $db->prepare("SELECT name FROM parishioners WHERE family_id = ? AND (relationship = 'Husband' OR relationship = 'Head') LIMIT 1");
        $stmt->execute([$family_id_filter]);
        $husband = $stmt->fetch();
        $husband_name = $husband ? $husband['name'] : '-';

        $stmt = $db->prepare("SELECT name FROM parishioners WHERE family_id = ? AND (relationship = 'Wife' OR relationship = 'Spouse') LIMIT 1");
        $stmt->execute([$family_id_filter]);
        $wife = $stmt->fetch();
        $wife_name = $wife ? $wife['name'] : '-';

        // Get all payments for this family
        $stmt = $db->prepare("SELECT * FROM subscriptions WHERE family_id = ? ORDER BY paid_date DESC");
        $stmt->execute([$family_id_filter]);
        $family_payments = $stmt->fetchAll();

        // Calculate totals
        $total_paid = array_sum(array_column($family_payments, 'amount'));

        // Calculate due
        $sub_type = $selected_family['subscription_type'] ?? 'yearly';
        $sub_amount = $selected_family['subscription_amount'] ?? ANNUAL_SUBSCRIPTION_AMOUNT;
        $sub_start = $selected_family['subscription_start_date'] ?? $selected_family['created_at'];

        $start = new DateTime($sub_start);
        $now = new DateTime();
        $total_due = 0;

        if ($sub_type === 'monthly') {
            $interval = $start->diff($now);
            $months = ($interval->y * 12) + $interval->m + 1;
            $total_due = $months * $sub_amount;
        } else {
            $years = (int) date('Y') - (int) $start->format('Y') + 1;
            $total_due = $years * $sub_amount;
        }

        $balance_due = $total_due - $total_paid;
    }
}

// 1. Financial Summary
$all_time_total = $db->query("SELECT SUM(amount) FROM subscriptions")->fetchColumn() ?: 0;
$year_total = $db->query("SELECT SUM(amount) FROM subscriptions WHERE year = $year_filter")->fetchColumn() ?: 0;

// 2. Fetch Families for Due Calculation
$families = $db->query("SELECT id, name, anbiyam, subscription_type, subscription_amount, subscription_start_date, created_at FROM families ORDER BY name ASC")->fetchAll();
$family_dues = [];

foreach ($families as $f) {
    $paid = $db->prepare("SELECT SUM(amount) FROM subscriptions WHERE family_id = ?");
    $paid->execute([$f['id']]);
    $total_paid = $paid->fetchColumn() ?: 0;

    $sub_type = $f['subscription_type'] ?? 'yearly';
    $sub_amount = $f['subscription_amount'] ?? ANNUAL_SUBSCRIPTION_AMOUNT;
    $sub_start = $f['subscription_start_date'] ?? $f['created_at'];

    $start = new DateTime($sub_start);
    $now = new DateTime();
    $total_due = 0;

    if ($sub_type === 'monthly') {
        $interval = $start->diff($now);
        $months = ($interval->y * 12) + $interval->m + 1;
        $total_due = $months * $sub_amount;
    } else {
        $years = (int) date('Y') - (int) $start->format('Y') + 1;
        $total_due = $years * $sub_amount;
    }

    $balance = $total_due - $total_paid;
    if ($balance > 0 || isset($_GET['show_all'])) {
        $family_dues[] = [
            'id' => $f['id'],
            'name' => $f['name'],
            'anbiyam' => $f['anbiyam'],
            'total_due' => $total_due,
            'total_paid' => $total_paid,
            'balance' => $balance
        ];
    }
}

// 3. Recent Payments for the year
$sql = "SELECT s.*, f.name as family_name, f.anbiyam 
        FROM subscriptions s 
        JOIN families f ON s.family_id = f.id 
        WHERE s.year = ? 
        ORDER BY s.paid_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$year_filter]);
$recent_payments = $stmt->fetchAll();
?>

<?php if ($family_id_filter && isset($selected_family)): ?>
    <!-- Family-Specific Subscription View -->
    <div class="card no-print" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0;">💰 Subscription Details: <?php echo htmlspecialchars($selected_family['name']); ?>
                </h2>
                <p style="color: var(--secondary); margin: 0.5rem 0 0 0;">Anbiyam:
                    <?php echo htmlspecialchars($selected_family['anbiyam']); ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
                <a href="subscriptions.php" class="btn btn-secondary">← Back to All</a>
            </div>
        </div>
    </div>

    <!-- Family Information Card -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="margin-top: 0; color: var(--primary);">Family Information</h3>
        <div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <div>
                <p style="margin: 0.5rem 0;"><strong>Family ID:</strong>
                    <?php echo htmlspecialchars($selected_family['family_code'] ?? 'N/A'); ?></p>
                <p style="margin: 0.5rem 0;"><strong>Family Name:</strong>
                    <?php echo htmlspecialchars($selected_family['name']); ?></p>
                <p style="margin: 0.5rem 0;"><strong>Anbiyam:</strong>
                    <?php echo htmlspecialchars($selected_family['anbiyam']); ?></p>
                <p style="margin: 0.5rem 0;"><strong>Sub. Start Date:</strong>
                    <?php echo !empty($selected_family['subscription_start_date']) ? date('d-m-Y', strtotime($selected_family['subscription_start_date'])) : '-'; ?>
                </p>
            </div>
            <div>
                <p style="margin: 0.5rem 0;"><strong>Husband:</strong> <?php echo htmlspecialchars($husband_name); ?></p>
                <p style="margin: 0.5rem 0;"><strong>Wife:</strong> <?php echo htmlspecialchars($wife_name); ?></p>
                <p style="margin: 0.5rem 0;"><strong>Phone:</strong>
                    <?php echo htmlspecialchars($selected_family['phone'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>

    <!-- Financial Summary Card -->
    <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div class="card"
            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
            <p style="margin:0; opacity: 0.9; font-size: 0.8rem; text-transform: uppercase; font-weight: bold;">Total Paid
            </p>
            <h2 style="margin: 0.5rem 0; font-size: 1.8rem;"><?php echo CURRENCY . number_format($total_paid, 2); ?></h2>
        </div>
        <div class="card"
            style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none;">
            <p style="margin:0; opacity: 0.9; font-size: 0.8rem; text-transform: uppercase; font-weight: bold;">Total Due
            </p>
            <h2 style="margin: 0.5rem 0; font-size: 1.8rem;"><?php echo CURRENCY . number_format($total_due, 2); ?></h2>
        </div>
        <div class="card"
            style="background: linear-gradient(135deg, <?php echo $balance_due > 0 ? '#ef4444 0%, #dc2626' : '#10b981 0%, #059669'; ?> 100%); color: white; border: none;">
            <p style="margin:0; opacity: 0.9; font-size: 0.8rem; text-transform: uppercase; font-weight: bold;">Balance</p>
            <h2 style="margin: 0.5rem 0; font-size: 1.8rem;"><?php echo CURRENCY . number_format($balance_due, 2); ?></h2>
        </div>
    </div>

    <div class="card">
        <h3>Payment History</h3>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Date Paid</th>
                    <th>Year</th>
                    <th>Amount</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($family_payments)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--secondary);">No payments recorded</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($family_payments as $payment): ?>
                        <tr>
                            <td><?php echo format_date($payment['paid_date']); ?></td>
                            <td><?php echo $payment['year']; ?></td>
                            <td style="font-weight: 700; color: #059669;">
                                <?php echo CURRENCY . number_format($payment['amount'], 2); ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="report_subscription_receipt.php?id=<?php echo $payment['id']; ?>" target="_blank"
                                    class="btn btn-secondary" style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">📄 Receipt</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include 'includes/footer.php';
    exit; ?>
<?php endif; ?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin:0;">💰 Subscriptions & Financials</h1>
            <p style="color: var(--secondary); margin:0;">Detailed overview of parish collections and outstanding dues.
            </p>
        </div>
        <div class="no-print" style="display: flex; align-items: center; gap: 0.75rem;">
            <form method="GET"
                style="display: flex; gap: 0.5rem; align-items: center; border: 1px solid var(--border); padding: 0.25rem; border-radius: 12px; background: white;">
                <input type="text" name="search" placeholder="Search Family ID / Name..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="border: none; background: transparent; width: 220px; padding: 0.5rem; margin: 0; outline: none;">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">🔍</button>
            </form>
            <button onclick="startScanner()" class="btn btn-secondary"
                style="background: #e0e7ff; color: var(--primary);">📷 Scan QR</button>
            <a href="report_subscriptions.php" target="_blank" class="btn btn-primary" style="background: #1e293b;">📄
                Full Report</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print View</button>
        </div>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
    <div class="card"
        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
        <p style="margin:0; opacity: 0.9; font-size: 0.8rem; text-transform: uppercase; font-weight: bold;">Collected
            All Time</p>
        <h2 style="margin: 0.5rem 0; font-size: 2rem;"><?php echo CURRENCY . number_format($all_time_total, 2); ?></h2>
    </div>
    <div class="card"
        style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none;">
        <p style="margin:0; opacity: 0.9; font-size: 0.8rem; text-transform: uppercase; font-weight: bold;">Collected In
            <?php echo $year_filter; ?>
        </p>
        <h2 style="margin: 0.5rem 0; font-size: 2rem;"><?php echo CURRENCY . number_format($year_total, 2); ?></h2>
    </div>
    <?php
    $total_outstanding = array_sum(array_column($family_dues, 'balance'));
    ?>
    <div class="card"
        style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;">
        <p style="margin:0; opacity: 0.9; font-size: 0.8rem; text-transform: uppercase; font-weight: bold;">Total
            Outstanding Due</p>
        <h2 style="margin: 0.5rem 0; font-size: 2rem;"><?php echo CURRENCY . number_format($total_outstanding, 2); ?>
        </h2>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1.5fr 1fr;">
    <!-- Dues Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Outstanding Dues (By Family)</h3>
            <a href="?show_all=1" style="font-size: 0.8rem; color: var(--primary);">Show All Families</a>
        </div>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Family</th>
                    <th>Anbiyam</th>
                    <th>Total Paid</th>
                    <th>Balance Due</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($family_dues as $fd): ?>
                    <tr>
                        <td><strong><a href="family_view.php?id=<?php echo $fd['id']; ?>"
                                    style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($fd['name']); ?></a></strong>
                        </td>
                        <td><?php echo htmlspecialchars($fd['anbiyam']); ?></td>
                        <td style="color: #059669; font-weight: 600;">
                            <?php echo CURRENCY . number_format($fd['total_paid']); ?>
                        </td>
                        <td style="color: #dc2626; font-weight: 700; background: #fee2e2; border-radius: 4px;">
                            <?php echo CURRENCY . number_format($fd['balance']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Payments -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Payments in <?php echo $year_filter; ?></h3>
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <input type="number" name="year" value="<?php echo $year_filter; ?>" style="width: 70px; padding: 4px;">
                <button type="submit" class="btn btn-secondary" style="padding: 4px 8px;">Go</button>
            </form>
        </div>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($recent_payments as $rp): ?>
                <li
                    style="padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($rp['family_name']); ?>
                        </div>
                        <small style="color: var(--secondary);"><?php echo format_date($rp['paid_date']); ?> | Year
                            <?php echo $rp['year']; ?></small>
                    </div>
                    <div style="font-weight: 700; color: #059669;"><?php echo CURRENCY . number_format($rp['amount']); ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<style>
    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        text-align: left;
        padding: 12px;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .modern-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }
</style>

<!-- Scan Modal -->
<div id="scan_modal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; padding:1.5rem; border-radius:15px; width:90%; max-width:500px; text-align:center;">
        <h3>📷 Scan Family QR</h3>
        <p style="font-size: 0.85rem; color: var(--secondary); margin-bottom: 1rem;">Point your camera at the Family QR
            code</p>
        <div id="reader"
            style="width:100%; min-height:300px; background:#f1f5f9; margin-bottom:1rem; border-radius: 12px; overflow: hidden;">
        </div>
        <button onclick="closeScanner()" class="btn btn-secondary" style="width:100%;">Close Scanner</button>
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
        // Expecting ID, Family Code, or a URL
        let searchVal = decodedText;

        try {
            // Check if it's a URL
            if (decodedText.startsWith('http')) {
                const url = new URL(decodedText);
                const id = url.searchParams.get("id");
                if (id) {
                    window.location.href = `subscriptions.php?family_id=${id}`;
                    return;
                }
            }
        } catch (e) { }

        // If not a URL redirect, search for the text (ID or Code)
        window.location.href = `subscriptions.php?search=${encodeURIComponent(searchVal)}`;
        closeScanner();
    }

    function onScanFailure(error) {
        // usually better to ignore and keep scanning.
    }

    function closeScanner() {
        document.getElementById('scan_modal').style.display = 'none';
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
            }).catch(error => {
                console.error("Failed to clear html5QrcodeScanner", error);
                html5QrcodeScanner = null;
            });
        }
    }
</script>

<style>
    @media print {
        .card {
            box-shadow: none !important;
            border: 1px solid #eee !important;
            margin-bottom: 1rem !important;
        }

        .grid {
            display: block !important;
        }

        .grid>.card {
            margin-bottom: 1rem !important;
            width: 100% !important;
        }

        body {
            background: white !important;
        }

        .modern-table th {
            background: #f1f5f9 !important;
            color: black !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>