<?php
/**
 * LifeLink - About & Blood Compatibility Information
 */
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'About & Compatibility Guide - LifeLink';
$activeNav = 'about';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-4">
    <!-- Header banner -->
    <div class="text-center mb-5">
        <span class="badge bg-burgundy-subtle text-burgundy px-3 py-2 fw-bold text-uppercase mb-2">
            <i class="bi bi-info-circle-fill me-1"></i> Medical Information & Guidelines
        </span>
        <h1 class="fw-bold text-dark">About LifeLink</h1>
        <p class="text-muted col-lg-7 mx-auto">
            LifeLink is a centralized, community-driven emergency blood network created to eliminate emergency supply delays and connect voluntary donors with healthcare centers.
        </p>
    </div>

    <!-- Blood Compatibility Matrix Card -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-burgundy mb-0">
                <i class="bi bi-grid-3x3-gap-fill me-2"></i>Blood Type Compatibility Matrix
            </h5>
            <span class="badge bg-danger"><i class="bi bi-heart-pulse me-1"></i>Standard Transfusion Rules</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 text-start ps-4">Blood Group</th>
                            <th class="py-3">Can Donate Blood To</th>
                            <th class="py-3">Can Receive Blood From</th>
                            <th class="py-3">Special Classification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('O-') ?></td>
                            <td><span class="badge bg-success">Everyone (Universal Donor)</span></td>
                            <td><span class="badge bg-dark">O- only</span></td>
                            <td><span class="badge bg-danger">Universal Red Cell Donor</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('O+') ?></td>
                            <td>O+, A+, B+, AB+</td>
                            <td>O+, O-</td>
                            <td><span class="text-muted small">Most common blood type</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('A-') ?></td>
                            <td>A-, A+, AB-, AB+</td>
                            <td>A-, O-</td>
                            <td><span class="text-muted small">Rare blood type</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('A+') ?></td>
                            <td>A+, AB+</td>
                            <td>A+, A-, O+, O-</td>
                            <td><span class="text-muted small">High demand</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('B-') ?></td>
                            <td>B-, B+, AB-, AB+</td>
                            <td>B-, O-</td>
                            <td><span class="text-muted small">Rare blood type</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('B+') ?></td>
                            <td>B+, AB+</td>
                            <td>B+, B-, O+, O-</td>
                            <td><span class="text-muted small">Common blood type</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('AB-') ?></td>
                            <td>AB-, AB+</td>
                            <td>AB-, A-, B-, O-</td>
                            <td><span class="badge bg-warning text-dark">Rarest Blood Type (~1%)</span></td>
                        </tr>
                        <tr>
                            <td class="text-start ps-4 fw-bold text-danger"><?= blood_badge('AB+') ?></td>
                            <td>AB+ only</td>
                            <td><span class="badge bg-success">Everyone (Universal Recipient)</span></td>
                            <td><span class="badge bg-primary">Universal Recipient</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Eligibility Criteria -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm p-4">
                <h5 class="fw-bold text-burgundy mb-3"><i class="bi bi-check2-circle text-success me-2"></i>Who Can Donate Blood?</h5>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-lg text-success me-2 fs-5"></i>
                        <span>Age between <strong>18 and 65 years</strong>.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-lg text-success me-2 fs-5"></i>
                        <span>Weight is at least <strong>45 kg (100 lbs)</strong>.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-lg text-success me-2 fs-5"></i>
                        <span>Hemoglobin count is <strong>&ge; 12.5 g/dL</strong>.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-lg text-success me-2 fs-5"></i>
                        <span>At least <strong>90 days (3 months)</strong> have passed since your last whole blood donation.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-check-lg text-success me-2 fs-5"></i>
                        <span>Normal blood pressure, pulse, and body temperature.</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm p-4">
                <h5 class="fw-bold text-burgundy mb-3"><i class="bi bi-x-circle text-danger me-2"></i>Temporary / Permanent Deferrals</h5>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-dash-circle text-danger me-2 fs-5"></i>
                        <span>Active cold, flu, fever, or infectious sore throat on donation day.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-dash-circle text-danger me-2 fs-5"></i>
                        <span>Tattoo or body piercing done within the last 6 months.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-dash-circle text-danger me-2 fs-5"></i>
                        <span>Major surgery undergone in the past 6 to 12 months.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-dash-circle text-danger me-2 fs-5"></i>
                        <span>Currently pregnant or lactating.</span>
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-dash-circle text-danger me-2 fs-5"></i>
                        <span>History of cardiac conditions, Hepatitis B/C, or HIV.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="card shadow-sm border-0 mb-4 p-4">
        <h4 class="fw-bold text-dark mb-3"><i class="bi bi-question-diamond text-burgundy me-2"></i>Frequently Asked Questions</h4>
        <div class="accordion accordion-flush" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading1">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1">
                        How long does a blood donation take?
                    </button>
                </h2>
                <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        The actual blood drawing takes only about 8 to 10 minutes. The entire procedure, including health checkup, donor registration, and 15 minutes of resting and refreshments, takes roughly 45 minutes.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading2">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                        Why are contact numbers hidden for guest users?
                    </button>
                </h2>
                <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        To protect our volunteer donors against unsolicited telemarketing and privacy abuse, phone numbers are only displayed to authenticated members who are signed into the LifeLink platform.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="faqHeading3">
                    <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                        How are donor profiles verified?
                    </button>
                </h2>
                <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small">
                        When a volunteer registers as a donor, their account is flagged as "pending". LifeLink administrators verify basic donor criteria and phone authentication through the Admin Verification Portal before marking the donor as "verified".
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
