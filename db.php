<?php
// db.php
require_once 'config.php';

try {
    $db = new PDO('sqlite:database/parish.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Solve "database is locked" errors
    $db->exec('PRAGMA busy_timeout = 10000');
    $db->exec('PRAGMA journal_mode = WAL');

    // Create tables
    // Note: SQLite ALTER TABLE is limited. We use if not exists for intial setup.
    // For updates on existing DB during dev, we check cols.

    $commands = [
        "CREATE TABLE IF NOT EXISTS families (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_code TEXT,
            name TEXT NOT NULL,
            head_name TEXT,
            spouse_name TEXT,
            head_image TEXT,
            spouse_image TEXT,
            address TEXT,
            anbiyam TEXT,
            substation TEXT,
            phone TEXT,
            subscription_type TEXT DEFAULT 'yearly',
            subscription_amount REAL DEFAULT 1200,
            subscription_start_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS parishioners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_id INTEGER,
            name TEXT NOT NULL,
            image TEXT,
            dob DATE,
            gender TEXT,
            relationship TEXT,
            education TEXT,
            pious_association TEXT,
            occupation TEXT,
            father_name TEXT,
            mother_name TEXT,
            baptism_date DATE,
            communion_date DATE,
            confirmation_date DATE,
            marriage_date DATE,
            death_date DATE,
            is_deceased INTEGER DEFAULT 0,
            whatsapp TEXT,
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_id INTEGER,
            amount REAL,
            year INTEGER,
            paid_date DATE,
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS planner (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            description TEXT,
            event_date DATETIME
        )",
        "CREATE TABLE IF NOT EXISTS baptisms (
            parishioner_id INTEGER PRIMARY KEY,
            minister TEXT,
            godfather TEXT,
            godmother TEXT,
            place TEXT,
            place_of_birth TEXT,
            date_of_birth DATE,
            date_of_baptism DATE,
            FOREIGN KEY (parishioner_id) REFERENCES parishioners(id)
        )",
        "CREATE TABLE IF NOT EXISTS deaths (
            parishioner_id INTEGER PRIMARY KEY,
            date_of_death DATE,
            cause TEXT,
            sacraments_received TEXT,
            place_of_burial TEXT,
            minister TEXT,
            cemetery TEXT,
            FOREIGN KEY (parishioner_id) REFERENCES parishioners(id)
        )",
        "CREATE TABLE IF NOT EXISTS first_communions (
            parishioner_id INTEGER PRIMARY KEY,
            minister TEXT,
            place TEXT,
            FOREIGN KEY (parishioner_id) REFERENCES parishioners(id)
        )",
        "CREATE TABLE IF NOT EXISTS confirmations (
            parishioner_id INTEGER PRIMARY KEY,
            minister TEXT,
            sponsor TEXT,
            place TEXT,
            FOREIGN KEY (parishioner_id) REFERENCES parishioners(id)
        )",
        "CREATE TABLE IF NOT EXISTS marriages (
            parishioner_id INTEGER PRIMARY KEY,
            minister TEXT,
            spouse_name TEXT,
            witness1 TEXT,
            witness2 TEXT,
            place TEXT,
            FOREIGN KEY (parishioner_id) REFERENCES parishioners(id)
        )",
        "CREATE TABLE IF NOT EXISTS banns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            parishioner_id INTEGER,
            ref_no TEXT,
            date_issued DATE,
            checkbox1 INTEGER DEFAULT 0,
            checkbox2 INTEGER DEFAULT 0,
            checkbox3 INTEGER DEFAULT 0,
            groom_name TEXT,
            groom_father TEXT,
            groom_mother TEXT,
            groom_place TEXT,
            groom_parish TEXT,
            groom_diocese TEXT,
            groom_dob DATE,
            groom_baptism_place TEXT,
            groom_baptism_date DATE,
            bride_name TEXT,
            bride_father TEXT,
            bride_mother TEXT,
            bride_place TEXT,
            bride_parish TEXT,
            bride_diocese TEXT,
            bride_dob DATE,
            bride_baptism_place TEXT,
            bride_baptism_date DATE,
            impediment TEXT,
            banns1 DATE,
            banns2 DATE,
            banns3 DATE,
            marriage_date DATE,
            marriage_place TEXT,
            FOREIGN KEY (parishioner_id) REFERENCES parishioners(id)
        )",
        "CREATE TABLE IF NOT EXISTS parish_profile (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            church_name TEXT,
            place TEXT,
            diocese TEXT,
            vicar TEXT,
            established_year TEXT,
            address TEXT,
            contact_info TEXT,
            church_image TEXT,
            msg_birthday TEXT,
            msg_marriage TEXT,
            msg_death TEXT,
            asst_vicar TEXT,
            seal_image TEXT,
            signature_image TEXT,
            enable_seal INTEGER DEFAULT 0,
            enable_signature INTEGER DEFAULT 0,
            gemini_api_key TEXT,
            substations TEXT
        )",
        "CREATE TABLE IF NOT EXISTS vouchers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            voucher_type TEXT NOT NULL, -- 'receipt' or 'expense'
            voucher_no TEXT,
            voucher_date DATE,
            person_name TEXT,
            towards TEXT,
            particulars TEXT,
            amount REAL,
            amount_words TEXT,
            payment_mode TEXT,
            reference_no TEXT,
            account_head TEXT,
            category TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS staging_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($commands as $command) {
        $db->exec($command);
    }

    // Migrations
    $f_cols = $db->query("PRAGMA table_info(families)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('family_code', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN family_code TEXT");

    // Rename anbium to anbiyam migration
    if (in_array('anbium', $f_cols) && !in_array('anbiyam', $f_cols)) {
        $db->exec("ALTER TABLE families RENAME COLUMN anbium TO anbiyam");
    }
    if (!in_array('head_name', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN head_name TEXT");
    if (!in_array('spouse_name', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN spouse_name TEXT");
    if (!in_array('head_image', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN head_image TEXT");
    if (!in_array('spouse_image', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN spouse_image TEXT");
    if (!in_array('subscription_type', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN subscription_type TEXT DEFAULT 'yearly'");
    if (!in_array('subscription_amount', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN subscription_amount REAL DEFAULT 1200");
    if (!in_array('subscription_start_date', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN subscription_start_date DATE");

    $cols = $db->query("PRAGMA table_info(parishioners)")->fetchAll(PDO::FETCH_COLUMN, 1);
    // (Previous migrations commented out or kept for safety)
    if (!in_array('education', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN education TEXT");
    if (!in_array('pious_association', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN pious_association TEXT");
    if (!in_array('occupation', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN occupation TEXT");
    if (!in_array('image', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN image TEXT");
    if (!in_array('father_name', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN father_name TEXT");
    if (!in_array('mother_name', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN mother_name TEXT");
    if (!in_array('whatsapp', $cols))
        $db->exec("ALTER TABLE parishioners ADD COLUMN whatsapp TEXT");

    // Sacrament table migrations
    $bap_cols = $db->query("PRAGMA table_info(baptisms)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('place', $bap_cols))
        $db->exec("ALTER TABLE baptisms ADD COLUMN place TEXT");

    $com_cols = $db->query("PRAGMA table_info(first_communions)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('place', $com_cols))
        $db->exec("ALTER TABLE first_communions ADD COLUMN place TEXT");

    $conf_cols = $db->query("PRAGMA table_info(confirmations)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('place', $conf_cols))
        $db->exec("ALTER TABLE confirmations ADD COLUMN place TEXT");

    $mar_cols = $db->query("PRAGMA table_info(marriages)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('place', $mar_cols))
        $db->exec("ALTER TABLE marriages ADD COLUMN place TEXT");
    if (!in_array('spouse_name', $mar_cols))
        $db->exec("ALTER TABLE marriages ADD COLUMN spouse_name TEXT");

    $death_cols = $db->query("PRAGMA table_info(deaths)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('minister', $death_cols))
        $db->exec("ALTER TABLE deaths ADD COLUMN minister TEXT");
    if (!in_array('cemetery', $death_cols))
        $db->exec("ALTER TABLE deaths ADD COLUMN cemetery TEXT");

    $prof_cols = $db->query("PRAGMA table_info(parish_profile)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('church_image', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN church_image TEXT");
    if (!in_array('msg_birthday', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN msg_birthday TEXT");
    if (!in_array('msg_marriage', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN msg_marriage TEXT");
    if (!in_array('msg_death', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN msg_death TEXT");
    if (!in_array('asst_vicar', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN asst_vicar TEXT");
    if (!in_array('seal_image', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN seal_image TEXT");
    if (!in_array('signature_image', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN signature_image TEXT");
    if (!in_array('enable_seal', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN enable_seal INTEGER DEFAULT 0");
    if (!in_array('enable_signature', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN enable_signature INTEGER DEFAULT 0");
    if (!in_array('gemini_api_key', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN gemini_api_key TEXT");
    if (!in_array('pincode', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN pincode TEXT");
    if (!in_array('substations', $prof_cols))
        $db->exec("ALTER TABLE parish_profile ADD COLUMN substations TEXT");

    // Add substation column to families if not exists
    if (!in_array('substation', $f_cols))
        $db->exec("ALTER TABLE families ADD COLUMN substation TEXT");

    // NEW: Custom AI Knowledge Table (User-trained AI)
    $db->exec("CREATE TABLE IF NOT EXISTS custom_ai_knowledge (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        keywords TEXT NOT NULL,
        response TEXT NOT NULL,
        category TEXT DEFAULT 'General',
        hits INTEGER DEFAULT 0
    )");

    // Initial Seeding
    $count = $db->query("SELECT COUNT(*) FROM custom_ai_knowledge")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO custom_ai_knowledge (keywords, response, category) VALUES 
            ('hello,hi,hey,greetings', 'Hello! I am your locally trained Parish Assistant. How can I help you? 😊', 'General'),
            ('help,how to,guide', 'You can train me by adding keywords and responses in the AI Training menu! I match your questions based on the keywords you teach me.', 'Help'),
            ('voucher,vouchers,receipt,expense', 'You can manage Vouchers from the main menu. Create a Receipt for incoming money or an Expense for outgoing money. All records are saved to the database.', 'Accounts'),
            ('history,list,filter', 'The Voucher History page allows you to see all transactions. You can filter by date, category, or type, and even print a summary report.', 'Accounts'),
            ('who made you,developer', 'I was created specially for this Parish Management System to be an offline helper.', 'About')");
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>