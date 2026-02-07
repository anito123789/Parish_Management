<?php
require_once 'db.php';
include 'includes/header.php';

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $db->prepare("DELETE FROM parishioners WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='parishioners.php';</script>";
    exit;
}

// Handle Delete All Request
if (isset($_POST['delete_all_parishioners'])) {
    $db->query("DELETE FROM parishioners");
    echo "<script>alert('All parishioners have been deleted.'); window.location.href='parishioners.php';</script>";
    exit;
}

$search = $_GET['search'] ?? '';
$per_page = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
if ($per_page <= 0)
    $per_page = 20;

// Pagination
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $per_page;

$where = "WHERE (p.name LIKE ? OR relationship LIKE ?)";
$params = ["%$search%", "%$search%"];

$count = $db->prepare("SELECT COUNT(*) FROM parishioners p $where");
$count->execute($params);
$total = $count->fetchColumn();
$pages = ceil($total / $per_page);

$sql = "SELECT p.*, f.name as family_name, f.anbiyam FROM parishioners p 
        LEFT JOIN families f ON p.family_id = f.id 
        $where 
        ORDER BY p.name ASC LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();
?>

<div class="card">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin:0;">Parishioners Directory</h2>
            <p style="margin:0; color: var(--secondary);">Manage individual members</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <form method="POST"
                onsubmit="return confirm('🚨 CRITICAL WARNING: You are about to PERMANENTLY delete ALL parishioners. This action cannot be undone.\n\nType DELETE ALL in the next step to confirm.');"
                style="display: inline-block;">
                <button type="button" onclick="confirmDeleteAllParishioners(this.form)" class="btn btn-danger"
                    style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;">🗑️ Delete All</button>
                <input type="hidden" name="delete_all_parishioners" value="1">
            </form>
            <a href="families.php" class="btn btn-primary" style="white-space: nowrap;">Add via Family List</a>
        </div>
    </div>

    <script>
        function confirmDeleteAllParishioners(form) {
            let confirmation = prompt("⚠️ Final Warning: Are you sure you want to delete EVERY parishioner?\n\nPlease type DELETE ALL to proceed:");
            if (confirmation === "DELETE ALL") {
                form.submit();
            } else {
                alert("Deletion cancelled. Match failed.");
            }
        }
    </script>

    <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; align-items: center; flex-wrap: wrap;">
        <input type="text" name="search" placeholder="Search name..." value="<?php echo htmlspecialchars($search); ?>"
            style="flex: 1; min-width: 200px;">

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
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Family</th>
                    <th>Age</th>
                    <th>Status</th>
                    <th>Education</th>
                    <th>Work</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $r):
                    $age = $r['dob'] ? date_diff(date_create($r['dob']), date_create('today'))->y : '-';
                    ?>
                    <tr style="<?php echo $r['is_deceased'] ? 'opacity: 0.6; background: #f8fafc;' : ''; ?>">
                        <td>
                            <strong>
                                <?php echo htmlspecialchars($r['name'] ?? ''); ?>
                            </strong><br>
                            <small style="color:var(--secondary);">
                                <?php echo htmlspecialchars($r['relationship'] ?? ''); ?>
                            </small>
                        </td>
                        <td>
                            <a href="family_view.php?id=<?php echo $r['family_id']; ?>">
                                <?php echo htmlspecialchars($r['family_name'] ?? '-'); ?>
                            </a>
                            <br>
                            <span style="font-size:0.8em; color:var(--secondary);">#
                                <?php echo htmlspecialchars($r['anbiyam'] ?? ''); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $age; ?>
                        </td>
                        <td>
                            <?php if ($r['is_deceased']): ?>
                                <span class="status-badge status-deceased">Deceased</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($r['education'] ?? '-'); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($r['occupation'] ?? '-'); ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="family_view.php?id=<?php echo $r['family_id']; ?>" class="btn btn-secondary"
                                style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto;">View</a>
                            <a href="parishioner_form.php?id=<?php echo $r['id']; ?>&family_id=<?php echo $r['family_id']; ?>"
                                class="btn btn-secondary"
                                style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto;">Edit</a>
                            <?php if ($r['is_deceased']): ?>
                                <a href="report_death.php?id=<?php echo $r['id']; ?>" target="_blank" class="btn btn-secondary"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background: #1e293b; color: #fff; width: auto;">Death</a>
                            <?php endif; ?>
                            <a href="parishioners.php?delete_id=<?php echo $r['id']; ?>" class="btn btn-danger"
                                style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; width: auto;"
                                onclick="return confirm('⚠️ Are you sure you want to delete <?php echo htmlspecialchars($r['name']); ?>?');">Delete</a>
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

<?php include 'includes/footer.php'; ?>