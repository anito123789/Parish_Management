<?php
require_once 'db.php';
include 'includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Parishioner ID missing.";
    exit;
}

// Fetch Parishioner basic info
$stmt = $db->prepare("SELECT p.*, f.name as family_name, f.anbiyam, f.address, f.phone as family_phone 
                      FROM parishioners p 
                      LEFT JOIN families f ON p.family_id = f.id 
                      WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    echo "Parishioner not found.";
    exit;
}

// Fetch Sacramental Data
$baptism = $db->query("SELECT * FROM baptisms WHERE parishioner_id = $id")->fetch() ?: [];
$communion = $db->query("SELECT * FROM first_communions WHERE parishioner_id = $id")->fetch() ?: [];
$confirmation = $db->query("SELECT * FROM confirmations WHERE parishioner_id = $id")->fetch() ?: [];
$marriage = $db->query("SELECT * FROM marriages WHERE parishioner_id = $id")->fetch() ?: [];
$death = $db->query("SELECT * FROM deaths WHERE parishioner_id = $id")->fetch() ?: [];

$age = $p['dob'] ? date_diff(date_create($p['dob']), date_create('today'))->y : 'N/A';
?>

<!-- Print Only Header -->
<div class="print-header"
    style="display: none; text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #333; padding-bottom: 1rem;">
    <h1 style="margin: 0; font-size: 24pt;"><?php echo htmlspecialchars($sidebar_profile['church_name']); ?></h1>
    <p style="margin: 5px 0; font-size: 14pt;"><?php echo htmlspecialchars($sidebar_profile['place']); ?></p>
    <h2 style="margin: 10px 0 0 0; font-size: 18pt; text-transform: uppercase; color: #555;">Parishioner Profile</h2>
</div>

<div class="no-print" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="family_view.php?id=<?php echo $p['family_id']; ?>" class="btn btn-secondary"
            style="margin-bottom: 1rem;">&larr; Back to Family</a>
        <h1 style="margin:0;">👤 Parishioner Profile</h1>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="parishioner_form.php?id=<?php echo $p['id']; ?>" class="btn btn-primary">Edit Details</a>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Profile</button>
    </div>
</div>

