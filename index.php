<?php
require_once 'db.php';
include 'includes/header.php';

// Fetch Parish Profile for WhatsApp Templates
$profile = $db->query("SELECT * FROM parish_profile LIMIT 1")->fetch() ?: [];

// Helper to get signature
$sig = "\n\nWith Prayers,\n";
if (!empty($profile['vicar'])) {
    $sig .= "Fr. " . $profile['vicar'] . "\n(Parish Priest)\n";
}
if (!empty($profile['asst_vicar'])) {
    $sig .= "Fr. " . $profile['asst_vicar'] . "\n(Asst. Parish Priest)\n";
}
if (!empty($profile['place'])) {
    $sig .= $profile['place'];
}

$def_bday = "Dear [Name], the Parish of " . ($profile['church_name'] ?? 'our church') . " wishes you a very Happy Birthday! May God bless you with abundant joy and health. Have a wonderful day! 🎂🙏";
$msg_bday_tpl = (!empty($profile['msg_birthday']) ? $profile['msg_birthday'] : $def_bday) . $sig;

$def_mar = "Happy Marriage Anniversary to [Name]! Wishing you many more years of love and togetherness. May God's grace always be upon your family. 💍✨";
$msg_mar_tpl = (!empty($profile['msg_marriage']) ? $profile['msg_marriage'] : $def_mar) . $sig;

$def_death = "Remembering [Name] on [his/her] Death Anniversary today. The Parish community joins you in prayer for the departed soul. May [he/she] rest in eternal peace. 🕯️🙏";
$msg_death_tpl = (!empty($profile['msg_death']) ? $profile['msg_death'] : $def_death) . $sig;

// Stats
$family_count = $db->query("SELECT COUNT(*) FROM families")->fetchColumn();
$parishioner_count = $db->query("SELECT COUNT(*) FROM parishioners WHERE is_deceased = 0")->fetchColumn();

// Upcoming Birthdays (Next 7 days)
$sql_birthdays = "SELECT p.id, p.family_id, p.name, p.dob, p.whatsapp, strftime('%m-%d', p.dob) as bday_month_day, f.anbiyam 
                  FROM parishioners p
                  LEFT JOIN families f ON p.family_id = f.id
                  WHERE p.is_deceased = 0 AND p.dob IS NOT NULL AND 
                  strftime('%m-%d', p.dob) BETWEEN strftime('%m-%d', 'now') AND strftime('%m-%d', 'now', '+7 days')
                  ORDER BY bday_month_day ASC";
$birthdays = $db->query($sql_birthdays)->fetchAll();

// Upcoming Death Anniversaries (Next 7 days)
$sql_deaths = "SELECT p.id, p.family_id, p.name, p.death_date, p.whatsapp, strftime('%m-%d', p.death_date) as death_month_day, f.anbiyam 
                  FROM parishioners p
                  LEFT JOIN families f ON p.family_id = f.id
                  WHERE p.is_deceased = 1 AND p.death_date IS NOT NULL AND 
                  strftime('%m-%d', p.death_date) BETWEEN strftime('%m-%d', 'now') AND strftime('%m-%d', 'now', '+7 days')
                  ORDER BY death_month_day ASC";
$death_anns = $db->query($sql_deaths)->fetchAll();

// Upcoming Marriage Anniversaries (Next 7 days)
$sql_marriages = "SELECT p.id, p.family_id, p.name, p.marriage_date, p.whatsapp, strftime('%m-%d', p.marriage_date) as marriage_month_day, f.anbiyam 
                  FROM parishioners p
                  LEFT JOIN families f ON p.family_id = f.id
                  WHERE p.is_deceased = 0 AND p.marriage_date IS NOT NULL AND 
                  strftime('%m-%d', p.marriage_date) BETWEEN strftime('%m-%d', 'now') AND strftime('%m-%d', 'now', '+7 days')
                  ORDER BY marriage_month_day ASC";
$marriage_anns = $db->query($sql_marriages)->fetchAll();

