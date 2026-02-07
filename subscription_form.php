<?php
require_once 'db.php';
include 'includes/header.php';

$family_id = $_GET['family_id'] ?? null;
if (!$family_id) {
    echo "Family ID required.";
    exit;
}

$f = $db->prepare("SELECT name FROM families WHERE id = ?");
$f->execute([$family_id]);
$family = $f->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $year = $_POST['year'];
    $date = $_POST['paid_date'];

    $stmt = $db->prepare("INSERT INTO subscriptions (family_id, amount, year, paid_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$family_id, $amount, $year, $date]);
    $payment_id = $db->lastInsertId();
    $stmt = null; // Explicitly clear statement

    // Redirect back to family view (User prefers to print from subscriptions history if needed)
    header("Location: family_view.php?id=$family_id");
    exit;
}
?>

<div class="card" style="max-width: 500px; margin: auto;">
    <h2>Add Subscription</h2>
    <p>For Family: <strong>
            <?php echo htmlspecialchars($family['name']); ?>
        </strong></p>

    <form method="POST">
        <div class="form-group">
            <label>Amount</label>
            <input type="number" step="0.01" name="amount" required>
        </div>
        <div class="form-group">
            <label>For Year</label>
            <input type="number" name="year" value="<?php echo date('Y'); ?>" required>
        </div>
        <div class="form-group">
            <label>Date Paid</label>
            <input type="date" name="paid_date" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div style="display:flex; gap:1rem; justify-content:flex-end;">
            <a href="family_view.php?id=<?php echo $family_id; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Record Payment</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>