<?php
require_once 'db.php';
include 'includes/header.php';

// Fetch pending submissions
$stmt = $db->query("SELECT * FROM staging_submissions WHERE status = 'pending' ORDER BY submission_date DESC");
$submissions = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Submitted PDF Data</h1>
            <!-- <a href="print_survey_template.php" target="_blank" class="btn btn-sm btn-info mt-2">
                🖨️ Download Printable Form (PDF)
            </a> -->
        </div>
        <span class="badge bg-primary">
            <?php echo count($submissions); ?> Pending
        </span>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="card shadow mb-4">
            <div class="card-body text-center py-5">
                <h3 class="text-gray-500">No pending submissions</h3>
                <p>Share the link via WhatsApp to collect data: <br>
                    <code><?php echo "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/public_survey.php"; ?></code>
                </p>
                <a href="public_survey.php" target="_blank" class="btn btn-primary mt-3">Open Form</a>
                <a href="print_survey_template.php" target="_blank" class="btn btn-primary mt-3">Print PDF Form</a>

            </div>
        </div>
    <?php else: ?>

        <div class="grid" style="display:grid; gap: 1.5rem;">
            <?php foreach ($submissions as $sub):
                $data = json_decode($sub['data'], true);
                $family = $data['family'];
                $members = $data['members'];
                ?>
                <div class="card shadow border-left-primary">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo htmlspecialchars($family['head_name']); ?>
                            <span class="text-muted small ml-2">(
                                <?php echo count($members); ?> Members)
                            </span>
                        </h6>
                        <div class="dropdown no-arrow">
                            <span class="small text-gray-500 mr-3">
                                <?php echo date('d M, h:i A', strtotime($sub['submission_date'])); ?>
                            </span>
                            <a href="import_submission.php?id=<?php echo $sub['id']; ?>" class="btn btn-success btn-sm"
                                onclick="return confirm('Strictly verify data before importing. Continue?');">
                                <i class="fas fa-file-import"></i> Import From PDF
                            </a>
                            <a href="reject_submission.php?id=<?php echo $sub['id']; ?>" class="btn btn-danger btn-sm ml-2"
                                onclick="return confirm('Delete this submission?');">
                                Reject
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Address:</strong><br>
                                <?php echo nl2br(htmlspecialchars($family['address'])); ?><br>
                                <strong>Phone:</strong>
                                <?php echo htmlspecialchars($family['phone']); ?><br>
                                <strong>Anbiyam:</strong>
                                <?php echo htmlspecialchars($family['anbiyam']); ?>
                            </div>
                            <div class="col-md-8">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Rel</th>
                                            <th>Gender</th>
                                            <th>DOB</th>
                                            <th>Sacraments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($members as $m): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($m['name']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($m['relationship']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($m['gender']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($m['dob']); ?>
                                                </td>
                                                <td>
                                                <td>
                                                    <?php
                                                    $sacs = [];
                                                    if (!empty($m['baptism_date'])) {
                                                        $info = "B: " . $m['baptism_date'];
                                                        if (!empty($m['b_godfather']))
                                                            $info .= " (GF:{$m['b_godfather']})";
                                                        $sacs[] = "<span title='" . htmlspecialchars(json_encode($m)) . "'>$info</span>";
                                                    }
                                                    if (!empty($m['communion_date']))
                                                        $sacs[] = 'Comm';
                                                    if (!empty($m['confirmation_date']))
                                                        $sacs[] = 'Conf';
                                                    if (!empty($m['marriage_date']))
                                                        $sacs[] = 'M';

                                                    if (!empty($m['is_deceased']))
                                                        $sacs[] = '<span class="text-danger">✝ Died</span>';

                                                    echo implode('<br>', $sacs);
                                                    ?>
                                                </td>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>