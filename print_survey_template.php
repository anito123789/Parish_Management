<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Parish Census Form</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            background: #e8e8e8;
            margin: 0;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1.3;
        }

        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 20mm;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header h1 {
            margin: 0 0 4px 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header p {
            margin: 0;
            font-size: 8.5pt;
            font-style: italic;
        }

        .section-header {
            background: #d9d9d9;
            border: 1px solid #000;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            margin-top: 8px;
            margin-bottom: 6px;
        }

        .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 6px;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .label {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 1px;
            color: #000;
        }

        input[type="text"],
        input[type="date"],
        input[type="tel"] {
            border: none;
            border-bottom: 1px dotted #000;
            background: transparent;
            font-family: inherit;
            font-size: 9pt;
            padding: 1px 0;
            width: 100%;
            outline: none;
        }

        input:focus {
            border-bottom: 1px solid #2563eb;
            background: #eff6ff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 4px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            background: #e5e5e5;
            text-align: center;
            font-weight: bold;
            font-size: 7.5pt;
        }

        td input {
            border: none !important;
            width: 100%;
            font-size: 8pt;
            padding: 0;
            background: transparent;
        }

        td input:focus {
            background: #eff6ff;
        }

        .member-box {
            border: 1px solid #000;
            padding: 6px;
            margin-bottom: 8px;
            background: #fafafa;
        }

        .member-header {
            font-weight: bold;
            background: #e0e0e0;
            padding: 3px 6px;
            margin: -6px -6px 6px -6px;
            border-bottom: 1px solid #000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9pt;
        }

        .member-header input {
            background: transparent;
            border: none;
            font-weight: bold;
            text-align: right;
            width: 200px;
            font-size: 9pt;
        }

        .page-footer {
            margin-top: auto;
            padding-top: 8px;
            font-size: 7pt;
            text-align: right;
            color: #666;
        }

        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }

        .signature-box {
            flex: 1;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
            height: 40px;
        }

        .signature-label {
            font-size: 8pt;
            font-weight: bold;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            .page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: 297mm;
                page-break-after: always;
                page-break-inside: avoid;
            }

            .page:last-child {
                page-break-after: auto;
            }

            .no-print {
                display: none !important;
            }

            input {
                border-bottom: 1px dotted #000 !important;
            }

            .member-box {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <!-- Toolbar -->
    <div class="no-print"
        style="position: fixed; top: 0; left: 0; width: 100%; background: #1e293b; color: white; padding: 10px 20px; z-index: 100; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
        <div>
            <strong style="font-size: 14px;">📋 Parish Census Form</strong>
            <span style="margin-left: 10px; font-size: 12px; opacity: 0.9;">Fill details and print to PDF</span>
        </div>
        <div>
            <button onclick="window.print()"
                style="background: #10b981; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 13px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                🖨️ Print / Save PDF
            </button>
        </div>
    </div>

    <div style="height: 60px;" class="no-print"></div> <!-- Spacer -->

    <!-- PAGE 1 -->
    <div class="page" id="page1">
        <div class="content-wrapper">
            <div class="header">
                <h1>Parish Family Census Form</h1>
                <p>Please fill all details accurately using BLOCK LETTERS</p>
            </div>

            <div class="section-header">I. Family Information</div>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <span class="label">Head of Family Name</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-group" style="flex:2;">
                    <span class="label">Spouse Name</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-group">
                    <span class="label">Family ID</span>
                    <input type="text" placeholder="Office Use">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <span class="label">Unit / Anbiyam</span>
                    <input type="text">
                </div>
                <div class="form-group">
                    <span class="label">Mobile Number (WhatsApp)</span>
                    <input type="tel" placeholder="+91">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <span class="label">Residential Address</span>
                    <input type="text" placeholder="Door No, Street, Area">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <input type="text" placeholder="City, District, Pincode" style="margin-top: 2px;">
                </div>
            </div>

            <div class="section-header">II. Family Members Details</div>

            <!-- MEMBER 1: Head of Family -->
            <div class="member-box">
                <div class="member-header">
                    <span>1. Head of Family</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <span class="label">Gender</span>
                        <input type="text" placeholder="M/F">
                    </div>
                    <div class="form-group">
                        <span class="label">Date of Birth</span>
                        <input type="text" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group" style="flex:1.5;">
                        <span class="label">Education</span>
                        <input type="text">
                    </div>
                    <div class="form-group" style="flex:1.5;">
                        <span class="label">Occupation</span>
                        <input type="text">
                    </div>
                </div>
                <table>
                    <tr>
                        <th width="14%">Sacrament</th>
                        <th width="12%">Date</th>
                        <th width="22%">Place</th>
                        <th width="20%">Minister</th>
                        <th width="32%">Details (Godparents/Witnesses)</th>
                    </tr>
                    <tr>
                        <td>Baptism</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text" placeholder="Godfather / Godmother"></td>
                    </tr>
                    <tr>
                        <td>Communion</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Confirmation</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text" placeholder="Sponsor"></td>
                    </tr>
                    <tr>
                        <td>Marriage</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text" placeholder="Witnesses"></td>
                    </tr>
                </table>
            </div>

            <!-- MEMBER 2: Spouse -->
            <div class="member-box">
                <div class="member-header">
                    <span>2. Spouse</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <span class="label">Gender</span>
                        <input type="text" placeholder="M/F">
                    </div>
                    <div class="form-group">
                        <span class="label">Date of Birth</span>
                        <input type="text" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group" style="flex:1.5;">
                        <span class="label">Education</span>
                        <input type="text">
                    </div>
                    <div class="form-group" style="flex:1.5;">
                        <span class="label">Occupation</span>
                        <input type="text">
                    </div>
                </div>
                <table>
                    <tr>
                        <th width="14%">Sacrament</th>
                        <th width="12%">Date</th>
                        <th width="22%">Place</th>
                        <th width="20%">Minister</th>
                        <th width="32%">Details</th>
                    </tr>
                    <tr>
                        <td>Baptism</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Communion</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Confirmation</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Marriage</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                </table>
            </div>

            <div class="page-footer">Page 1 of 2</div>
        </div>
    </div>

    <!-- PAGE 2 -->
    <div class="page" id="page2">
        <div class="content-wrapper">
            <div class="section-header" style="margin-top: -6px;">II. Family Members Details (Continued)</div>

            <!-- MEMBER 3: Child/Other -->
            <div class="member-box">
                <div class="member-header">
                    <span>3. Child / Other Member</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <span class="label">Relation</span>
                        <input type="text" placeholder="Son/Daughter/Other">
                    </div>
                    <div class="form-group">
                        <span class="label">Gender</span>
                        <input type="text" placeholder="M/F">
                    </div>
                    <div class="form-group">
                        <span class="label">Date of Birth</span>
                        <input type="text" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group">
                        <span class="label">Education</span>
                        <input type="text">
                    </div>
                    <div class="form-group">
                        <span class="label">Occupation</span>
                        <input type="text">
                    </div>
                </div>
                <table>
                    <tr>
                        <th width="14%">Sacrament</th>
                        <th width="12%">Date</th>
                        <th width="22%">Place</th>
                        <th width="20%">Minister</th>
                        <th width="32%">Details</th>
                    </tr>
                    <tr>
                        <td>Baptism</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Communion</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Confirmation</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                </table>
            </div>

            <!-- MEMBER 4: Child/Other -->
            <div class="member-box">
                <div class="member-header">
                    <span>4. Child / Other Member</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <span class="label">Relation</span>
                        <input type="text" placeholder="Son/Daughter/Other">
                    </div>
                    <div class="form-group">
                        <span class="label">Gender</span>
                        <input type="text" placeholder="M/F">
                    </div>
                    <div class="form-group">
                        <span class="label">Date of Birth</span>
                        <input type="text" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group">
                        <span class="label">Education</span>
                        <input type="text">
                    </div>
                    <div class="form-group">
                        <span class="label">Occupation</span>
                        <input type="text">
                    </div>
                </div>
                <table>
                    <tr>
                        <th width="14%">Sacrament</th>
                        <th width="12%">Date</th>
                        <th width="22%">Place</th>
                        <th width="20%">Minister</th>
                        <th width="32%">Details</th>
                    </tr>
                    <tr>
                        <td>Baptism</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Communion</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Confirmation</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                </table>
            </div>

            <!-- MEMBER 5: Child/Other -->
            <div class="member-box">
                <div class="member-header">
                    <span>5. Child / Other Member</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <span class="label">Relation</span>
                        <input type="text" placeholder="Son/Daughter/Other">
                    </div>
                    <div class="form-group">
                        <span class="label">Gender</span>
                        <input type="text" placeholder="M/F">
                    </div>
                    <div class="form-group">
                        <span class="label">Date of Birth</span>
                        <input type="text" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group">
                        <span class="label">Education</span>
                        <input type="text">
                    </div>
                    <div class="form-group">
                        <span class="label">Occupation</span>
                        <input type="text">
                    </div>
                </div>
                <table>
                    <tr>
                        <th width="14%">Sacrament</th>
                        <th width="12%">Date</th>
                        <th width="22%">Place</th>
                        <th width="20%">Minister</th>
                        <th width="32%">Details</th>
                    </tr>
                    <tr>
                        <td>Baptism</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Communion</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Confirmation</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                </table>
            </div>

            <!-- MEMBER 6: Child/Other -->
            <div class="member-box">
                <div class="member-header">
                    <span>6. Child / Other Member</span>
                    <input type="text" placeholder="Full Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <span class="label">Relation</span>
                        <input type="text" placeholder="Son/Daughter/Other">
                    </div>
                    <div class="form-group">
                        <span class="label">Gender</span>
                        <input type="text" placeholder="M/F">
                    </div>
                    <div class="form-group">
                        <span class="label">Date of Birth</span>
                        <input type="text" placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group">
                        <span class="label">Education</span>
                        <input type="text">
                    </div>
                    <div class="form-group">
                        <span class="label">Occupation</span>
                        <input type="text">
                    </div>
                </div>
                <table>
                    <tr>
                        <th width="14%">Sacrament</th>
                        <th width="12%">Date</th>
                        <th width="22%">Place</th>
                        <th width="20%">Minister</th>
                        <th width="32%">Details</th>
                    </tr>
                    <tr>
                        <td>Baptism</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Communion</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td>Confirmation</td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                </table>
            </div>

            <div class="section-header">III. Deceased Family Members</div>
            <div style="border: 1px solid #000; padding: 4px; background: #fafafa;">
                <table>
                    <tr>
                        <th width="28%">Name</th>
                        <th width="16%">Relationship</th>
                        <th width="14%">Date of Death</th>
                        <th width="20%">Cause</th>
                        <th width="22%">Place of Burial</th>
                    </tr>
                    <tr>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                    <tr>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                        <td><input type="text"></td>
                    </tr>
                </table>
            </div>

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature of Family Head</div>
                    <div style="font-size: 7pt; margin-top: 2px; color: #666;">Date: _______________</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature of Parish Priest</div>
                    <div style="font-size: 7pt; margin-top: 2px; color: #666;">Date: _______________</div>
                </div>
            </div>

            <div class="page-footer">Page 2 of 2</div>
        </div>
    </div>

</body>

</html>