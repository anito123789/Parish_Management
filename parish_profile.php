<?php
require_once 'db.php';
include 'includes/header.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM parish_profile WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: parish_profile.php");
    exit;
}

$profiles = $db->query("SELECT * FROM parish_profile")->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="margin:0;">⛪ Parish Profile</h2>
            <p style="margin:0; color: var(--secondary);">Manage Church Details</p>
        </div>
        <!-- Only allow adding if empty, or allow multiple if desired. Usually one. -->
        <?php if (empty($profiles)): ?>
            <a href="parish_profile_form.php" class="btn btn-primary">+ Create Profile</a>
        <?php endif; ?>
    </div>

    <?php if (empty($profiles)): ?>
        <p class="alert alert-info">No parish profile set. Please create one.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($profiles as $p): ?>
                <div class="card" style="border-top: 4px solid var(--primary);">
                    <h3>
                        <?php echo htmlspecialchars($p['church_name']); ?>
                    </h3>
                    <p><strong>Place:</strong>
                        <?php echo htmlspecialchars($p['place']); ?>
                    </p>
                    <p><strong>Diocese:</strong>
                        <?php echo htmlspecialchars($p['diocese']); ?>
                    </p>
                    <p><strong>Vicar:</strong>
                        <?php echo htmlspecialchars($p['vicar']); ?>
                    </p>
                    <?php if ($p['church_image']): ?>
                        <div style="margin-top: 1rem;">
                            <img src="<?php echo htmlspecialchars($p['church_image']); ?>"
                                style="width: 100%; max-height: 200px; object-fit: contain; border-radius: 8px;">
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem; display: flex; gap: 0.5rem;">
                        <a href="parish_profile_form.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary">Edit</a>
                        <a href="?delete_id=<?php echo $p['id']; ?>" class="btn btn-danger"
                            onclick="return confirm('Are you sure?')">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>