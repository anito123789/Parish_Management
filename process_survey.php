<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $family = $_POST['family'] ?? [];
    $members = $_POST['members'] ?? [];

    // Basic validation
    if (empty($family['head_name']) || empty($family['phone'])) {
        die("Error: Head Name and Phone are required.");
    }

    $fullData = [
        'family' => $family,
        'members' => $members
    ];

    $jsonData = json_encode($fullData);
    $head = $family['head_name'];
    $phone = $family['phone'];

    try {
        $stmt = $db->prepare("INSERT INTO staging_submissions (family_head, phone, data) VALUES (?, ?, ?)");
        $stmt->execute([$head, $phone, $jsonData]);

        // Success Page
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Submission Successful</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Inter', sans-serif;
                    background: #f1f5f9;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }

                .card {
                    background: white;
                    padding: 3rem;
                    border-radius: 16px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 400px;
                }

                h1 {
                    color: #10b981;
                    margin-bottom: 1rem;
                }

                p {
                    color: #64748b;
                    line-height: 1.5;
                }

                .btn {
                    margin-top: 1.5rem;
                    display: inline-block;
                    background: #4f46e5;
                    color: white;
                    padding: 0.75rem 1.5rem;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                }
            </style>
        </head>

        <body>
            <div class="card">
                <div style="font-size: 4rem;">✅</div>
                <h1>Thank You!</h1>
                <p>Your family details have been submitted successfully to the parish office.</p>
                <a href="public_survey.php" class="btn">Submit Another Family</a>
            </div>
        </body>

        </html>
        <?php
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: public_survey.php");
    exit;
}
?>