// Events
$sql_events = "SELECT * FROM planner WHERE event_date >= date('now') ORDER BY event_date ASC LIMIT 5";
$events = $db->query($sql_events)->fetchAll();

// Events Today for Notification
$today_events = $db->query("SELECT * FROM planner WHERE date(event_date) = date('now')")->fetchAll();

// Sacrament Statistics for Selected Year
$selected_year = $_GET['year'] ?? date('Y');
$baptism_count = $db->query("SELECT COUNT(*) FROM parishioners WHERE strftime('%Y', baptism_date) = '$selected_year'")->fetchColumn();
$communion_count = $db->query("SELECT COUNT(*) FROM parishioners WHERE strftime('%Y', communion_date) = '$selected_year'")->fetchColumn();
$confirmation_count = $db->query("SELECT COUNT(*) FROM parishioners WHERE strftime('%Y', confirmation_date) = '$selected_year'")->fetchColumn();
$marriage_count = $db->query("SELECT COUNT(*) FROM parishioners WHERE strftime('%Y', marriage_date) = '$selected_year'")->fetchColumn();
$death_count = $db->query("SELECT COUNT(*) FROM parishioners WHERE strftime('%Y', death_date) = '$selected_year'")->fetchColumn();

// Get available years from database (years where any sacrament was recorded)
$years_query = "SELECT DISTINCT strftime('%Y', baptism_date) as year FROM parishioners WHERE baptism_date IS NOT NULL
                UNION
                SELECT DISTINCT strftime('%Y', communion_date) FROM parishioners WHERE communion_date IS NOT NULL
                UNION
                SELECT DISTINCT strftime('%Y', confirmation_date) FROM parishioners WHERE confirmation_date IS NOT NULL
                UNION
                SELECT DISTINCT strftime('%Y', marriage_date) FROM parishioners WHERE marriage_date IS NOT NULL
                UNION
                SELECT DISTINCT strftime('%Y', death_date) FROM parishioners WHERE death_date IS NOT NULL
                ORDER BY year DESC";
$available_years = $db->query($years_query)->fetchAll(PDO::FETCH_COLUMN);
?>

<?php if (!empty($today_events)): ?>
    <div class="card"
        style="background: #fffbeb; border-left: 5px solid #f59e0b; margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem;">
        <div style="font-size: 1.5rem;">🔔</div>
        <div>
            <h4 style="margin: 0; color: #92400e;">Today's Notifications</h4>
            <p style="margin: 0; font-size: 0.9rem; color: #b45309;">
                You have <?php echo count($today_events); ?> event(s) scheduled for today:
                <strong><?php echo htmlspecialchars(implode(', ', array_column($today_events, 'title'))); ?></strong>
                <a href="planner.php" style="margin-left: 10px; color: #92400e; font-weight: bold;">View Calendar &rarr;</a>
            </p>
        </div>
    </div>
<?php endif; ?>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="card"
        style="border-left: 5px solid var(--primary); background: linear-gradient(to right, #ffffff, #f0f7ff);">
        <p
            style="margin: 0; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">
            Total Families</p>
        <h2 style="margin: 0.5rem 0; font-size: 2rem; color: var(--primary);"><?php echo $family_count; ?></h2>
    </div>

    <div class="card" style="border-left: 5px solid #10b981; background: linear-gradient(to right, #ffffff, #f0fdf4);">
        <p
            style="margin: 0; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">
            Total Parishioners</p>
        <h2 style="margin: 0.5rem 0; font-size: 2rem; color: #059669;"><?php echo $parishioner_count; ?></h2>
    </div>
</div>

