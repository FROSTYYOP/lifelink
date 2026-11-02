    </main>

    <!-- Footer -->
    <footer class="footer-lifelink pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <!-- Brand & Mission -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-heart-pulse-fill text-danger fs-3"></i>
                        <h4 class="text-white mb-0 fw-bold">LifeLink</h4>
                    </div>
                    <p class="small text-secondary">
                        LifeLink connects voluntary blood donors directly with patients and critical emergency cases in real time. Designed for maximum speed, security, and community reliability.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-burgundy px-2 py-1"><i class="bi bi-shield-lock-fill me-1"></i>PDO Secure</span>
                        <span class="badge bg-secondary px-2 py-1"><i class="bi bi-code-slash me-1"></i>PHP & MySQL</span>
                        <span class="badge bg-success px-2 py-1"><i class="bi bi-lightning-charge-fill me-1"></i>Real-time</span>
                    </div>
                </div>

                <!-- Quick Navigation -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Platform</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= $rootPrefix ?>index.php">Home</a></li>
                        <li class="mb-2"><a href="<?= $rootPrefix ?>search.php">Find Donors</a></li>
                        <li class="mb-2"><a href="<?= $rootPrefix ?>request-blood.php" class="text-danger fw-semibold">Emergency Request</a></li>
                        <li class="mb-2"><a href="<?= $rootPrefix ?>about.php">Eligibility & FAQs</a></li>
                    </ul>
                </div>

                <!-- Access & Roles -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Portals</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= $rootPrefix ?>auth/login.php">Sign In</a></li>
                        <li class="mb-2"><a href="<?= $rootPrefix ?>auth/register.php">Register as Donor</a></li>
                        <li class="mb-2"><a href="<?= $rootPrefix ?>donor/dashboard.php">Donor Dashboard</a></li>
                        <li class="mb-2"><a href="<?= $rootPrefix ?>admin/dashboard.php">Admin Panel</a></li>
                    </ul>
                </div>

                <!-- Emergency Contact & Viva Info -->
                <div class="col-lg-4 col-md-6">
                    <h6 class="text-white fw-bold mb-3"><i class="bi bi-hospital me-1 text-danger"></i>Emergency Support</h6>
                    <div class="p-3 rounded bg-dark bg-opacity-50 border border-secondary border-opacity-25 mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone-fill text-danger fs-5 me-2"></i>
                            <div>
                                <div class="text-white fw-bold">1800-5433-5465 (24x7)</div>
                                <div class="text-secondary small">National Blood Response Line</div>
                            </div>
                        </div>
                        <div class="small text-secondary">
                            For critical requests, fill the online form and contact emergency triage immediately.
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-secondary my-4 opacity-25">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
                <div>
                    &copy; <?= date('Y') ?> <strong>LifeLink System</strong>. Built for College Academic & Mini-Project Demonstration.
                </div>
                <div class="mt-2 mt-md-0">
                    <span>Designed for zero-configuration local execution with PHP & MySQL.</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Behaviors -->
    <script src="<?= $rootPrefix ?>assets/js/main.js"></script>
</body>
</html>
