<?php
require_once 'db.php';
include 'includes/header.php';

$uploadedPdf = null;
if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
    if (!is_dir('uploads/temp_pdfs'))
        mkdir('uploads/temp_pdfs', 0777, true);
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $_FILES['pdf_file']['name']);
    $filepath = 'uploads/temp_pdfs/' . $filename;
    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $filepath)) {
        $uploadedPdf = $filepath;
    }
}
?>

<div class="container-fluid" style="height: calc(100vh - 100px); display: flex; flex-direction: column;">
    <!-- Step 1: Upload (if no PDF) -->
    <?php if (!$uploadedPdf): ?>
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0"><i class="fas fa-file-pdf"></i> Upload Scanned/Digital PDF</h5>
                    </div>
                    <div class="card-body">
                        <p>Upload a filled Parish Census PDF to transcribe and import its data.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group mb-4">
                                <label class="btn btn-outline-primary btn-block p-4"
                                    style="border-style: dashed; cursor: pointer;">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i><br>
                                    <strong>Click to Select PDF</strong>
                                    <input type="file" name="pdf_file" accept=".pdf" style="display: none;"
                                        onchange="this.form.submit()">
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!-- Step 2: Split Screen (Form Left, PDF Right) -->
        <div class="row h-100 no-gutters">

            <!-- Left: Data Entry Form -->
            <div class="col-md-6 h-100" style="overflow-y: auto; background: #f8f9fc; border-right: 2px solid #ddd;">
                <div class="p-3">
                    <h5 class="text-primary mb-3">Parishioner Data Entry</h5>

                    <!-- This form submits to process_survey.php just like the public form -->
                    <form action="process_survey.php" method="POST" id="entryForm">
                        <input type="hidden" name="source" value="pdf_entry">

                        <!-- Family Details -->
                        <div class="card mb-3">
                            <div class="card-header py-2"><strong>I. Family Details</strong></div>
                            <div class="card-body p-3">
                                <div class="form-group mb-2">
                                    <label class="small text-muted">Head Name</label>
                                    <input type="text" name="family[head_name]" class="form-control form-control-sm"
                                        required>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group mb-2">
                                            <label class="small text-muted">Family ID</label>
                                            <input type="text" name="family[family_code]"
                                                class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group mb-2">
                                            <label class="small text-muted">Anbiyam</label>
                                            <input type="text" name="family[anbiyam]" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group mb-2">
                                            <label class="small text-muted">Phone</label>
                                            <input type="text" name="family[phone]" class="form-control form-control-sm"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="small text-muted">Address</label>
                                    <textarea name="family[address]" class="form-control form-control-sm"
                                        rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Members -->
                        <div id="members_container"></div>

                        <div class="text-center my-3">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addMember()">+ Add
                                Member</button>
                        </div>

                        <button type="submit" class="btn btn-success btn-block py-2">
                            <i class="fas fa-save"></i> Save & Queue for Import
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: PDF Viewer -->
            <div class="col-md-6 h-100">
                <div class="h-100 d-flex flex-column">
                    <div class="bg-dark text-white p-2 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-eye"></i> PDF Viewer</span>
                        <a href="pdf_data_entry.php" class="btn btn-sm btn-light">Change File</a>
                    </div>
                    <iframe src="<?php echo htmlspecialchars($uploadedPdf); ?>"
                        style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>

        </div>

        <!-- Reusing JS from public_survey.php but simplified for admin context -->
        <script>
            let memberCount = 0;

            function addMember() {
                memberCount++;
                const id = memberCount;
                // Condensed member card for side panel
                const html = `
            <div class="card mb-2" id="member_card_${id}">
                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
                    <span class="font-weight-bold text-primary small">#${id} Member</span>
                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="document.getElementById('member_card_${id}').remove()">×</button>
                </div>
                <div class="card-body p-2">
                    <div class="row no-gutters">
                        <div class="col-8 pr-1"><input type="text" name="members[${id}][name]" class="form-control form-control-sm mb-1" placeholder="Name" required></div>
                        <div class="col-4"><select name="members[${id}][relationship]" class="form-control form-control-sm mb-1"><option value="Head">Head</option><option value="Spouse">Spouse</option><option value="Son">Son</option><option value="Daughter">Daughter</option><option value="Other">Other</option></select></div>
                    </div>
                    <div class="row no-gutters">
                         <div class="col-4 pr-1"><select name="members[${id}][gender]" class="form-control form-control-sm mb-1"><option value="Male">Male</option><option value="Female">Female</option></select></div>
                         <div class="col-4 pr-1"><input type="date" name="members[${id}][dob]" class="form-control form-control-sm mb-1"></div>
                         <div class="col-4"><input type="text" name="members[${id}][occupation]" class="form-control form-control-sm mb-1" placeholder="Job"></div>
                    </div>
                    
                    <!-- Simplified Sacraments Checkboxes -->
                    <div class="d-flex justify-content-between my-2 small" style="background:#eee; padding:4px; border-radius:4px;">
                        <label><input type="checkbox" onchange="toggleSec(this, 'b_det_${id}')"> Bap</label>
                        <label><input type="checkbox" onchange="toggleSec(this, 'c_det_${id}')"> Com</label>
                        <label><input type="checkbox" onchange="toggleSec(this, 'conf_det_${id}')"> Con</label>
                        <label><input type="checkbox" onchange="toggleSec(this, 'm_det_${id}')"> Mar</label>
                        <label class="text-danger"><input type="checkbox" name="members[${id}][is_deceased]" value="1"> Died</label>
                    </div>

                    <!-- Details Sections (Hidden by default) -->
                    <div id="b_det_${id}" style="display:none;" class="mb-1 p-1 border">
                        <small class="text-primary font-weight-bold">Baptism</small>
                        <div class="row no-gutters"><div class="col-6"><input type="date" name="members[${id}][baptism_date]" class="form-control form-control-sm"></div><div class="col-6"><input type="text" name="members[${id}][b_place]" placeholder="Place" class="form-control form-control-sm"></div></div>
                        <div class="row no-gutters"><div class="col-6"><input type="text" name="members[${id}][b_minister]" placeholder="Minister" class="form-control form-control-sm"></div><div class="col-6"><input type="text" name="members[${id}][b_godfather]" placeholder="Godfather" class="form-control form-control-sm"></div></div>
                    </div>
                    <div id="c_det_${id}" style="display:none;" class="mb-1 p-1 border">
                        <small class="text-warning font-weight-bold">Communion</small>
                        <div class="row no-gutters"><div class="col-6"><input type="date" name="members[${id}][communion_date]" class="form-control form-control-sm"></div><div class="col-6"><input type="text" name="members[${id}][c_place]" placeholder="Place" class="form-control form-control-sm"></div></div>
                    </div>
                    <div id="conf_det_${id}" style="display:none;" class="mb-1 p-1 border">
                        <small class="text-danger font-weight-bold">Confirmation</small>
                        <div class="row no-gutters"><div class="col-6"><input type="date" name="members[${id}][confirmation_date]" class="form-control form-control-sm"></div><div class="col-6"><input type="text" name="members[${id}][conf_place]" placeholder="Place" class="form-control form-control-sm"></div></div>
                    </div>
                     <div id="m_det_${id}" style="display:none;" class="mb-1 p-1 border">
                        <small class="text-purple font-weight-bold" style="color:purple">Marriage</small>
                        <div class="row no-gutters"><div class="col-6"><input type="date" name="members[${id}][marriage_date]" class="form-control form-control-sm"></div><div class="col-6"><input type="text" name="members[${id}][m_place]" placeholder="Place" class="form-control form-control-sm"></div></div>
                    </div>

                </div>
            </div>`;
                document.getElementById('members_container').insertAdjacentHTML('beforeend', html);
            }

            function toggleSec(chk, id) {
                document.getElementById(id).style.display = chk.checked ? 'block' : 'none';
            }

            // Add default members
            addMember(); addMember(); addMember();
        </script>
    <?php endif; ?>
</div>

<?php
// Don't include footer if in split view to maximize space
if (!$uploadedPdf)
    include 'includes/footer.php';
?>