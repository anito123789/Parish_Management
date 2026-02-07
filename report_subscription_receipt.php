<?php
require_once 'db.php';

$payment_id = $_GET['id'] ?? null;
if (!$payment_id) {
    exit("Payment ID Missing");
}

// Fetch Payment Details
$stmt = $db->prepare("SELECT s.*, f.name as family_name, f.family_code, f.anbiyam, f.address 
                      FROM subscriptions s 
                      JOIN families f ON s.family_id = f.id 
                      WHERE s.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    exit("Payment record not found");
}

// Fetch Parish Profile
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];

/**
 * Helper to convert number to words (Simplified for Currency)
 */
function number_to_words($number)
{
    $hyphen = '-';
    $conjunction = ' and ';
    $separator = ', ';
    $negative = 'negative ';
    $decimal = ' and ';
    $dictionary = array(
        0 => 'Zero',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety',
        100 => 'Hundred',
        1000 => 'Thousand',
        1000000 => 'Million'
    );

    if (!is_numeric($number))
        return false;
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX)
        return false;

    if ($number < 0)
        return $negative . number_to_words(abs($number));

    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= number_to_words($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $string .= number_to_words((int) $fraction) . ' Cents';
    }

    return $string;
}

$amount_in_words = number_to_words($payment['amount']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Subscription Receipt -
        <?php echo $payment['id']; ?>
    </title>
    <style>
        @page {
            size: A5 landscape;
            margin: 5mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 5mm;
            background: #f8fafc;
            color: #1e293b;
            font-size: 10pt;
        }

        .receipt-container {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10mm;
            height: 125mm;
            /* Roughly A5 landscape height minus margins */
            position: relative;
            box-sizing: border-box;
            background-image:
                radial-gradient(circle at 100% 0%, rgba(226, 232, 240, 0.15) 0%, transparent 25%),
                radial-gradient(circle at 0% 100%, rgba(226, 232, 240, 0.15) 0%, transparent 25%);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #334155;
            padding-bottom: 5mm;
            margin-bottom: 5mm;
        }

        .church-info h1 {
            margin: 0;
            font-size: 16pt;
            color: #0f172a;
            text-transform: uppercase;
        }

        .church-info p {
            margin: 2px 0;
            font-size: 9pt;
            color: #64748b;
        }

        .receipt-title {
            text-align: right;
        }

        .receipt-title h2 {
            margin: 0;
            font-size: 18pt;
            color: #334155;
            letter-spacing: 2px;
        }

        .receipt-title p {
            margin: 2px 0;
            font-weight: bold;
            color: #475569;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5mm;
            margin-bottom: 5mm;
        }

        .detail-item {
            display: flex;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 2px;
        }

        .label {
            font-weight: bold;
            color: #64748b;
            width: 35mm;
            font-size: 9pt;
        }

        .value {
            flex: 1;
            font-weight: 600;
            color: #0f172a;
        }

        .payment-summary {
            margin-top: 8mm;
            background: #f1f5f9;
            padding: 5mm;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .amount-box {
            text-align: center;
        }

        .amount-label {
            font-size: 8pt;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }

        .amount-value {
            font-size: 20pt;
            font-weight: 800;
            color: #0f172a;
        }

        .words-area {
            flex: 1;
            margin-left: 10mm;
            font-style: italic;
            font-size: 10pt;
            color: #334155;
        }

        .footer {
            margin-top: 10mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .sig-block {
            text-align: center;
            width: 40mm;
        }

        .sig-line {
            border-top: 1px solid #334155;
            margin-top: 15mm;
            padding-top: 1mm;
            font-weight: bold;
            font-size: 9pt;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 60pt;
            color: rgba(0, 0, 0, 0.03);
            pointer-events: none;
            white-space: nowrap;
            font-weight: 900;
            text-transform: uppercase;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                border: 1px solid #ccc;
                box-shadow: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 10px; display: flex; gap: 10px; justify-content: center;">
        <button onclick="window.print()"
            style="padding: 8px 20px; background: #334155; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Print
            Receipt</button>
        <button onclick="window.history.back()"
            style="padding: 8px 20px; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer;">Go
            Back</button>
    </div>

    <div class="receipt-container">
        <div class="watermark">PAID</div>

        <div class="header">
            <div class="church-info">
                <h1>
                    <?php echo htmlspecialchars($profile['church_name'] ?? 'Parish Church'); ?>
                </h1>
                <p>
                    <?php echo htmlspecialchars($profile['place'] ?? ''); ?> |
                    <?php echo htmlspecialchars($profile['diocese'] ?? ''); ?> Diocese
                </p>
                <p>Phone:
                    <?php echo htmlspecialchars($profile['phone'] ?? '-'); ?>
                </p>
            </div>
            <div class="receipt-title">
                <h2>RECEIPT</h2>
                <p>No: #
                    <?php echo str_pad($payment['id'], 5, '0', STR_PAD_LEFT); ?>
                </p>
                <p>Date:
                    <?php echo date('d-m-Y', strtotime($payment['paid_date'])); ?>
                </p>
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-item">
                <span class="label">Received From:</span>
                <span class="value">
                    <?php echo htmlspecialchars($payment['family_name']); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="label">Family ID:</span>
                <span class="value">
                    <?php echo htmlspecialchars($payment['family_code'] ?? 'ID-' . $payment['family_id']); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="label">Description:</span>
                <span class="value">Parish Subscription for the Year
                    <?php echo $payment['year']; ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="label">Anbiyam:</span>
                <span class="value">
                    <?php echo htmlspecialchars($payment['anbiyam'] ?: 'N/A'); ?>
                </span>
            </div>
        </div>

        <div class="payment-summary">
            <div class="amount-box">
                <div class="amount-label">Amount Received</div>
                <div class="amount-value">₹
                    <?php echo number_format($payment['amount'], 2); ?>
                </div>
            </div>
            <div class="words-area">
                Rupees
                <?php echo $amount_in_words; ?> Only.
            </div>
        </div>

        <div class="footer">
            <div style="font-size: 8pt; color: #64748b;">
                Thank you for your generous contribution to the parish.
            </div>
            <div style="display: flex; gap: 2rem; align-items: flex-end;">
                <?php if ($profile['enable_seal'] && !empty($profile['seal_image'])): ?>
                    <div style="width: 80px; height: 80px;">
                        <img src="<?php echo htmlspecialchars($profile['seal_image']); ?>"
                            style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                <?php endif; ?>

                <div class="sig-block" style="flex: 1; text-align: right;">
                    <?php if ($profile['enable_signature'] && !empty($profile['signature_image'])): ?>
                        <div style="height: 50px; margin-bottom: 5px;">
                            <img src="<?php echo htmlspecialchars($profile['signature_image']); ?>"
                                style="max-height: 100%; object-fit: contain;">
                        </div>
                    <?php endif; ?>
                    <div class="sig-line">Parish Priest / Accountant</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>