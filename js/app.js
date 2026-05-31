// ============================================================
// app.js - Student Clearance Processing System
// Author: Gideon Agtas
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- SIDEBAR TOGGLE ----
    const sidebar = document.getElementById('sidebar');
    const navbar = document.querySelector('.top-navbar');
    const mainContent = document.querySelector('.main-content');
    const footer = document.querySelector('.main-footer');
    const toggleBtn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    function isMobile() { return window.innerWidth <= 768; }

    function openSidebar() {
        sidebar && sidebar.classList.add('mobile-open');
        backdrop && backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    window.closeSidebar = function() {
        sidebar && sidebar.classList.remove('mobile-open');
        backdrop && backdrop.classList.remove('active');
        document.body.style.overflow = '';
    };

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (isMobile()) {
                if (sidebar && sidebar.classList.contains('mobile-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                sidebar && sidebar.classList.toggle('collapsed');
                navbar && navbar.classList.toggle('collapsed');
                mainContent && mainContent.classList.toggle('collapsed');
                footer && footer.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
        });
    }

    // Close sidebar on window resize to desktop
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            closeSidebar();
        }
    });

    // Restore sidebar state
    if (!isMobile() && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar && sidebar.classList.add('collapsed');
        navbar && navbar.classList.add('collapsed');
        mainContent && mainContent.classList.add('collapsed');
        footer && footer.classList.add('collapsed');
    }

    // Mobile overlay close
    document.addEventListener('click', function (e) {
        if (isMobile() && sidebar && sidebar.classList.contains('mobile-open')) {
            if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('mobile-open');
            }
        }
    });

    // ---- SEARCH/FILTER TABLE ----
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#dataTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    }

    // ---- TOAST NOTIFICATION ----
    window.showToast = function (message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const icons = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
        const toast = document.createElement('div');
        toast.className = `toast toast-custom align-items-center text-white bg-${type} show`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${icons[type] || 'info-circle-fill'} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };

    // Show flash toast from URL params
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success')) showToast(decodeURIComponent(urlParams.get('success')), 'success');
    if (urlParams.get('error')) showToast(decodeURIComponent(urlParams.get('error')), 'danger');

    // ---- CONFIRM DELETE ----
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ---- AUTO-DISMISS ALERTS ----
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(a => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(a);
            bsAlert.close();
        });
    }, 4000);

    // ---- PAGINATION ----
    const rowsPerPage = 10;
    const tables = document.querySelectorAll('.paginated-table');
    tables.forEach(table => {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        let currentPage = 1;
        const totalPages = () => Math.ceil(rows.filter(r => r.style.display !== 'none').length / rowsPerPage);

        function renderPage() {
            const visible = rows.filter(r => r.style.display !== 'none');
            visible.forEach((r, i) => {
                r.style.display = (i >= (currentPage - 1) * rowsPerPage && i < currentPage * rowsPerPage) ? '' : 'none';
            });
        }

        const pager = table.closest('.table-card')?.querySelector('.pagination-container');
        if (pager) {
            pager.querySelectorAll('[data-page]').forEach(btn => {
                btn.addEventListener('click', function () {
                    currentPage = parseInt(this.dataset.page);
                    renderPage();
                });
            });
        }
    });
});

// ---- CHARTS INIT (called from dashboard pages) ----
function initDoughnutChart(id, labels, data, colors) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14, font: { size: 12, family: 'Plus Jakarta Sans' } } }
            },
            cutout: '65%'
        }
    });
}

function initBarChart(id, labels, datasets) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 12, family: 'Plus Jakarta Sans' } } } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 } } },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
}