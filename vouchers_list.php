<?php
require_once 'db.php';
include 'includes/header.php';

// Filters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($start_date) {
    $where[] = "voucher_date >= ?";
    $params[] = $start_date;
}
if ($end_date) {
    $where[] = "voucher_date <= ?";
    $params[] = $end_date;
}
if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}
if ($type) {
    $where[] = "voucher_type = ?";
    $params[] = $type;
}
if ($search) {
    $where[] = "(person_name LIKE ? OR voucher_no LIKE ? OR towards LIKE ? OR particulars LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query = "SELECT * FROM vouchers";
if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY voucher_date DESC, id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$vouchers = $stmt->fetchAll();

// Get Categories for filter
$categories = $db->query("SELECT DISTINCT category FROM vouchers WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Calculations
$total_receipts = 0;
$total_expenses = 0;
foreach ($vouchers as $v) {
    if ($v['voucher_type'] == 'receipt')
        $total_receipts += $v['amount'];
    else
        $total_expenses += $v['amount'];
}
?>

<div class="content-header">
    <h1>📋 Voucher History</h1>
    <div class="header-actions">
        <a href="voucher_receipt.php" class="btn btn-primary">➕ New Receipt</a>
        <a href="voucher_expense.php" class="btn btn-danger">➕ New Expense</a>
    </div>
</div>

<div class="card" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <div class="filter-grid">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Name, No, Reason...">
            </div>
            <div class="filter-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <option value="receipt" <?php echo $type == 'receipt' ? 'selected' : ''; ?>>Receipts</option>
                    <option value="expense" <?php echo $type == 'expense' ? 'selected' : ''; ?>>Expenses</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Category</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="filter-group" style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <button type="button" onclick="window.print()" class="btn btn-info">🖨️ Print List</button>
                <a href="vouchers_list.php" class="btn btn-secondary">🔄 Reset</a>
            </div>
        </div>
    </form>
</div>

<div class="print-header no-screen">
    <h2 style="margin: 0;"><?php echo htmlspecialchars($profile['church_name'] ?? 'Parish Voucher Report'); ?></h2>
    <p style="margin: 5px 0;">Voucher History Report (<?php echo date('d-m-Y'); ?>)</p>
    <?php if ($start_date || $end_date): ?>
        <p style="font-size: 0.9rem;">Period: <?php echo $start_date ? date('d-m-Y', strtotime($start_date)) : 'Start'; ?>
            to <?php echo $end_date ? date('d-m-Y', strtotime($end_date)) : 'End'; ?></p>
    <?php endif; ?>
</div>

<div class="stats-row" style="margin-bottom: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
    <div class="stat-card receipt">
        <h3>Total Receipts</h3>
        <p>₹
            <?php echo number_format($total_receipts, 2); ?>
        </p>
    </div>
    <div class="stat-card expense">
        <h3>Total Expenses</h3>
        <p>₹
            <?php echo number_format($total_expenses, 2); ?>
        </p>
    </div>
    <div class="stat-card balance">
        <h3>Net Balance</h3>
        <p>₹
            <?php echo number_format($total_receipts - $total_expenses, 2); ?>
        </p>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher No</th>
                <th>Type</th>
                <th>Person / Party</th>
                <th>Reason (Towards/Particulars)</th>
                <th>Category</th>
                <th>Amount</th>
                <th class="no-print">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vouchers)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No vouchers found matching your criteria.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vouchers as $v): ?>
                    <tr>
                        <td>
                            <?php echo date('d-m-Y', strtotime($v['voucher_date'])); ?>
                        </td>
                        <td><strong>
                                <?php echo htmlspecialchars($v['voucher_no'] ?? ''); ?>
                            </strong></td>
                        <td>
                            <span
                                class="badge <?php echo ($v['voucher_type'] ?? '') == 'receipt' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo strtoupper($v['voucher_type'] ?? ''); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($v['person_name'] ?? ''); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars(($v['towards'] ?? '') ?: ($v['particulars'] ?? '')); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($v['category'] ?? ''); ?>
                        </td>
                        <td
                            style="font-weight: bold; color: <?php echo $v['voucher_type'] == 'receipt' ? '#059669' : '#dc2626'; ?>;">
                            ₹
                            <?php echo number_format($v['amount'], 2); ?>
                        </td>
                        <td class="no-print">
                            <div style="display: flex; gap: 5px;">
                                <a href="voucher_<?php echo $v['voucher_type']; ?>.php?id=<?php echo $v['id']; ?>"
                                    class="btn btn-sm btn-info" title="View/Edit">👁️</a>
                                <a href="#"
                                    onclick="if(confirm('Delete this voucher?')) window.location.href='vouchers_list.php?delete=<?php echo $v['id']; ?>'"
                                    class="btn btn-sm btn-danger" title="Delete">🗑️</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Delete Logic
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM vouchers WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: vouchers_list.php?msg=Deleted");
    exit();
}
?>

<style>
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-size: 0.8rem;
        font-weight: bold;
        color: #64748b;
    }

    .filter-group input,
    .filter-group select {
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
    }

    .stat-card {
        padding: 20px;
        border-radius: 8px;
        color: white;
    }

    .stat-card.receipt {
        background: #10b981;
    }

    .stat-card.expense {
        background: #ef4444;
    }

    .stat-card.balance {
        background: #3b82f6;
    }

    .stat-card h3 {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .stat-card p {
        margin: 5px 0 0;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8rem;
    }

    .btn-info {
        background: #0ea5e9;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }

    @media screen {
        .no-screen {
            display: none !important;
        }
    }

    @media print {
        @page {
            size: A4;
            margin: 15mm;
        }

        .no-print,
        .filter-form,
        .content-header,
        .header-actions {
            display: none !important;
        }

        .no-screen {
            display: block !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #eee !important;
            padding: 0 !important;
        }

        .table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .table th,
        .table td {
            border: 1px solid #ddd !important;
            padding: 8px !important;
            font-size: 0.85rem !important;
        }

        .stats-row {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
            margin-bottom: 20px !important;
        }

        .stat-card {
            color: #000 !important;
            border: 1px solid #ddd !important;
            padding: 10px !important;
        }

        .stat-card h3 {
            color: #666 !important;
        }

        .stat-card p {
            color: #000 !important;
            font-size: 1.2rem !important;
        }

        .badge {
            border: 1px solid #ccc !important;
            background: none !important;
            color: #000 !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>