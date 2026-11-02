<?php
/**
 * LifeLink - Common Header & Navigation
 */
require_once __DIR__ . '/functions.php';

// Determine relative path prefix to root
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isSubdir = preg_match('#/(auth|donor|admin|public)/#i', $scriptName);
$rootPrefix = $isSubdir ? '../' : './';

$user = current_user();
$pageTitle = $pageTitle ?? 'LifeLink - Smart Blood Donor & Emergency Assistance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon: Medical Blood Droplet -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🩸</text></svg>">

    <!-- OpenGraph / Social Share Meta Tags -->
    <meta property="og:title" content="LifeLink - Voluntary Blood Donor & Emergency Assistance">
    <meta property="og:description" content="Connecting voluntary blood donors with patients in urgent need. Real-time emergency requests, WhatsApp SOS, and donor privacy shield.">
    <meta property="og:type" content="website">

    <!-- Custom Burgundy Theme -->
    <link rel="stylesheet" href="<?= $rootPrefix ?>assets/css/style.css">
</head>
<body>

    <!-- Emergency Top Banner -->
    <div class="bg-burgundy py-1 px-3 text-white small d-flex justify-content-between align-items-center">
        <div class="container d-flex justify-content-between align-items-center">
            <span><i class="bi bi-telephone-inbound-fill me-1"></i> Emergency Blood Helpline: <strong>1800-LIFE-LINK (1800-5433-5465)</strong></span>
            <span class="d-none d-md-inline"><i class="bi bi-shield-check me-1"></i> 100% Free & Verified Community Platform</span>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-lifelink sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= $rootPrefix ?>index.php">
                <i class="bi bi-heart-pulse-fill text-burgundy fs-3"></i>
                <span>Life<span class="text-burgundy">Link</span></span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-dismiss="collapse" data-bs-target="#lifelinkNav" aria-controls="lifelinkNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="lifelinkNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>" href="<?= $rootPrefix ?>index.php">
                            <i class="bi bi-house-door me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'search' ? 'active' : '' ?>" href="<?= $rootPrefix ?>search.php">
                            <i class="bi bi-search me-1"></i>Find Donors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-semibold <?= ($activeNav ?? '') === 'request' ? 'active' : '' ?>" href="<?= $rootPrefix ?>request-blood.php">
                            <i class="bi bi-exclamation-octagon-fill me-1 text-danger"></i>Request Blood
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeNav ?? '') === 'about' ? 'active' : '' ?>" href="<?= $rootPrefix ?>about.php">
                            <i class="bi bi-info-circle me-1"></i>About
                        </a>
                    </li>
                </ul>

                <!-- User Session / Action Buttons -->
                <div class="d-flex align-items-center gap-2">
                    <?php if ($user): ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <div class="btn-group">
                                <a href="<?= $rootPrefix ?>admin/dashboard.php" class="btn btn-sm btn-outline-burgundy">
                                    <i class="bi bi-speedometer2 me-1"></i>Admin Panel
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-burgundy dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item" href="<?= $rootPrefix ?>admin/dashboard.php"><i class="bi bi-grid me-2"></i>Overview</a></li>
                                    <li><a class="dropdown-item" href="<?= $rootPrefix ?>admin/verify-donors.php"><i class="bi bi-patch-check me-2"></i>Verify Donors</a></li>
                                    <li><a class="dropdown-item" href="<?= $rootPrefix ?>admin/manage-requests.php"><i class="bi bi-droplet-half me-2"></i>Manage Requests</a></li>
                                    <li><a class="dropdown-item" href="<?= $rootPrefix ?>admin/users.php"><i class="bi bi-people me-2"></i>User Directory</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= $rootPrefix ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                                </ul>
                            </div>
                        <?php elseif ($user['role'] === 'donor'): ?>
                            <div class="btn-group">
                                <a href="<?= $rootPrefix ?>donor/dashboard.php" class="btn btn-sm btn-burgundy">
                                    <i class="bi bi-person-circle me-1"></i>Donor Portal
                                </a>
                                <button type="button" class="btn btn-sm btn-burgundy dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item" href="<?= $rootPrefix ?>donor/dashboard.php"><i class="bi bi-speedometer me-2"></i>Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= $rootPrefix ?>donor/profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= $rootPrefix ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <!-- Regular User -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-fill me-1"></i><?= e($user['name']) ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><span class="dropdown-item-text text-muted small"><i class="bi bi-envelope me-1"></i><?= e($user['email']) ?></span></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= $rootPrefix ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?= $rootPrefix ?>auth/login.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                        </a>
                        <a href="<?= $rootPrefix ?>auth/register.php" class="btn btn-sm btn-burgundy">
                            <i class="bi bi-person-plus-fill me-1"></i>Join as Donor
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <main class="py-4">
        <div class="container">
            <?php display_flash(); ?>
        </div>
