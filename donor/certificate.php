<?php
/**
 * LifeLink - Donor Certificate of Appreciation
 * Printable & Downloadable PDF/Card for Donors
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

require_roles('donor', 'admin');

$userId = (int) $_SESSION['user_id'];

// If admin is viewing, allow passing donor_id query param
if (has_role('admin') && isset($_GET['donor_id'])) {
    $stmt = $pdo->prepare("SELECT d.*, u.name, u.email, u.phone 
                           FROM donors d 
                           JOIN users u ON d.user_id = u.id 
                           WHERE d.id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['donor_id']]);
} else {
    $stmt = $pdo->prepare("SELECT d.*, u.name, u.email, u.phone 
                           FROM donors d 
                           JOIN users u ON d.user_id = u.id 
                           WHERE d.user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
}

$donor = $stmt->fetch();

if (!$donor) {
    set_flash('danger', 'Donor record not found.');
    header('Location: dashboard.php');
    exit;
}

$certId = 'LL-' . date('Y', strtotime($donor['created_at'])) . '-' . str_pad($donor['id'], 5, '0', STR_PAD_LEFT);
$issueDate = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLink Donor Certificate - <?= e($donor['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        .cert-container {
            max-width: 900px;
            margin: 40px auto;
        }
        .cert-frame {
            background: #ffffff;
            border: 12px double #800020;
            padding: 45px 55px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            border-radius: 4px;
        }
        .cert-frame::before {
            content: '';
            position: absolute;
            top: 6px; left: 6px; right: 6px; bottom: 6px;
            border: 2px solid #b3002d;
            pointer-events: none;
        }
        .cert-title {
            font-family: 'Cinzel', serif;
            color: #800020;
            letter-spacing: 2px;
            font-weight: 700;
        }
        .cert-recipient {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            color: #1e293b;
            border-bottom: 2px solid #800020;
            display: inline-block;
            padding: 0 30px 5px 30px;
            margin: 15px 0;
            font-weight: 700;
        }
        .cert-seal {
            width: 110px;
            height: 110px;
            border: 3px dashed #800020;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #800020;
            margin: 0 auto;
            background: #fff0f3;
        }
        .signature-line {
            width: 180px;
            border-bottom: 1px solid #64748b;
            margin: 0 auto 6px auto;
        }
        @media (max-width: 600px) {
            .cert-container { margin: 15px auto; }
            .cert-frame { padding: 25px 15px; border-width: 6px; }
            .cert-recipient { font-size: 1.4rem; padding: 0 10px 5px 10px; }
            .cert-title { font-size: 1.3rem; }
            .cert-seal { width: 85px; height: 85px; }
        }
        @media print {
            body { background: #ffffff; }
            .no-print { display: none !important; }
            .cert-container { margin: 0; max-width: 100%; }
            .cert-frame { box-shadow: none; border-width: 8px; padding: 30px; }
        }
    </style>
</head>
<body>

    <div class="cert-container">
        <!-- Controls Bar -->
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-danger" style="background-color: #800020; border-color: #800020;">
                    <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
                </button>
            </div>
        </div>

        <!-- Printable Certificate Card -->
        <div class="cert-frame text-center">
            <!-- Header Icon & Logo -->
            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                <i class="bi bi-heart-pulse-fill text-danger fs-1"></i>
                <h3 class="mb-0 fw-bold text-dark">Life<span style="color:#800020;">Link</span></h3>
            </div>
            <div class="text-uppercase small tracking-wide text-muted fw-bold mb-4">
                National Voluntary Blood Donor & Emergency Assistance Network
            </div>

            <!-- Title -->
            <h1 class="cert-title mb-2 fs-2">Certificate of Appreciation</h1>
            <p class="text-muted fst-italic">This honor is proudly presented to</p>

            <!-- Recipient Name -->
            <div>
                <div class="cert-recipient"><?= e($donor['name']) ?></div>
            </div>

            <!-- Body Paragraph -->
            <p class="col-lg-10 mx-auto text-secondary leading-relaxed mb-4 mt-2">
                In sincere recognition of your exemplary dedication and humanitarian commitment as a voluntary blood donor with Blood Group <strong class="text-danger fw-bold fs-5">[ <?= e($donor['blood_group']) ?> ]</strong> in <strong class="text-dark"><?= e($donor['city']) ?></strong>. Your readiness to donate and save lives brings hope to patients and families in emergency need.
            </p>

            <!-- Key Details Row -->
            <div class="row align-items-center justify-content-between mt-5 pt-3">
                <div class="col-4 text-center">
                    <div class="signature-line"></div>
                    <div class="fw-bold text-dark small">Dr. Rajesh V. Patil</div>
                    <div class="text-muted" style="font-size: 0.75rem;">Medical Director & Triage Board</div>
                </div>

                <div class="col-4 text-center">
                    <div class="cert-seal shadow-sm">
                        <i class="bi bi-patch-check-fill fs-3 mb-1"></i>
                        <span style="font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px;">VERIFIED DONOR</span>
                    </div>
                </div>

                <div class="col-4 text-center">
                    <div class="signature-line"></div>
                    <div class="fw-bold text-dark small"><?= $issueDate ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;">Issue Date & Authorization</div>
                </div>
            </div>

            <!-- Footer Certificate ID -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top small text-muted">
                <span>Certificate ID: <strong><?= $certId ?></strong></span>
                <span>Verification: <strong>https://lifelink.org/verify</strong></span>
                <span>Status: <span class="badge bg-success">Active Donor</span></span>
            </div>
        </div>
    </div>

</body>
</html>
