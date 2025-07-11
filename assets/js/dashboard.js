/**
 * Dashboard JavaScript
 * Bevat functionaliteit voor zoeken en verwijderen van lessen
 */

document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const tableBody = document.querySelector('tbody');
    
    if (searchInput && searchBtn) {
        // Search on button click
        searchBtn.addEventListener('click', performSearch);
        
        // Search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Real-time search (optional)
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const rows = tableBody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        const noResultsMsg = document.querySelector('.no-results-message');
        
        if (visibleRows.length === 0 && searchTerm !== '') {
            if (!noResultsMsg) {
                const msg = document.createElement('tr');
                msg.className = 'no-results-message';
                msg.innerHTML = '<td colspan="7" class="text-center text-muted py-4">Geen lessen gevonden</td>';
                tableBody.appendChild(msg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
    
    // Delete lesson functionality
    window.deleteLesson = function(lessonId) {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const confirmBtn = document.getElementById('confirmDelete');
        
        confirmBtn.onclick = function() {
            // Show loading state
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verwijderen...';
            confirmBtn.disabled = true;
            
            // Send delete request
            fetch('api/les_verwijderen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    les_id: lessonId,
                    csrf_token: getCSRFToken()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove row from table
                    const row = document.querySelector(`tr[data-lesson-id="${lessonId}"]`);
                    if (row) {
                        row.remove();
                    }
                    
                    // Show success message
                    showAlert('Les succesvol verwijderd!', 'success');
                    
                    // Close modal
                    modal.hide();
                } else {
                    showAlert(data.error || 'Er is een fout opgetreden.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Er is een fout opgetreden bij het verwijderen van de les.', 'danger');
            })
            .finally(() => {
                // Reset button
                confirmBtn.innerHTML = 'Verwijderen';
                confirmBtn.disabled = false;
            });
        };
        
        modal.show();
    };
    
    // Helper function to get CSRF token
    function getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }
    
    // Helper function to show alerts
    function showAlert(message, type) {
        const alertContainer = document.createElement('div');
        alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
        alertContainer.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container');
        const firstChild = container.firstChild;
        container.insertBefore(alertContainer, firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertContainer.parentNode) {
                alertContainer.remove();
            }
        }, 5000);
    }
    
    // Debounce function for search
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Add data attributes to table rows for delete functionality
    const lessonRows = document.querySelectorAll('tbody tr');
    lessonRows.forEach(row => {
        const deleteBtn = row.querySelector('.btn-outline-danger');
        if (deleteBtn) {
            const lessonId = deleteBtn.getAttribute('onclick').match(/\d+/)[0];
            row.setAttribute('data-lesson-id', lessonId);
        }
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
}); 