<div class="profile-container grid" style="grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- LEFT COLUMN: Personal Info -->
    <div class="card profile-sidebar" style="text-align: center;">
        <div style="margin-bottom: 1.5rem;">
            <?php if ($p['image']): ?>
                <img src="<?php echo htmlspecialchars($p['image']); ?>" class="profile-img"
                    style="width: 180px; height: 180px; border-radius: 20px; object-fit: cover; border: 4px solid white; box-shadow: var(--shadow);">
            <?php else: ?>
                <div class="profile-placeholder"
                    style="width: 180px; height: 180px; border-radius: 20px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 4rem; margin: auto; border: 4px solid white; box-shadow: var(--shadow);">
                    👤</div>
            <?php endif; ?>
        </div>

        <h2 style="margin: 0; color: var(--text-main);">
            <?php echo htmlspecialchars($p['name']); ?>
        </h2>
        <p style="margin: 0.5rem 0; color: var(--secondary); font-weight: 500;">
            <?php echo htmlspecialchars($p['gender']); ?> | Age:
            <?php echo $age; ?>
        </p>

        <?php if ($p['is_deceased']): ?>
            <span class="status-badge status-deceased"
                style="margin-top: 0.5rem; font-size: 0.9rem; padding: 0.5rem 1rem;">🕯️ Deceased</span>
        <?php else: ?>
            <span class="status-badge status-active" style="margin-top: 0.5rem; font-size: 0.9rem; padding: 0.5rem 1rem;">✓
                Active Parishioner</span>
        <?php endif; ?>

        <div class="sidebar-details"
            style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 1.5rem; text-align: left;">
            <div style="margin-bottom: 1rem;">
                <label
                    style="color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Family</label>
                <div style="font-weight: 600;">
                    <a href="family_view.php?id=<?php echo $p['family_id']; ?>" class="no-print-link"
                        style="text-decoration:none; color: var(--primary);">
                        <?php echo htmlspecialchars($p['family_name']); ?>
                    </a>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label
                    style="color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Anbiyam</label>
                <div style="font-weight: 600;">
                    <?php echo htmlspecialchars($p['anbiyam'] ?: 'N/A'); ?>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label
                    style="color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Relation</label>
                <div style="font-weight: 600;">
                    <?php echo htmlspecialchars($p['relationship'] ?: 'N/A'); ?>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label
                    style="color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; font-weight: bold;">Pious
                    Association</label>
                <div style="font-weight: 600; color: var(--primary);">
                    <?php echo htmlspecialchars($p['pious_association'] ?: 'None'); ?>
                </div>
            </div>

            <!-- Generate Letters Section -->
            <div class="no-print" style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 1.5rem;">
                <label
                    style="color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 0.5rem;">📄
                    Official Letters</label>
                <a href="recommendation_letter.php?parishioner_id=<?php echo $p['id']; ?>&type=recommendation"
                    class="btn btn-secondary"
                    style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start; text-align: left; font-size: 0.8rem; padding: 0.5rem;">📜
                    Recommendation</a>
                <a href="recommendation_letter.php?parishioner_id=<?php echo $p['id']; ?>&type=godparent"
                    class="btn btn-secondary"
                    style="width: 100%; justify-content: flex-start; text-align: left; font-size: 0.8rem; padding: 0.5rem;">📜
                    Godparent Suitability</a>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Detailed Info & Sacraments -->
    <div class="profile-main">
        <!-- Personal Details -->
        <div class="card mb-1">
            <h3 class="section-title"
                style="margin-top:0; color: var(--primary); border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem;">
                Personal Details</h3>
            <div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div>
                    <label class="detail-label">Date of Birth</label>
                    <div class="detail-value"><?php echo format_date($p['dob']); ?></div>
                </div>
                <div>
                    <label class="detail-label">Occupation</label>
                    <div class="detail-value"><?php echo htmlspecialchars($p['occupation'] ?: 'Not Specified'); ?></div>
                </div>
                <div>
                    <label class="detail-label">Education</label>
                    <div class="detail-value"><?php echo htmlspecialchars($p['education'] ?: 'Not Specified'); ?></div>
                </div>
                <div>
                    <label class="detail-label">Contact Info</label>
                    <div class="detail-value"><?php echo htmlspecialchars($p['family_phone'] ?: 'N/A'); ?></div>
                </div>
                <div style="grid-column: span 2;">
                    <label class="detail-label">Address</label>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($p['address'])); ?></div>
                </div>
            </div>
        </div>

        <!-- LINEAGE -->
        <div class="card mb-1">
            <h3 class="section-title"
                style="margin-top:0; color: #10b981; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem;">Lineage
            </h3>
            <div class="grid" style="grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div>
                    <label class="detail-label">Father's Name</label>
                    <div class="detail-value"><?php echo htmlspecialchars($p['father_name'] ?: 'Not Specified'); ?>
                    </div>
                </div>
                <div>
                    <label class="detail-label">Mother's Name</label>
                    <div class="detail-value"><?php echo htmlspecialchars($p['mother_name'] ?: 'Not Specified'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SACRAMENTS -->
        <div class="card mb-1 sac-card">
            <h3 class="section-title"
                style="margin-top:0; color: #f59e0b; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.5rem;">
                Sacramental Records</h3>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                <!-- Baptism -->
                <div class="sac-item">
                    <strong>🕊️ Baptism</strong>
                    <div class="sac-date">
                        <?php echo $p['baptism_date'] ? format_date($p['baptism_date']) : 'Not Received'; ?>
                    </div>
                </div>

                <!-- Communion -->
                <div class="sac-item">
                    <strong>🍷 First Holy Communion</strong>
                    <div class="sac-date">
                        <?php echo $p['communion_date'] ? format_date($p['communion_date']) : 'Not Received'; ?>
                    </div>
                </div>

                <!-- Confirmation -->
                <div class="sac-item">
                    <strong>🔥 Confirmation</strong>
                    <div class="sac-date">
                        <?php echo $p['confirmation_date'] ? format_date($p['confirmation_date']) : 'Not Received'; ?>
                    </div>
                </div>

                <!-- Marriage -->
                <div class="sac-item">
                    <strong>💍 Holy Matrimony</strong>
                    <div class="sac-date">
                        <?php echo $p['marriage_date'] ? format_date($p['marriage_date']) : 'Not Received'; ?>
                    </div>
                </div>
            </div>

            <?php if ($p['is_deceased']): ?>
                <div class="sac-item"
                    style="margin-top: 1rem; background: #fff1f2; border: 1px solid #fecaca; grid-column: span 2;">
                    <strong style="color: #be123c;">⚰️ Death Record</strong>
                    <div class="sac-date" style="color: #be123c;"><?php echo format_date($p['death_date']); ?></div>
                    <div style="font-size: 0.8rem; color: #be123c;">Cause:
                        <?php echo htmlspecialchars($death['cause'] ?: 'N/A'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .detail-label {
        color: var(--secondary);
        font-size: 0.8rem;
        display: block;
        margin-bottom: 2px;
    }

    .detail-value {
        font-weight: 600;
    }

    .mb-1 {
        margin-bottom: 1.5rem;
    }

    .sac-item {
        padding: 0.75rem;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
        background: #fdfdfd;
    }

    .sac-date {
        color: var(--secondary);
        font-size: 0.85rem;
        font-weight: 500;
    }

    @media print {
        @page {
            size: A4;
            margin: 15mm;
        }

        .no-print,
        .sidebar,
        .btn,
        header,
        .top-bar {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            border: none !important;
        }

        .content-wrapper {
            padding: 0 !important;
            background: white !important;
        }

        body {
            background: white !important;
            -webkit-print-color-adjust: exact;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }

        .print-header {
            display: block !important;
        }

        .profile-container {
            display: grid !important;
            grid-template-columns: 220px 1fr !important;
            gap: 1.5rem !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #e2e8f0 !important;
            padding: 1rem !important;
            margin-bottom: 1rem !important;
            page-break-inside: avoid;
            background: white !important;
        }

        .profile-img,
        .profile-placeholder {
            width: 140px !important;
            height: 140px !important;
            margin-bottom: 10px !important;
        }

        h2 {
            font-size: 16pt !important;
        }

        h3 {
            font-size: 12pt !important;
            margin-bottom: 10px !important;
            border-bottom: 1pt solid #000 !important;
        }

        .detail-label {
            font-size: 8pt !important;
            color: #666 !important;
        }

        .detail-value {
            font-size: 10pt !important;
        }

        .grid {
            gap: 1rem !important;
        }

        .sac-card .grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }

        .sac-item {
            padding: 5pt !important;
            border: 1pt solid #eee !important;
            font-size: 9pt !important;
        }

        .sidebar-details div {
            margin-bottom: 8pt !important;
        }

        .sidebar-details label {
            font-size: 7pt !important;
        }

        .no-print-link {
            color: black !important;
            text-decoration: none !important;
            pointer-events: none;
        }

        /* Force single page if possible */
        .profile-main .card:last-child {
            margin-bottom: 0 !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>