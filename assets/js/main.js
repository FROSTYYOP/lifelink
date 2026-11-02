/**
 * LifeLink - Client-side Interactive Behaviors
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Bootstrap Client-Side Validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 2. Auto-dismiss alerts after 6 seconds
    const flashAlerts = document.querySelectorAll('.alert-dismissible');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } catch (e) {}
        }, 6000);
    });

    // 3. Password visibility toggle
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.querySelector(this.getAttribute('data-target'));
            if (input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-eye');
                    icon.classList.toggle('bi-eye-slash');
                }
            }
        });
    });

    // 4. Client-side Instant Filter for Donor directory
    const liveFilterInput = document.getElementById('liveDonorCityFilter');
    const liveGroupFilter = document.getElementById('liveDonorBloodFilter');
    const donorCards = document.querySelectorAll('.donor-item-card');

    function applyLiveFilter() {
        if (!donorCards.length) return;
        const cityVal = (liveFilterInput ? liveFilterInput.value : '').toLowerCase().trim();
        const bloodVal = (liveGroupFilter ? liveGroupFilter.value : '').toUpperCase().trim();

        let visibleCount = 0;
        donorCards.forEach(card => {
            const cardCity = (card.getAttribute('data-city') || '').toLowerCase();
            const cardBlood = (card.getAttribute('data-blood') || '').toUpperCase();

            const matchCity = !cityVal || cardCity.includes(cityVal);
            const matchBlood = !bloodVal || cardBlood === bloodVal;

            if (matchCity && matchBlood) {
                card.closest('.donor-col').style.display = '';
                visibleCount++;
            } else {
                card.closest('.donor-col').style.display = 'none';
            }
        });

        const noResultsEl = document.getElementById('noDonorsFoundMsg');
        if (noResultsEl) {
            noResultsEl.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (liveFilterInput) {
        liveFilterInput.addEventListener('input', applyLiveFilter);
    }
    if (liveGroupFilter) {
        liveGroupFilter.addEventListener('change', applyLiveFilter);
    }

    // 5. Confirmation helper for delete/critical action buttons
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
