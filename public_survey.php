<?php
// strictly no auth check here as it is public
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parish Census Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #64748b;
            --bg: #f1f5f9;
            --paper: #ffffff;
            --text: #1e293b;
            --border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 800px;
            /* A4 width approx */
            background: var(--paper);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }

        .progress-bar {
            height: 6px;
            background: #e0e7ff;
            width: 100%;
        }

        .progress-fill {
            height: 100%;
            background: #34d399;
            width: 50%;
            /* Starts at 50% */
            transition: width 0.3s ease;
        }

        .content {
            padding: 2rem;
        }

        .step {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 600px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: white;
            border: 1px solid var(--border);
            color: var(--secondary);
        }

        .btn-secondary:hover {
            background: #f8fafc;
            color: var(--text);
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-weight: 600;
            color: var(--primary);
        }

        .remove-btn {
            color: #ef4444;
            font-size: 0.85rem;
            cursor: pointer;
            background: none;
            border: none;
        }

        /* Checkbox style */
        .checkbox-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
        }

        .checkbox-item:hover {
            border-color: var(--primary);
        }

        .checkbox-item input:checked+span {
            color: var(--primary);
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1>Parish Census Form</h1>
            <p>Please fill in your family details accurately.</p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                <a href="print_survey_template.php" target="_blank"
                    style="color: rgba(255,255,255,0.9); text-decoration: underline;">
                    🖨️ Download Printable Form (PDF)
                </a>
            </p>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress"></div>
        </div>

        <form id="surveyForm" action="process_survey.php" method="POST" class="content">

            <!-- STEP 1: FAMILY DETAILS -->
            <div class="step active" id="step1">
                <h2 style="margin-top: 0; color: var(--primary);">Step 1: Family Details</h2>
                <p style="color: var(--secondary); margin-bottom: 1.5rem;">Enter the details of the family head and
                    residence.</p>

                <div class="grid">
                    <div class="form-group">
                        <label>Head of Family Name <span style="color:red">*</span></label>
                        <input type="text" name="family[head_name]" required placeholder="e.g. John Doe">
                    </div>
                    <div class="form-group">
                        <label>Spouse Name</label>
                        <input type="text" name="family[spouse_name]" placeholder="e.g. Mary Doe">
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Phone / WhatsApp <span style="color:red">*</span></label>
                        <input type="tel" name="family[phone]" required placeholder="e.g. 9876543210">
                    </div>
                    <div class="form-group">
                        <label>Anbiyam (Unit)</label>
                        <input type="text" name="family[anbiyam]" placeholder="e.g. St. Anthony Anbiyam">
                    </div>
                </div>

                <div class="form-group">
                    <label>Residential Address</label>
                    <textarea name="family[address]" rows="3" placeholder="Enter full address..."></textarea>
                </div>

                <div class="actions" style="justify-content: flex-end;">
                    <button type="button" class="btn btn-primary" onclick="goToStep(2)">Next Step &rarr;</button>
                </div>
            </div>

            <!-- STEP 2: FAMILY MEMBERS -->
            <div class="step" id="step2">
                <h2 style="margin-top: 0; color: var(--primary);">Step 2: Family Members</h2>
                <p style="color: var(--secondary); margin-bottom: 1.5rem;">Add all members of the family including
                    yourself.</p>

                <div id="members_container">
                    <!-- Members will be added here via JS -->
                </div>

                <button type="button" class="btn btn-secondary"
                    style="width: 100%; border: 2px dashed var(--border); margin-top: 1rem;" onclick="addMember()">
                    + Add Family Member
                </button>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(1)">&larr; Back</button>
                    <button type="submit" class="btn btn-primary">Submit Info &rarr;</button>
                </div>
            </div>

        </form>
    </div>

    <script>
        let memberCount = 0;

        function goToStep(step) {
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');

            const progress = step === 1 ? '50%' : '100%';
            document.getElementById('progress').style.width = progress;

            window.scrollTo(0, 0);
        }

        function addMember() {
            memberCount++;
            const id = memberCount;

            const html = `
        <div class="card" id="member_card_${id}">
            <div class="card-header">
                <span class="card-title">Number #${id}</span>
                <button type="button" class="remove-btn" onclick="removeMember(${id})">Remove</button>
            </div>
            
            <div class="grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="members[${id}][name]" required placeholder="Name">
                </div>
                <div class="form-group">
                    <label>Relationship</label>
                    <select name="members[${id}][relationship]">
                        <option value="Head">Head</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Son">Son</option>
                        <option value="Daughter">Daughter</option>
                        <option value="Parent">Parent</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="members[${id}][dob]">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="members[${id}][gender]">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                 <div class="form-group">
                    <label>Education</label>
                    <input type="text" name="members[${id}][education]" placeholder="e.g. 10th">
                </div>
                 <div class="form-group">
                    <label>Occupation</label>
                    <input type="text" name="members[${id}][occupation]" placeholder="e.g. Student">
                </div>
            </div>
            
            <div class="form-group">
                <label>Sacraments Received</label>
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" onchange="toggleSacrament(this, 'baptism_${id}')"> 
                        <span>Baptism</span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" onchange="toggleSacrament(this, 'communion_${id}')"> 
                        <span>Communion</span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" onchange="toggleSacrament(this, 'confirmation_${id}')"> 
                        <span>Confirmation</span>
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" onchange="toggleSacrament(this, 'marriage_${id}')"> 
                        <span>Marriage</span>
                    </label>
                </div>
            </div>

            <!-- Sacrament Details -->
            <div class="sacrament-details" style="background: #f1f5f9; padding: 1rem; border-radius: 8px; margin-top: 1rem; display:none;" id="sacraments_wrapper_${id}">
                
                <div id="baptism_${id}" style="display:none; margin-bottom: 1rem; border-bottom: 1px dashed #ccc; padding-bottom: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #4f46e5;">Baptism Details</h4>
                    <div class="grid">
                        <div class="form-group"><label>Date</label><input type="date" name="members[${id}][baptism_date]"></div>
                        <div class="form-group"><label>Place</label><input type="text" name="members[${id}][b_place]" placeholder="Church Name, City"></div>
                        <div class="form-group"><label>Minister</label><input type="text" name="members[${id}][b_minister]" placeholder="Rev. Fr..."></div>
                        <div class="form-group"><label>Godfather</label><input type="text" name="members[${id}][b_godfather]"></div>
                        <div class="form-group"><label>Godmother</label><input type="text" name="members[${id}][b_godmother]"></div>
                    </div>
                </div>

                <div id="communion_${id}" style="display:none; margin-bottom: 1rem; border-bottom: 1px dashed #ccc; padding-bottom: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #f59e0b;">First Communion</h4>
                    <div class="grid">
                        <div class="form-group"><label>Date</label><input type="date" name="members[${id}][communion_date]"></div>
                        <div class="form-group"><label>Place</label><input type="text" name="members[${id}][c_place]" placeholder="Church Name"></div>
                        <div class="form-group"><label>Minister</label><input type="text" name="members[${id}][c_minister]"></div>
                    </div>
                </div>

                <div id="confirmation_${id}" style="display:none; margin-bottom: 1rem; border-bottom: 1px dashed #ccc; padding-bottom: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #ef4444;">Confirmation</h4>
                    <div class="grid">
                        <div class="form-group"><label>Date</label><input type="date" name="members[${id}][confirmation_date]"></div>
                        <div class="form-group"><label>Place</label><input type="text" name="members[${id}][conf_place]" placeholder="Church Name"></div>
                        <div class="form-group"><label>Minister</label><input type="text" name="members[${id}][conf_minister]" placeholder="Bishop..."></div>
                        <div class="form-group"><label>Sponsor/Godparent</label><input type="text" name="members[${id}][conf_sponsor]"></div>
                    </div>
                </div>

                <div id="marriage_${id}" style="display:none; margin-bottom: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #8b5cf6;">Marriage</h4>
                    <div class="grid">
                        <div class="form-group"><label>Date</label><input type="date" name="members[${id}][marriage_date]"></div>
                        <div class="form-group"><label>Place</label><input type="text" name="members[${id}][m_place]" placeholder="Church Name"></div>
                        <div class="form-group"><label>Minister</label><input type="text" name="members[${id}][m_minister]"></div>
                        <div class="form-group"><label>Witness 1</label><input type="text" name="members[${id}][m_witness1]"></div>
                        <div class="form-group"><label>Witness 2</label><input type="text" name="members[${id}][m_witness2]"></div>
                    </div>
                </div>
            </div>

            <!-- Deceased Section -->
            <div style="margin-top: 1rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                <label class="checkbox-item" style="display: inline-flex;">
                    <input type="checkbox" name="members[${id}][is_deceased]" value="1" onchange="toggleDeceased(this, 'deceased_${id}')"> 
                    <span>Is Deceased?</span>
                </label>
                
                <div id="deceased_${id}" style="display:none; margin-top: 1rem; background: #fee2e2; padding: 1rem; border-radius: 8px;">
                     <h4 style="margin: 0 0 0.5rem 0; color: #b91c1c;">Death Record</h4>
                     <div class="grid">
                        <div class="form-group"><label>Date of Death</label><input type="date" name="members[${id}][death_date]"></div>
                        <div class="form-group"><label>Cause</label><input type="text" name="members[${id}][death_cause]"></div>
                        <div class="form-group"><label>Place of Burial</label><input type="text" name="members[${id}][death_place]"></div>
                     </div>
                </div>
            </div>

        </div>
        `;

            document.getElementById('members_container').insertAdjacentHTML('beforeend', html);
        }

        function removeMember(id) {
            document.getElementById('member_card_' + id).remove();
        }

        function toggleSacrament(checkbox, divId) {
            const div = document.getElementById(divId);
            div.style.display = checkbox.checked ? 'block' : 'none';

            // Check if any child of wrapper is visible to show wrapper
            const wrapper = div.parentElement;
            const siblings = wrapper.children;
            let anyVisible = false;
            for (let i = 0; i < siblings.length; i++) {
                if (siblings[i].style.display === 'block') anyVisible = true;
            }
            wrapper.style.display = anyVisible ? 'block' : 'none';

            // Scroll to new section
            if (checkbox.checked) div.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function toggleDeceased(checkbox, divId) {
            document.getElementById(divId).style.display = checkbox.checked ? 'block' : 'none';
        }

        // Add one member by default (Head)
        addMember();

        // Pre-fill Head name if typed in step 1
        document.querySelector('input[name="family[head_name]"]').addEventListener('input', function (e) {
            const firstMemberName = document.querySelector('input[name="members[1][name]"]');
            if (firstMemberName && !firstMemberName.value) {
                firstMemberName.value = e.target.value;
            }
        });

    </script>

</body>

</html>