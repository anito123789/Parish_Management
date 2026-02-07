<?php
// Prevent any whitespace before this tag

// Internal logging
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . DIRECTORY_SEPARATOR . 'qr_errors.log');

require_once 'db.php';

// Check if library exists
$lib_path = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'phpqrcode.php';
if (!file_exists($lib_path)) {
    error_log("CRITICAL: phpqrcode.php missing");
    if (isset($silent_qr))
        return;
    else
        exit;
}
require_once $lib_path;

$id = $_GET['id'] ?? null;
if (!$id)
    if (isset($silent_qr))
        return;
    else
        exit;

// Fetch family
$stmt = $db->prepare("SELECT * FROM families WHERE id = ?");
$stmt->execute([$id]);
$family = $stmt->fetch();

if (!$family)
    if (isset($silent_qr))
        return;
    else
        exit;

$family_code = $family['family_code'] ?? 'FAM' . str_pad($id, 4, '0', STR_PAD_LEFT);
$anbiyam_name = trim($family['anbiyam'] ?? 'General');
if (empty($anbiyam_name))
    $anbiyam_name = 'General';

// Directories
$base_dir = __DIR__ . DIRECTORY_SEPARATOR . 'qrcodes';
if (!file_exists($base_dir))
    @mkdir($base_dir, 0777, true);

$safe_anbiyam = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $anbiyam_name);
$save_path = $base_dir . DIRECTORY_SEPARATOR . $safe_anbiyam;
if (!file_exists($save_path))
    @mkdir($save_path, 0777, true);

// 1. Generate QR Code into a temp file
$temp_qr = $base_dir . DIRECTORY_SEPARATOR . 'temp_qr_' . $id . '.png';
QRcode::png($family_code, $temp_qr, QR_ECLEVEL_L, 10, 2);

if (!file_exists($temp_qr)) {
    error_log("ERROR: Library failed to create temp file at $temp_qr");
    if (isset($silent_qr))
        return;
    else
        exit;
}

$qr_temp = @imagecreatefrompng($temp_qr);
@unlink($temp_qr);

if (!$qr_temp) {
    error_log("ERROR: Could not load QR image from temp file for Family ID: $id");
    if (isset($silent_qr))
        return;
    else
        exit;
}

$width = imagesx($qr_temp);
$height = imagesy($qr_temp);

// 2. Create Final Image with Labels
$new_height = $height + 85;
$final_image = imagecreatetruecolor($width, $new_height);
$white = imagecolorallocate($final_image, 255, 255, 255);
$black = imagecolorallocate($final_image, 0, 0, 0);

imagefill($final_image, 0, 0, $white);
imagecopy($final_image, $qr_temp, 0, 0, 0, 0, $width, $height);

// Add Labels
$center = $width / 2;

// Code
$f5 = 5;
$txt_code = $family_code;
$w_code = imagefontwidth($f5) * strlen($txt_code);
imagestring($final_image, $f5, $center - ($w_code / 2), $height + 5, $txt_code, $black);

// Name
$f3 = 3;
$txt_name = substr($family['name'], 0, 40);
$w_name = imagefontwidth($f3) * strlen($txt_name);
imagestring($final_image, $f3, $center - ($w_name / 2), $height + 30, $txt_name, $black);

// Anbiyam
$f2 = 2;
$txt_anb = "Anbiyam: " . $anbiyam_name;
$w_anb = imagefontwidth($f2) * strlen($txt_anb);
imagestring($final_image, $f2, $center - ($w_anb / 2), $height + 55, $txt_anb, $black);

// 3. Save to Folder
$clean_code = preg_replace('/[^a-zA-Z0-9_\-]/', '', $family_code);
$filename = 'QR_' . $clean_code . '.png';
$full_save_path = $save_path . DIRECTORY_SEPARATOR . $filename;

if (!@imagepng($final_image, $full_save_path)) {
    error_log("ERROR: Failed to save QR image to $full_save_path");
}

// 4. Output to Browser (Only if not included silently)
if (!isset($silent_qr)) {
    if (!headers_sent()) {
        header('Content-Type: image/png');
        if (isset($_GET['preview'])) {
            header('Content-Disposition: inline; filename="' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
    }
    imagepng($final_image);
}

// Cleanup
imagedestroy($qr_temp);
imagedestroy($final_image);