<!-- Sacrament Statistics for Current Year -->
<div style="margin-top: 1.5rem; background: white; padding: 1.5rem; border-radius: 16px; box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-main);">📊 Sacraments Statistics</h3>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <label for="year_select" style="font-size: 0.85rem; color: var(--secondary);">Year:</label>
            <select id="year_select" onchange="window.location.href='index.php?year='+this.value"
                style="padding: 0.5rem 1rem; border-radius: 8px; border: 2px solid #e2e8f0; background: white; font-weight: 600; color: var(--primary); cursor: pointer;">
                <?php
                // Add current year if not in list
                if (!in_array(date('Y'), $available_years)) {
                    $available_years[] = date('Y');
                    rsort($available_years);
                }
                foreach ($available_years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div style="text-align: center; padding: 0.75rem; background: #f0f7ff; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">Baptisms
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #1d4ed8;"><?php echo $baptism_count; ?></div>
        </div>
        <div style="text-align: center; padding: 0.75rem; background: #fffbeb; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">
                Communions</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #b45309;"><?php echo $communion_count; ?></div>
        </div>
        <div style="text-align: center; padding: 0.75rem; background: #fef2f2; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">
                Confirmations</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626;"><?php echo $confirmation_count; ?></div>
        </div>
        <div style="text-align: center; padding: 0.75rem; background: #f0fdfa; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">Marriages
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #0d9488;"><?php echo $marriage_count; ?></div>
        </div>
        <div style="text-align: center; padding: 0.75rem; background: #f8fafc; border-radius: 12px;">
            <div style="font-size: 0.7rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">Deaths
            </div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #475569;"><?php echo $death_count; ?></div>
        </div>
    </div>
</div>

<div class="grid dashboard-layout" style="margin-top: 1rem;">

    <div>
        <?php if (!empty($birthdays)): ?>
            <div class="card">
                <h3>🎉 Upcoming Birthdays (7 Days)</h3>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($birthdays as $b):
                        // Calculate which birthday (age turning)
                        $birth_year = date('Y', strtotime($b['dob']));
                        $current_year = date('Y');
                        $age = $current_year - $birth_year;

                        // Ordinal suffix (1st, 2nd, 3rd, etc.)
                        $suffix = 'th';
                        if ($age % 100 < 11 || $age % 100 > 13) {
                            switch ($age % 10) {
                                case 1:
                                    $suffix = 'st';
                                    break;
                                case 2:
                                    $suffix = 'nd';
                                    break;
                                case 3:
                                    $suffix = 'rd';
                                    break;
                            }
                        }
                        ?>
                        <li
                            style="padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                            <div>
                                <a href="parishioner_view.php?id=<?php echo $b['id']; ?>"
                                    style="text-decoration: none; color: inherit;">
                                    <strong><?php echo htmlspecialchars($b['name']); ?></strong>
                                </a>
                                <span style="color: var(--primary); font-size: 0.85rem; font-weight: 600;"> -
                                    <?php echo $age . $suffix; ?> Birthday</span><br>
                                <span style="font-size:0.75rem; color: var(--secondary);">Anbiyam:
                                    <?php echo htmlspecialchars($b['anbiyam'] ?? 'N/A'); ?> |
                                    <a href="parishioner_view.php?id=<?php echo $b['id']; ?>"
                                        style="color: var(--primary); font-size: 0.7rem;">View Details</a>
                                    <?php if ($b['whatsapp']): ?>
                                        <?php
                                        // Get parishioner gender for proper pronoun replacement
                                        $p_stmt = $db->prepare("SELECT gender FROM parishioners WHERE id = ?");
                                        $p_stmt->execute([$b['id']]);
                                        $gender = $p_stmt->fetchColumn() ?: 'male';
                                        $birthday_msg = generate_whatsapp_message($msg_bday_tpl, $b['name'], $gender, $profile['church_name'] ?? null);
                                        ?>
                                        | <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $b['whatsapp']); ?>?text=<?php echo urlencode($birthday_msg); ?>"
                                            target="_blank"
                                            style="color: #25d366; font-size: 0.7rem; font-weight: bold; text-decoration: none;">💬
                                            WhatsApp</a>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="status-badge"
                                style="background: #e0e7ff; color: var(--primary); align-self: center;"><?php echo date('d-m', strtotime($b['dob'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($marriage_anns)): ?>
            <div class="card">
                <h3>💍 Upcoming Marriage Anniversaries (7 Days)</h3>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($marriage_anns as $m):
                        // Calculate which anniversary
                        $marriage_year = date('Y', strtotime($m['marriage_date']));
                        $current_year = date('Y');
                        $years = $current_year - $marriage_year;

                        // Ordinal suffix
                        $suffix = 'th';
                        if ($years % 100 < 11 || $years % 100 > 13) {
                            switch ($years % 10) {
                                case 1:
                                    $suffix = 'st';
                                    break;
                                case 2:
                                    $suffix = 'nd';
                                    break;
                                case 3:
                                    $suffix = 'rd';
                                    break;
                            }
                        }
                        ?>
                        <li
                            style="padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                            <div>
                                <a href="parishioner_view.php?id=<?php echo $m['id']; ?>"
                                    style="text-decoration: none; color: inherit;">
                                    <strong><?php echo htmlspecialchars($m['name']); ?></strong>
                                </a>
                                <span style="color: #0d9488; font-size: 0.85rem; font-weight: 600;"> -
                                    <?php echo $years . $suffix; ?> Anniversary</span><br>
                                <span style="font-size:0.75rem; color: var(--secondary);">Anbiyam:
                                    <?php echo htmlspecialchars($m['anbiyam'] ?? 'N/A'); ?> |
                                    <a href="parishioner_view.php?id=<?php echo $m['id']; ?>"
                                        style="color: var(--primary); font-size: 0.7rem;">View Details</a>
                                    <?php if ($m['whatsapp']): ?>
                                        <?php
                                        // Get parishioner gender for proper pronoun replacement
                                        $p_stmt = $db->prepare("SELECT gender FROM parishioners WHERE id = ?");
                                        $p_stmt->execute([$m['id']]);
                                        $gender = $p_stmt->fetchColumn() ?: 'male';
                                        $marriage_msg = generate_whatsapp_message($msg_mar_tpl, $m['name'], $gender, $profile['church_name'] ?? null);
                                        ?>
                                        | <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $m['whatsapp']); ?>?text=<?php echo urlencode($marriage_msg); ?>"
                                            target="_blank"
                                            style="color: #25d366; font-size: 0.7rem; font-weight: bold; text-decoration: none;">💬
                                            WhatsApp</a>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="status-badge"
                                style="background: #fdf2f8; color: #db2777; align-self: center;"><?php echo date('d-m', strtotime($m['marriage_date'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($death_anns)): ?>
            <div class="card">
                <h3>🕯️ Upcoming Death Anniversaries (7 Days)</h3>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($death_anns as $d):
                        // Calculate which death anniversary
                        $death_year = date('Y', strtotime($d['death_date']));
                        $current_year = date('Y');
                        $years = $current_year - $death_year;

                        // Ordinal suffix
                        $suffix = 'th';
                        if ($years % 100 < 11 || $years % 100 > 13) {
                            switch ($years % 10) {
                                case 1:
                                    $suffix = 'st';
                                    break;
                                case 2:
                                    $suffix = 'nd';
                                    break;
                                case 3:
                                    $suffix = 'rd';
                                    break;
                            }
                        }
                        ?>
                        <li
                            style="padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                            <div>
                                <a href="parishioner_view.php?id=<?php echo $d['id']; ?>"
                                    style="text-decoration: none; color: inherit;">
                                    <strong><?php echo htmlspecialchars($d['name']); ?></strong>
                                </a>
                                <span style="color: #475569; font-size: 0.85rem; font-weight: 600;"> -
                                    <?php echo $years . $suffix; ?> Death Anniversary</span><br>
                                <span style="font-size:0.75rem; color: var(--secondary);">Anbiyam:
                                    <?php echo htmlspecialchars($d['anbiyam'] ?? 'N/A'); ?> |
                                    <a href="parishioner_view.php?id=<?php echo $d['id']; ?>"
                                        style="color: var(--primary); font-size: 0.7rem;">View Details</a>
                                    <?php if ($d['whatsapp']): ?>
                                        <?php
                                        // Get parishioner gender for proper pronoun replacement
                                        $p_stmt = $db->prepare("SELECT gender FROM parishioners WHERE id = ?");
                                        $p_stmt->execute([$d['id']]);
                                        $gender = $p_stmt->fetchColumn() ?: 'male';
                                        $death_msg = generate_whatsapp_message($msg_death_tpl, $d['name'], $gender, $profile['church_name'] ?? null);
                                        ?>
                                        | <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $d['whatsapp']); ?>?text=<?php echo urlencode($death_msg); ?>"
                                            target="_blank"
                                            style="color: #25d366; font-size: 0.7rem; font-weight: bold; text-decoration: none;">💬
                                            WhatsApp</a>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="status-badge"
                                style="background: #f1f5f9; color: #64748b; align-self: center;"><?php echo date('d-m', strtotime($d['death_date'])); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <h3>📅 Upcoming Events</h3>
            <?php if (empty($events)): ?>
                <p style="color: var(--secondary);">No events.</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($events as $e): ?>
                        <li
                            style="padding: 1rem 0; border-bottom: 1px solid #f1f5f9; display: flex; gap: 1rem; align-items: center;">
                            <div
                                style="background: var(--primary); color: white; padding: 0.5rem; border-radius: 8px; min-width: 60px; text-align: center;">
                                <div style="font-size: 0.7rem; text-transform: uppercase;">
                                    <?php echo date('M', strtotime($e['event_date'])); ?>
                                </div>
                                <div style="font-size: 1.25rem; font-weight: bold; line-height: 1;">
                                    <?php echo date('d', strtotime($e['event_date'])); ?>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($e['title']); ?>
                                </div>
                                <small style="color: #64748b; display: flex; align-items: center; gap: 0.3rem;">
                                    🕒 <?php echo date('h:i A', strtotime($e['event_date'])); ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a href="planner.php" class="btn btn-secondary" style="width: 100%; margin-top: 1rem;">View Planner</a>
        </div>

        <!-- Mobile Access Info (Right Side) -->
        <?php
        $ips = [];
        // Method 1: ipconfig
        @exec("ipconfig", $output);
        if (!empty($output)) {
            foreach ($output as $line) {
                if (preg_match('/IPv4 Address.*: ([\d\.]+)/', $line, $matches)) {
                    $ip = trim($matches[1]);
                    if ($ip != '127.0.0.1' && !in_array($ip, $ips))
                        $ips[] = $ip;
                }
            }
        }
        // Method 2: hostname -I (if on linux/WSL)
        if (empty($ips)) {
            @exec("hostname -I", $host_output);
            if (!empty($host_output)) {
                $found_ips = explode(' ', trim($host_output[0]));
                foreach ($found_ips as $ip) {
                    if (!empty($ip) && $ip != '127.0.0.1')
                        $ips[] = $ip;
                }
            }
        }
        // Method 3: Fallback
        if (empty($ips))
            $ips[] = @gethostbyname(gethostname());
        if (empty($ips) || $ips[0] == '127.0.0.1')
            $ips = ['[Your-IP-Address]'];
        ?>
        <div class="card"
            style="background: var(--primary-light); border: 1px dashed var(--primary); padding: 1.5rem; margin-top: 1rem; border-radius: 16px;">
            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                <div style="font-size: 1.5rem;">📱</div>
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-dark); font-size: 0.9rem;">Mobile Access</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        <p style="margin: 0; color: var(--text-main); font-size: 0.85rem;">
                            Connect your phone to the same Wi-Fi:
                        </p>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                            <?php foreach ($ips as $ip): ?>
                                <div
                                    style="background: white; padding: 10px; border-radius: 10px; border: 1.5px solid var(--primary); display: flex; flex-direction: column; align-items: center; gap: 0.5rem; flex: 1; min-width: 140px;">
                                    <span
                                        style="font-family: monospace; font-weight: 700; color: var(--primary); font-size: 1rem;">
                                        <?php echo $ip; ?>:8000
                                    </span>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode("http://$ip:8000"); ?>"
                                        alt="QR" style="width: 100px; height: 100px;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <?php include 'includes/footer.php'; ?>