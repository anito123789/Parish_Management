<?php
require_once 'db.php';
include 'includes/header.php';

$search = $_GET['search'] ?? '';

// Search Query (specifically for families/heads to write banns)
$list = [];
if (!empty($search)) {
    // Search for families by ID or Name
    $stmt = $db->prepare("SELECT f.* FROM families f WHERE f.id LIKE ? OR f.name LIKE ? ORDER BY f.id ASC LIMIT 20");
    $stmt->execute(["%$search%", "%$search%"]);
    $list = $stmt->fetchAll();
}
?>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1 style="color: var(--primary-dark); margin-bottom: 0.5rem;">📜 Write Banns</h1>
        <p style="color: var(--secondary);">Enter <strong>Family ID</strong> to prepare Marriage Banns</p>
    </div>

    <form method="GET"
        style="display: flex; gap: 1rem; margin-bottom: 2rem; background: #f1f5f9; padding: 1.5rem; border-radius: 15px; border: 1px solid #e2e8f0;">
        <input type="text" name="search" placeholder="Scan QR Code or Enter Family ID..." autofocus
            value="<?php echo htmlspecialchars($search); ?>"
            style="flex: 1; padding: 1rem; border-radius: 10px; border: 2px solid #cbd5e1; font-size: 1.1rem; outline: none;">
        <button type="submit" class="btn btn-primary"
            style="padding: 0 2rem; border-radius: 10px; font-weight: 700;">Find Family</button>
    </form>

    <?php if (!empty($search)): ?>
        <?php if (empty($list)): ?>
            <div
                style="text-align: center; padding: 3rem; background: #fff; border: 2px dashed #e2e8f0; border-radius: 15px; color: #64748b;">
                No family found matching "<strong>
                    <?php echo htmlspecialchars($search); ?>
                </strong>"
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($list as $family):
                    // Fetch ALL family members
                    $members = $db->query("SELECT * FROM parishioners WHERE family_id = {$family['id']} ORDER BY CASE WHEN relationship = 'Head' THEN 1 WHEN relationship = 'Spouse' THEN 2 ELSE 3 END")->fetchAll(PDO::FETCH_ASSOC);

                    // Identify key members for Banns (typically son/daughter)
                    $marriable_members = array_filter($members, function ($m) {
                        return stripos($m['relationship'], 'Son') !== false || stripos($m['relationship'], 'Daughter') !== false || $m['relationship'] == 'Member';
                    });
                    ?>
                    <div style="background: white; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin: 0; color: var(--primary-dark);"><?php echo htmlspecialchars($family['name']); ?>
                                </h3>
                                <div style="color: #64748b; font-size: 0.9rem;">
                                    Family ID: <strong><?php echo $family['id']; ?></strong> |
                                    Anbiyam: <?php echo htmlspecialchars($family['anbiyam']); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span
                                    style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem;">
                                    <?php echo count($members); ?> Members
                                </span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                            <?php foreach ($members as $member):
                                $is_marriable = (stripos($member['relationship'], 'Son') !== false || stripos($member['relationship'], 'Daughter') !== false);
                                ?>
                                <div
                                    style="border: 1px solid <?php echo $is_marriable ? '#cbd5e1' : '#f1f5f9'; ?>; padding: 10px; border-radius: 8px; background: <?php echo $is_marriable ? '#fff' : '#f8fafc'; ?>;">
                                    <div style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($member['name']); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b; display: flex; justify-content: space-between;">
                                        <span><?php echo htmlspecialchars($member['relationship']); ?></span>
                                        <span><?php echo date('d-m-Y', strtotime($member['dob'])); ?></span>
                                    </div>
                                    <div style="margin-top: 8px;">
                                        <a href="report_banns.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary"
                                            style="width: 100%; display: block; text-align: center; padding: 4px; font-size: 0.8rem;">
                                            Write Banns
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; color: #cbd5e1; border: 2px dashed #f1f5f9; border-radius: 15px;">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">⛪</div>
            <p style="font-size: 1.1rem;">Locate the family to generate their Marriage Banns</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>