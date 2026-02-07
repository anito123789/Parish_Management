<?php
require_once 'db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Family ID missing.";
    exit;
}

// Fetch Family
$stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
$stmt->execute([$id]);
$family = $stmt->fetch();

if (!$family) {
    echo "Family not found.";
    exit;
}

// Fetch Parishioners (Members)
$stmt = $db->prepare("SELECT * FROM parishioners WHERE family_id = ? ORDER BY dob ASC");
$stmt->execute([$id]);
$members = $stmt->fetchAll();

// Fetch Subscriptions
$stmt = $db->prepare("SELECT * FROM subscriptions WHERE family_id = ? ORDER BY year DESC");
$stmt->execute([$id]);
$subscriptions = $stmt->fetchAll();

// Calc Paid
$total_paid = 0;
foreach ($subscriptions as $s)
    $total_paid += $s['amount'];
// This is overly simple, ideally calculate per year due.
?>

<div
    style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 2rem; border-radius: 16px; color: white; margin-bottom: 2rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
    <!-- Decorative Circle -->
    <div
        style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;">
    </div>

    <div
        style="position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <div
                style="background: rgba(255,255,255,0.1); width: 80px; height: 80px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.2);">
                🏠
            </div>
            <div>
                <nav style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.8rem; opacity: 0.8;">
                    <a href="families.php" style="color: white; text-decoration: none;">Families</a>
                    <span>/</span>
                    <span style="font-weight: 500;">Details</span>
                </nav>
                <h1 style="margin: 0; font-size: 2.25rem; font-weight: 800; letter-spacing: -0.025em;">
                    <?php echo htmlspecialchars($family['name']); ?>
                </h1>
                <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.95rem;">
                    <span
                        style="background: rgba(255,255,255,0.15); padding: 2px 10px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">
                        ID: <strong
                            style="font-family: monospace;"><?php echo htmlspecialchars($family['family_code'] ?? '-'); ?></strong>
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.3rem;">
                        📍 <?php echo htmlspecialchars($family['anbiyam']); ?> Anbiyam
                    </span>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <div style="text-align: center;">
                <img src="generate_qr.php?id=<?php echo $family['id']; ?>&preview=1" alt="Family QR"
                    style="width: 60px; height: 60px; border-radius: 8px; border: 2px solid white;">
                <br>
                <a href="generate_qr.php?id=<?php echo $family['id']; ?>"
                    download="QR_<?php echo $family['family_code']; ?>.png"
                    style="color: white; font-size: 0.7rem; text-decoration: underline; opacity: 0.8;">Download</a>
            </div>
            <a href="family_form.php?id=<?php echo $family['id']; ?>" class="btn"
                style="background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.25rem; border-radius: 10px; font-weight: 600; text-decoration: none; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                Edit Profile
            </a>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr;">

    <!-- Members Section -->
    <div>
        <div class="card" style="border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span
                        style="background: #e0e7ff; color: #4338ca; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">👥</span>
                    Family Members
                </h2>
                <a href="parishioner_form.php?family_id=<?php echo $family['id']; ?>" class="btn btn-primary"
                    style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;">+ Add Member</a>
            </div>

            <?php if (empty($members)): ?>
                <div
                    style="text-align: center; padding: 3rem; background: #f8fafc; border-radius: 12px; border: 2px dashed #e2e8f0;">
                    <p style="color: #64748b; margin: 0;">No members registered in this family yet.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($members as $m):
                        $dobObj = $m['dob'] ? date_create($m['dob']) : false;
                        $nowObj = date_create('today');
                        $age = ($dobObj && $nowObj) ? date_diff($dobObj, $nowObj)->y : '-';
                        ?>
                        <div
                            style="background: white; border: 1px solid #f1f5f9; border-radius: 12px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; <?php echo $m['is_deceased'] ? 'opacity: 0.7; background: #fcfcfc;' : 'box-shadow: 0 1px 3px rgba(0,0,0,0.05);'; ?>">
                            <div style="display: flex; gap: 1.25rem; align-items: center;">
                                <div style="position: relative;">
                                    <?php if ($m['image']): ?>
                                        <img src="<?php echo htmlspecialchars($m['image']); ?>"
                                            style="width: 56px; height: 56px; border-radius: 12px; object-fit: cover; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div
                                            style="width: 56px; height: 56px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                            👤</div>
                                    <?php endif; ?>
                                    <?php if ($m['is_deceased']): ?>
                                        <div
                                            style="position: absolute; bottom: -5px; right: -5px; background: #ef4444; color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; border: 2px solid white;">
                                            🕯️</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 1.1rem; color: #1e293b;">
                                        <a href="parishioner_view.php?id=<?php echo $m['id']; ?>"
                                            style="text-decoration: none; color: inherit;"><?php echo htmlspecialchars($m['name']); ?></a>
                                    </div>
                                    <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.25rem;">
                                        <span
                                            style="font-size: 0.85rem; color: #64748b; font-weight: 500;"><?php echo htmlspecialchars($m['relationship']); ?></span>
                                        <span style="width: 4px; height: 4px; background: #cbd5e1; border-radius: 50%;"></span>
                                        <span style="font-size: 0.85rem; color: #64748b;"><?php echo $age; ?> years</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 2rem; align-items: center;">
                                <!-- Certificates Shortcut -->
                                <div style="display: flex; gap: 0.75rem;">
                                    <?php if ($m['baptism_date']): ?>
                                        <a href="report_baptism.php?id=<?php echo $m['id']; ?>" target="_blank"
                                            title="Baptism Certificate"
                                            style="padding: 4px 10px; border-radius: 6px; background: #f0f7ff; text-decoration: none; font-size: 0.75rem; font-weight: 700; color: #1d4ed8;">Baptism</a>
                                    <?php endif; ?>
                                    <?php if ($m['communion_date']): ?>
                                        <a href="report_communion.php?id=<?php echo $m['id']; ?>" target="_blank"
                                            title="Communion Certificate"
                                            style="padding: 4px 10px; border-radius: 6px; background: #fffbeb; text-decoration: none; font-size: 0.75rem; font-weight: 700; color: #b45309;">Communion</a>
                                    <?php endif; ?>
                                    <?php if ($m['confirmation_date']): ?>
                                        <a href="report_confirmation.php?id=<?php echo $m['id']; ?>" target="_blank"
                                            title="Confirmation Certificate"
                                            style="padding: 4px 10px; border-radius: 6px; background: #fef2f2; text-decoration: none; font-size: 0.75rem; font-weight: 700; color: #dc2626;">Confirmation</a>
                                    <?php endif; ?>
                                    <?php if ($m['marriage_date']): ?>
                                        <a href="report_marriage.php?id=<?php echo $m['id']; ?>" target="_blank"
                                            title="Marriage Certificate"
                                            style="padding: 4px 10px; border-radius: 6px; background: #f0fdfa; text-decoration: none; font-size: 0.75rem; font-weight: 700; color: #0d9488;">Marriage</a>
                                    <?php endif; ?>
                                    <a href="report_banns.php?id=<?php echo $m['id']; ?>" target="_blank" title="Write Banns"
                                        style="padding: 4px 10px; border-radius: 6px; background: #fff7ed; text-decoration: none; font-size: 0.75rem; font-weight: 700; color: #9a3412;">Banns</a>
                                    <?php if ($m['is_deceased']): ?>
                                        <a href="report_death.php?id=<?php echo $m['id']; ?>" target="_blank"
                                            title="Death Certificate"
                                            style="padding: 4px 10px; border-radius: 6px; background: #1e293b; text-decoration: none; font-size: 0.75rem; font-weight: 700; color: #f8fafc;">Death</a>
                                    <?php endif; ?>
                                </div>

                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="parishioner_form.php?id=<?php echo $m['id']; ?>&family_id=<?php echo $family['id']; ?>"
                                        class="btn btn-secondary"
                                        style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem;">Edit</a>
                                    <a href="reports.php?parishioner_id=<?php echo $m['id']; ?>" class="btn btn-secondary"
                                        style="padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; border: 1px solid #e2e8f0;">Reports</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card no-print">
            <h2>Family Reports</h2>
            <div style="display:flex; flex-wrap: wrap; gap: 0.75rem;">
                <a href="report_family.php?id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-secondary">Summary</a>
                <a href="report_baptism.php?family_id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-secondary">Baptisms</a>
                <a href="report_communion.php?family_id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-secondary">Communions</a>
                <a href="report_confirmation.php?family_id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-secondary">Confirmations</a>
                <a href="report_marriage.php?family_id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-secondary">Marriages</a>
                <a href="report_death.php?family_id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-secondary" style="background: #f1f5f9; color: #475569;">Deaths</a>
                <a href="subscriptions.php?family_id=<?php echo $family['id']; ?>" target="_blank"
                    class="btn btn-primary" style="background: #1e293b; color: white;">Subscriptions Detils</a>
            </div>
        </div>
    </div>

    <!-- Sidebar: Info & Subscriptions -->
    <div>
        <div class="card">
            <h3>Contact Details</h3>
            <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($family['address'])); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($family['phone']); ?></p>
            <p><strong>Sub. Start Date:</strong>
                <?php echo !empty($family['subscription_start_date']) ? format_date($family['subscription_start_date']) : format_date($family['created_at']); ?>
            </p>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3>Subscriptions</h3>
                <a href="subscription_form.php?family_id=<?php echo $family['id']; ?>" class="btn btn-secondary"
                    style="font-size: 0.8rem;">+ Pay</a>
            </div>

            <?php
            // Calculate subscription details based on type
            $sub_type = $family['subscription_type'] ?? 'yearly';
            $sub_amount = $family['subscription_amount'] ?? ANNUAL_SUBSCRIPTION_AMOUNT;
            $sub_start = $family['subscription_start_date'] ?? $family['created_at'];

            // Calculate total paid and last payment
            $total_paid = 0;
            $last_payment = null;
            foreach ($subscriptions as $s) {
                $total_paid += $s['amount'];
                if (!$last_payment || $s['paid_date'] > $last_payment['paid_date']) {
                    $last_payment = $s;
                }
            }

            // Calculate total expected due since start date
            $start = new DateTime($sub_start);
            $now = new DateTime();

            $total_due = 0;
            if ($sub_type === 'monthly') {
                // Ensure we count the starting month
                $interval = $start->diff($now);
                $months = ($interval->y * 12) + $interval->m + 1;
                $total_due = $months * $sub_amount;
                $period_label = "since " . date('M Y', strtotime($sub_start));
            } else {
                $years = (int) date('Y') - (int) $start->format('Y') + 1;
                $total_due = $years * $sub_amount;
                $period_label = "since " . $start->format('Y');
            }

            $overall_balance = $total_due - $total_paid;
            ?>

            <div
                style="background: #e0e7ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <small><?php echo ucfirst($sub_type); ?> Rate: ₹<?php echo number_format($sub_amount); ?></small><br>
                <strong style="font-size: 1.25rem; color: <?php echo $overall_balance > 0 ? '#ef4444' : '#10b981'; ?>">
                    <?php echo $overall_balance > 0 ? 'Balance Due: ₹' . number_format($overall_balance) : 'All Paid ✓'; ?>
                </strong>
                <div style="font-size: 0.8rem; color: #4338ca; margin-top: 0.25rem;">Total Due
                    <?php echo $period_label; ?>
                </div>
            </div>

            <?php if ($last_payment): ?>
                <div
                    style="background: #f0fdf4; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; border-left: 3px solid #10b981;">
                    <small style="color: #166534; font-weight: 600;">Last Payment</small><br>
                    <strong style="color: #15803d;">₹<?php echo number_format($last_payment['amount'], 2); ?></strong>
                    <small style="color: #166534;"> on <?php echo format_date($last_payment['paid_date']); ?></small>
                </div>
            <?php endif; ?>

            <div style="background: #fef3c7; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                <small style="color: #92400e;">Total Paid (All Time)</small><br>
                <strong
                    style="color: #b45309; font-size: 1.1rem;">₹<?php echo number_format($total_paid, 2); ?></strong>
            </div>

            <h4 style="margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 0.9rem; color: var(--secondary);">Recent
                Payments</h4>
            <ul style="list-style: none; padding: 0;">
                <?php
                $recent_payments = array_slice($subscriptions, 0, 2);
                foreach ($recent_payments as $sub): ?>
                    <li
                        style="border-bottom: 1px solid #eee; padding: 0.5rem 0; display:flex; justify-content:space-between;">
                        <span><small><?php echo format_date($sub['paid_date']); ?></small><br>Year
                            <?php echo $sub['year']; ?></span>
                        <strong>₹<?php echo number_format($sub['amount'], 2); ?></strong>
                    </li>
                <?php endforeach; ?>
                <?php if (count($subscriptions) > 2): ?>
                    <li style="padding: 0.5rem 0; text-align: center;">
                        <small style="color: var(--secondary);">
                            <a href="subscriptions.php?family_id=<?php echo $family['id']; ?>"
                                style="color: var(--primary); text-decoration: none;">
                                View all <?php echo count($subscriptions); ?> payments →
                            </a>
                        </small>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>