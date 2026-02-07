<?php
require_once 'db.php';
include 'includes/header.php';

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];

        // Read CSV
        $handle = fopen($file, "r");
        $headers = fgetcsv($handle, 0, ",", "\"", "\\"); // Explicitly provide default parameters to fix Deprecated warning

        $count = 0;

        // Prepare statements - Corrected column name 'phone' instead of 'mobile'
        $stmt_fam = $db->prepare("INSERT INTO families (name, family_code, phone, address, anbiyam) VALUES (?, ?, ?, ?, ?)");
        $stmt_par = $db->prepare("INSERT INTO parishioners (family_id, name, relationship, gender, dob, education, occupation, baptism_date, communion_date, confirmation_date, marriage_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, ",", "\"", "\\")) !== FALSE) {
                // Map Data (Assuming standard simple structure for now, can be improved)
                // We need to robustly find columns. For this first version, we'll try to guess based on standard Google Form headers users might make
                // OR we can enforce a template. Let's try to map by index if headers match loosely, or provided index.

                // Let's assume a "Family" based row like:
                // Timestamp, Email, Family Head, Phone, Address, Anbiyam, Spouse Name, Spouse DOB..., Child 1 Name...

                // For simplicity/robustness in V1, let's look for known keys in $headers or fallback to indices.
                $data = $row;

                // Helper to get value by approximated header name
                $getVal = function ($keywords) use ($headers, $data) {
                    foreach ($headers as $i => $h) {
                        foreach ((array) $keywords as $k) {
                            if (stripos($h, $k) !== false)
                                return $data[$i] ?? '';
                        }
                    }
                    return '';
                };

                // 1. Create Family
                $headName = $getVal(['Head Name', 'Family Head', 'Name of Head']); // Fallback to col 2 if needed?
                if (empty($headName))
                    continue; // Skip empty rows

                $phone = $getVal(['Phone', 'Mobile', 'WhatsApp']);
                $address = $getVal(['Address', 'Residential']);
                $anbiyam = $getVal(['Anbiyam', 'Unit', 'BCC']);
                $famCode = "FAM-" . rand(1000, 9999); // Auto generate ID if not in sheet

                $stmt_fam->execute([$headName, $famCode, $phone, $address, $anbiyam]);
                $famId = $db->lastInsertId();

                // 2. Add Head
                $dob = $getVal(['Head DOB', 'Date of Birth (Head)']);
                // Sacraments for Head
                $b_date = $getVal(['Head Baptism', 'Baptism Date (Head)']);
                $c_date = $getVal(['Head Communion', 'Communion Date (Head)']);
                $conf_date = $getVal(['Head Confirmation', 'Confirmation Date (Head)']);
                $m_date = $getVal(['Head Marriage', 'Marriage Date (Head)']);

                $stmt_par->execute([$famId, $headName, 'Head', 'Male', $dob, '', '', $b_date, $c_date, $conf_date, $m_date]);

                // 3. Add Spouse (if exists)
                $spouseName = $getVal(['Spouse Name', 'Wife Name', 'Husband Name']);
                if (!empty($spouseName)) {
                    $s_dob = $getVal(['Spouse DOB']);
                    $s_b = $getVal(['Spouse Baptism']);
                    $s_c = $getVal(['Spouse Communion']);
                    $s_conf = $getVal(['Spouse Confirmation']);
                    $s_m = $getVal(['Spouse Marriage']);

                    $stmt_par->execute([$famId, $spouseName, 'Spouse', 'Female', $s_dob, '', '', $s_b, $s_c, $s_conf, $s_m]);
                }

                // 4. Add Children (Loop for Child 1 to 5)
                for ($i = 1; $i <= 5; $i++) {
                    $cName = $getVal(["Child $i Name", "Name of Child $i", "Member $i Name"]);
                    if (!empty($cName)) {
                        $cRel = "Son/Daughter"; // Generic default
                        $cDob = $getVal(["Child $i DOB", "DOB $i"]);
                        $cGender = $getVal(["Child $i Gender", "Gender $i"]);

                        $c_b = $getVal(["Child $i Baptism"]);
                        $c_c = $getVal(["Child $i Communion"]);
                        $c_conf = $getVal(["Child $i Confirmation"]);
                        $c_m = $getVal(["Child $i Marriage"]);

                        $stmt_par->execute([$famId, $cName, $cRel, $cGender, $cDob, '', '', $c_b, $c_c, $c_conf, $c_m]);
                    }
                }

                $count++;
            }

            $db->commit();
            $msg = "Successfully imported $count families!";
            $msgType = "success";

        } catch (Exception $e) {
            $db->rollBack();
            $msg = "Error importing: " . $e->getMessage();
            $msgType = "danger";
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-file-csv"></i> Import Google Form Responses</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($msg)): ?>
                        <div class="alert alert-<?php echo $msgType; ?>">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>

                    <p>Upload the <strong>CSV file</strong> exported from your Google Form (Responses Sheet).</p>

                    <div class="alert alert-info">
                        <strong>Expected Google Form Column format (flexible):</strong><br>
                        <ul>
                            <li>Head Name, Phone, Address, Anbiyam</li>
                            <li>Spouse Name, Spouse DOB...</li>
                            <li>Child 1 Name, Child 1 DOB, Child 1 Baptism...</li>
                        </ul>
                        <small>The system will try to auto-match columns based on names like "Head Name", "Child 1
                            Name", etc.</small>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group mb-4">
                            <label class="btn btn-outline-success btn-block p-5"
                                style="border-style: dashed; cursor: pointer;">
                                <i class="fas fa-file-csv fa-3x mb-3"></i><br>
                                <strong>Select CSV File</strong>
                                <input type="file" name="csv_file" accept=".csv" style="display: none;"
                                    onchange="this.form.submit()">
                            </label>
                        </div>
                    </form>

                    <hr>

                    <h5 class="mb-3">📌 Step-by-Step Guide: Google Form Setup & Import</h5>

                    <div class="accordion" id="guideAccordion">
                        <!-- Step 1 -->
                        <div class="card">
                            <div class="card-header bg-light" id="headingOne" data-toggle="collapse"
                                data-target="#collapseOne" style="cursor: pointer;">
                                <h6 class="mb-0 text-primary">Step 1: Create the Google Form (Use these Exact Questions)
                                </h6>
                            </div>
                            <div id="collapseOne" class="collapse show" data-parent="#guideAccordion">
                                <div class="card-body">
                                    <p>Create a new Google Form with the following Question Titles. <span
                                            class="text-danger">Using similar keywords helps the Importer recognize
                                            them.</span></p>

                                    <strong>Section 1: Family Info</strong>
                                    <ul>
                                        <li><code>Head Name</code> (Short Answer)</li>
                                        <li><code>Phone</code> (Short Answer)</li>
                                        <li><code>Address</code> (Paragraph)</li>
                                        <li><code>Anbiyam</code> (Short Answer/Dropdown)</li>
                                        <li><code>Head DOB</code> (Date), <code>Head Baptism</code> (Date),
                                            <code>Head Communion</code> (Date), <code>Head Confirmation</code> (Date),
                                            <code>Head Marriage</code> (Date)
                                        </li>
                                    </ul>

                                    <strong>Section 2: Spouse Info</strong>
                                    <ul>
                                        <li><code>Spouse Name</code> (Short Answer)</li>
                                        <li><code>Spouse DOB</code>, <code>Spouse Baptism</code>,
                                            <code>Spouse Communion</code>, <code>Spouse Confirmation</code>,
                                            <code>Spouse Marriage</code> (Dates)
                                        </li>
                                    </ul>

                                    <strong>Section 3: Children (Repeat for Child 1, Child 2, etc.)</strong>
                                    <ul>
                                        <li><code>Child 1 Name</code> (Short Answer)</li>
                                        <li><code>Child 1 Gender</code> (Male/Female)</li>
                                        <li><code>Child 1 DOB</code> (Date)</li>
                                        <li><code>Child 1 Baptism</code>, <code>Child 1 Communion</code>,
                                            <code>Child 1 Confirmation</code> (Dates)
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="card">
                            <div class="card-header bg-light" id="headingTwo" data-toggle="collapse"
                                data-target="#collapseTwo" style="cursor: pointer;">
                                <h6 class="mb-0 text-primary">Step 2: Export Data to CSV</h6>
                            </div>
                            <div id="collapseTwo" class="collapse" data-parent="#guideAccordion">
                                <div class="card-body">
                                    <ol>
                                        <li>Open your Google Form and click on the <strong>Responses</strong> tab at the
                                            top.</li>
                                        <li>Click the green <strong>Excel/Sheets Icon</strong> ("Link to Sheets"). This
                                            opens a Google Sheet.</li>
                                        <li>In the Google Sheet menu, go to: <strong>File &rarr; Download &rarr; Comma
                                                Separated Values (.csv)</strong>.</li>
                                        <li>Save this <code>.csv</code> file to your computer.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="card">
                            <div class="card-header bg-light" id="headingThree" data-toggle="collapse"
                                data-target="#collapseThree" style="cursor: pointer;">
                                <h6 class="mb-0 text-primary">Step 3: Import Here</h6>
                            </div>
                            <div id="collapseThree" class="collapse" data-parent="#guideAccordion">
                                <div class="card-body">
                                    <ol>
                                        <li>Click the big dashed box above on this page.</li>
                                        <li>Select the <code>.csv</code> file you just downloaded.</li>
                                        <li>Wait for the "Success" message. Check the <strong>Families</strong> page to
                                            see your new data!</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>