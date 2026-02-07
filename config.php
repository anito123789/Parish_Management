<?php
// config.php
// We now fetch from DB, but for performance we might want to cache.
// For simplicity in this small app, we'll fetch on header load or define defaults if DB is empty.

define('ANNUAL_SUBSCRIPTION_AMOUNT', 1200); // Amount due per family per year
define('CURRENCY', '₹');

function format_date($date)
{
    if (!$date || $date == '0000-00-00')
        return '-';
    return date('d-m-Y', strtotime($date));
}

/**
 * Converts a date to a formal textual format (e.g., First day of January, Two Thousand Twenty-Four)
 */
function date_to_words($date)
{
    if (!$date || $date == '0000-00-00')
        return '-';
    $timestamp = strtotime($date);
    $day = (int) date('j', $timestamp);
    $month = date('F', $timestamp);
    $year = (int) date('Y', $timestamp);

    $ordinals = [
        1 => 'First',
        2 => 'Second',
        3 => 'Third',
        4 => 'Fourth',
        5 => 'Fifth',
        6 => 'Sixth',
        7 => 'Seventh',
        8 => 'Eighth',
        9 => 'Ninth',
        10 => 'Tenth',
        11 => 'Eleventh',
        12 => 'Twelfth',
        13 => 'Thirteenth',
        14 => 'Fourteenth',
        15 => 'Fifteenth',
        16 => 'Sixteenth',
        17 => 'Seventeenth',
        18 => 'Eighteenth',
        19 => 'Nineteenth',
        20 => 'Twentieth',
        21 => 'Twenty-first',
        22 => 'Twenty-second',
        23 => 'Twenty-third',
        24 => 'Twenty-fourth',
        25 => 'Twenty-fifth',
        26 => 'Twenty-sixth',
        27 => 'Twenty-seventh',
        28 => 'Twenty-eighth',
        29 => 'Twenty-ninth',
        30 => 'Thirtieth',
        31 => 'Thirty-first'
    ];

    $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"];
    $teens = ["Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
    $tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

    $year_words = "";
    if ($year >= 2000 && $year < 2100) {
        $year_words = "Two Thousand ";
        $rest = $year % 100;
        if ($rest > 0) {
            if ($rest < 10)
                $year_words .= $ones[$rest];
            elseif ($rest < 20)
                $year_words .= $teens[$rest - 10];
            else {
                $year_words .= $tens[floor($rest / 10)];
                if ($rest % 10 > 0)
                    $year_words .= "-" . $ones[$rest % 10];
            }
        }
    } else {
        $year_words = (string) $year; // Fallback for very old/future years
    }

    $day_word = $ordinals[$day] ?? $day;
    return "$day_word Day of $month, $year_words";
}

/**
 * Generate WhatsApp message with proper placeholders
 */
function generate_whatsapp_message($template, $parishioner_name, $gender, $parish_name = null)
{
    global $db;
    
    // Get parish name if not provided
    if (!$parish_name) {
        $profile = $db->query("SELECT church_name FROM parish_profile LIMIT 1")->fetch();
        $parish_name = $profile['church_name'] ?? 'Our Parish';
    }
    
    // Replace name placeholder
    $message = str_replace('[Name]', $parishioner_name, $template);
    
    // Replace parish name placeholder
    $message = str_replace('[Parish Name]', $parish_name, $message);
    
    // Replace gender-specific pronouns
    $is_female = (strtolower($gender) === 'female');
    
    $message = str_replace('[him/her]', $is_female ? 'her' : 'him', $message);
    $message = str_replace('[his/her]', $is_female ? 'her' : 'his', $message);
    $message = str_replace('[he/she]', $is_female ? 'she' : 'he', $message);
    
    return $message;
}

/**
 * Database Backup Utilities
 */
function backup_database($prefix = 'auto_')
{
    $db_file = __DIR__ . '/database/parish.db';
    $backup_dir = __DIR__ . '/backups/';

    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    $filename = $prefix . date('Y-m-d_H-i-s') . '.db';
    $dest = $backup_dir . $filename;

    if (file_exists($db_file)) {
        copy($db_file, $dest);
        return $filename;
    }
    return false;
}

function auto_backup_check()
{
    $backup_dir = __DIR__ . '/backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
        backup_database('initial_');
        return;
    }

    $last_backup = 0;
    $files = glob($backup_dir . 'auto_*.db');
    if ($files) {
        $last_backup = filemtime(end($files));
    }

    // Backup once every 24 hours
    if (time() - $last_backup > 86400) {
        backup_database('auto_');
    }
}
?>