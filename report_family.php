<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
if (!$id)
    exit("Family ID Missing");

$f_stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
$f_stmt->execute([$id]);
$family = $f_stmt->fetch();

$m_stmt = $db->prepare("SELECT * FROM parishioners WHERE family_id = ? ORDER BY dob ASC");
$m_stmt->execute([$id]);
$members = $m_stmt->fetchAll();

// Fetch Subscriptions
$s_stmt = $db->prepare("SELECT * FROM subscriptions WHERE family_id = ? ORDER BY paid_date DESC");
$s_stmt->execute([$id]);
$subscriptions = $s_stmt->fetchAll();

// Calculate Balance (Same as family_view but for report)
$total_paid = 0;
foreach ($subscriptions as $s)
    $total_paid += $s['amount'];
$sub_type = $family['subscription_type'] ?? 'yearly';
$sub_amount = $family['subscription_amount'] ?? ANNUAL_SUBSCRIPTION_AMOUNT;
$sub_start = $family['subscription_start_date'] ?? $family['created_at'];
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
$overall_balance = $total_due - $total_paid;

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch();
$c_name = $profile['church_name'] ?? 'Parish Church';
$c_place = $profile['place'] ?? 'City';
$c_diocese = $profile['diocese'] ?? 'Diocese';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Family Report - <?php echo htmlspecialchars($family['name']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()">🖨️ Print Report</button>

    <div class="header">
        <h1><?php echo htmlspecialchars($c_name); ?></h1>
        <p><?php echo htmlspecialchars($c_place); ?> | <?php echo htmlspecialchars($c_diocese); ?></p>
        <h2 style="margin-top: 20px;">Family Record</h2>
    </div>

    <div class="section">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 40%;">
                    <strong>Family Name:</strong> <?php echo htmlspecialchars($family['name']); ?><br>
                    <strong>Family ID:</strong> <?php echo htmlspecialchars($family['family_code'] ?? '-'); ?><br>
                    <strong>Anbiyam:</strong> <?php echo htmlspecialchars($family['anbiyam']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($family['phone']); ?>
                </td>
                <td style="border: none; width: 30%;">
                    <strong>Address:</strong><br>
                    <?php echo nl2br(htmlspecialchars($family['address'])); ?>
                </td>
                <td
                    style="border: none; width: 30%; background: #f9fafb; padding: 10px; border-radius: 5px; border: 1px solid #eee;">
                    <h4 style="margin: 0 0 5px 0; font-size: 14px;">Subscription Status</h4>
                    <strong>Start Date:</strong> <?php echo format_date($sub_start); ?><br>
                    <strong>Type:</strong> <?php echo ucfirst($sub_type); ?>
                    (₹<?php echo number_format($sub_amount); ?>)<br>
                    <strong>Total Paid:</strong> ₹<?php echo number_format($total_paid, 2); ?><br>
                    <strong style="color: <?php echo $overall_balance > 0 ? '#b91c1c' : '#047857'; ?>">
                        Balance: ₹<?php echo number_format($overall_balance, 2); ?>
                    </strong>
                </td>
            </tr>
        </table>
    </div>

    <h3>Members</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 60px;">Photo</th>
                <th>Name & Relation</th>
                <th>DOB</th>
                <th>Profile Details</th>
                <th>Baptism</th>
                <th>First Communion</th>
                <th>Confirmation</th>
                <th>Marriage</th>
                <th>Status </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td style="text-align: center;">
                        <?php if ($m['image']): ?>
                            <img src="<?php echo htmlspecialchars($m['image']); ?>"
                                style="width: 45px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                        <?php else: ?>
                            <div
                                style="width: 45px; height: 55px; background: #f3f4f6; border-radius: 4px; border: 1px solid #ddd; font-size: 8px; display:flex; align-items:center; justify-content:center;">
                                No Photo</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($m['name']); ?></strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($m['relationship']); ?></small>
                    </td>
                    <td style="white-space: nowrap;"><?php echo format_date($m['dob']); ?></td>
                    <td style="font-size: 0.85em;">
                        <?php if ($m['father_name']): ?>F:
                            <?php echo htmlspecialchars($m['father_name']); ?><br><?php endif; ?>
                        <?php if ($m['mother_name']): ?>M:
                            <?php echo htmlspecialchars($m['mother_name']); ?><br><?php endif; ?>
                        <?php if ($m['education']): ?>Edu:
                            <?php echo htmlspecialchars($m['education']); ?><br><?php endif; ?>
                        <?php if ($m['occupation']): ?>Work:
                            <?php echo htmlspecialchars($m['occupation']); ?><br><?php endif; ?>
                        <?php if ($m['pious_association']): ?>Pious:
                            <?php echo htmlspecialchars($m['pious_association']); ?>     <?php endif; ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php echo $m['baptism_date'] ? format_date($m['baptism_date']) : '-'; ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php echo $m['communion_date'] ? format_date($m['communion_date']) : '-'; ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php echo $m['confirmation_date'] ? format_date($m['confirmation_date']) : '-'; ?>
                    </td>
                    <td style="white-space: nowrap;">
                        <?php echo $m['marriage_date'] ? format_date($m['marriage_date']) : '-'; ?>
                    </td>
                    <td>
                        <?php if ($m['is_deceased']): ?>
                            <strong style="color: #b91c1c;">Deceased</strong><br>
                            <small><?php echo format_date($m['death_date']); ?></small>
                        <?php else: ?>
                            <span style="color: #047857;">Active</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right; font-size: 12px; color: #666;">
        <p>Generated on: <?php echo date('d/m/Y H:i'); ?></p>
    </div>
</body>

</html>