<?php
require_once 'db.php';

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];

// Get all families with primary members
$families = $db->query("SELECT f.*, 
    (SELECT name FROM parishioners WHERE family_id = f.id AND (relationship = 'Husband' OR relationship = 'Head') LIMIT 1) as husband_name,
    (SELECT name FROM parishioners WHERE family_id = f.id AND (relationship = 'Wife' OR relationship = 'Spouse') LIMIT 1) as wife_name
    FROM families f ORDER BY f.name ASC")->fetchAll();

$report_data = [];
$total_all_paid = 0;
$total_all_due = 0;
$current_year = date('Y');

foreach ($families as $f) {
    // Calculate totals for family
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
        $years = (int) $current_year - (int) $start->format('Y') + 1;
        $total_due = $years * $sub_amount;
    }

    $balance = $total_due - $total_paid;

    $report_data[] = [
        'code' => $f['family_code'],
        'name' => $f['name'],
        'anbiyam' => $f['anbiyam'],
        'phone' => $f['phone'],
        'sub_start' => $sub_start,
        'husband' => $f['husband_name'],
        'wife' => $f['wife_name'],
        'paid' => $total_paid,
        'due' => $total_due,
        'balance' => $balance
    ];

    $total_all_paid += $total_paid;
    $total_all_due += $total_due;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Subscription Report -
        <?php echo date('Y'); ?>
    </title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: sans-serif;
            font-size: 9pt;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18pt;
        }

        .header p {
            margin: 5px 0;
            font-size: 10pt;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 4px;
            text-align: left;
        }

        th {
            background: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .balance-due {
            color: #dc2626;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .footer-sig {
            border-top: 1px solid #333;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            margin-top: 50px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #1e293b; color: white; border: none; border-radius: 6px; cursor: pointer;">Print
            PDF Report</button>
    </div>

    <div class="header">
        <h1>
            <?php echo htmlspecialchars($profile['church_name'] ?? 'Parish Name'); ?>
        </h1>
        <p>
            <?php echo htmlspecialchars($profile['place'] ?? ''); ?>,
            <?php echo htmlspecialchars($profile['diocese'] ?? ''); ?>
        </p>
        <h2 style="margin: 10px 0 0 0; color: #1e293b;">Complete Subscription & Dues Report (
            <?php echo date('Y'); ?>)
        </h2>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">ID Code</th>
                <th style="width: 15%;">Family / Head</th>
                <th style="width: 15%;">Husband & Wife</th>
                <th style="width: 8%;">Anbiyam</th>
                <th style="width: 8%;">Phone</th>
                <th style="width: 8%;">Start Date</th>
                <th style="width: 10%;" class="text-right">Total Due</th>
                <th style="width: 10%;" class="text-right">Total Paid</th>
                <th style="width: 10%;" class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($row['code'] ?? '-'); ?>
                    </td>
                    <td>
                        <span class="bold">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </span>
                    </td>
                    <td>
                        <small>H:
                            <?php echo htmlspecialchars($row['husband'] ?? '-'); ?><br>W:
                            <?php echo htmlspecialchars($row['wife'] ?? '-'); ?>
                        </small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['anbiyam']); ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['phone'] ?? '-'); ?>
                    </td>
                    <td>
                        <?php echo !empty($row['sub_start']) ? date('d-m-Y', strtotime($row['sub_start'])) : '-'; ?>
                    </td>
                    <td class="text-right">
                        <?php echo number_format($row['due'], 2); ?>
                    </td>
                    <td class="text-right">
                        <?php echo number_format($row['paid'], 2); ?>
                    </td>
                    <td class="text-right <?php echo $row['balance'] > 0 ? 'balance-due' : ''; ?>">
                        <?php echo number_format($row['balance'], 2); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="6" class="text-right">GRAND TOTAL:</td>
                <td class="text-right">
                    <?php echo number_format($total_all_due, 2); ?>
                </td>
                <td class="text-right">
                    <?php echo number_format($total_all_paid, 2); ?>
                </td>
                <td class="text-right">
                    <?php echo number_format($total_all_due - $total_all_paid, 2); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div>
            <p>Report Generated:
                <?php echo date('d-m-Y H:i'); ?>
            </p>
        </div>
        <div class="footer-sig">
            Parish Priest Signature
        </div>
    </div>
</body>

</html>