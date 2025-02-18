// Loading göstergesi
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.remove();
    }
}

// Toast mesajı göster
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="toast-body">
            ${message}
        </div>
    `;
    document.body.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Tablo sıralama
document.querySelectorAll('th[data-sort]').forEach(header => {
    header.addEventListener('click', () => {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const index = Array.from(header.parentNode.children).indexOf(header);
        const direction = header.classList.contains('asc') ? -1 : 1;

        // Sıralama yönünü güncelle
        header.closest('tr').querySelectorAll('th').forEach(th => {
            th.classList.remove('asc', 'desc');
        });
        header.classList.add(direction === 1 ? 'asc' : 'desc');

        // Sırala
        rows.sort((a, b) => {
            const aValue = a.children[index].textContent;
            const bValue = b.children[index].textContent;
            return direction * aValue.localeCompare(bValue, 'tr');
        });

        // DOM'u güncelle
        tbody.append(...rows);
    });
});

// Toplu işlem seçimi
document.querySelectorAll('.select-all').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        const table = checkbox.closest('table');
        table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.checked = checkbox.checked;
        });
    });
});

// Toplu işlem butonları
document.querySelectorAll('[data-bulk-action]').forEach(button => {
    button.addEventListener('click', async () => {
        const action = button.dataset.bulkAction;
        const table = button.closest('.table-responsive').querySelector('table');
        const selectedIds = Array.from(table.querySelectorAll('tbody input[type="checkbox"]:checked'))
            .map(cb => cb.value);

        if (selectedIds.length === 0) {
            showToast('Lütfen en az bir öğe seçin', 'warning');
            return;
        }

        if (!confirm('Seçili öğeler üzerinde bu işlemi gerçekleştirmek istediğinizden emin misiniz?')) {
            return;
        }

        showLoading();

        try {
            const response = await fetch('/api/admin/bulk-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action,
                    ids: selectedIds
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message);
                // Sayfayı yenile
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            showToast('Bir hata oluştu', 'error');
        } finally {
            hideLoading();
        }
    });
});

// Arama filtresi
document.querySelectorAll('.search-input').forEach(input => {
    input.addEventListener('input', () => {
        const table = input.closest('.table-responsive').querySelector('table');
        const searchText = input.value.toLowerCase();

        table.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });
});

// Tarih filtresi
document.querySelectorAll('.date-filter').forEach(select => {
    select.addEventListener('change', () => {
        const table = select.closest('.table-responsive').querySelector('table');
        const days = parseInt(select.value);

        if (days === 0) {
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = '';
            });
            return;
        }

        const cutoff = new Date();
        cutoff.setDate(cutoff.getDate() - days);

        table.querySelectorAll('tbody tr').forEach(row => {
            const dateCell = row.querySelector('td[data-date]');
            if (dateCell) {
                const date = new Date(dateCell.dataset.date);
                row.style.display = date >= cutoff ? '' : 'none';
            }
        });
    });
});

// Grafik oluşturma
function createChart(canvas, data) {
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: data.type || 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...data.options
        }
    });
}

// Sayfa yüklendiğinde grafikleri oluştur
document.addEventListener('DOMContentLoaded', () => {
    // Kullanıcı istatistikleri grafiği
    const userStatsCanvas = document.getElementById('userStatsChart');
    if (userStatsCanvas) {
        createChart(userStatsCanvas, {
            type: 'line',
            labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
            datasets: [{
                label: 'Yeni Kullanıcılar',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        });
    }

    // İçerik istatistikleri grafiği
    const contentStatsCanvas = document.getElementById('contentStatsChart');
    if (contentStatsCanvas) {
        createChart(contentStatsCanvas, {
            type: 'bar',
            labels: ['Konular', 'Yorumlar', 'Şikayetler'],
            datasets: [{
                label: 'Son 7 Gün',
                data: [65, 59, 80],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        });
    }
}); 