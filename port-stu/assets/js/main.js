/**
 * LMS Main JavaScript
 * AJAX helpers, form validation, progress tracking
 */

// AJAX Helper
const ajax = {
    post: async function (url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        return response.json();
    },

    get: async function (url) {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        return response.json();
    }
};

// Flash message auto-hide
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// Mark lesson as complete
async function markLessonComplete(lessonId, courseId) {
    try {
        const response = await ajax.post(BASE_PATH + '/api/progress.php', {
            action: 'complete',
            lesson_id: lessonId
        });

        if (response.success) {
            // Update UI
            const lessonItem = document.querySelector(`[data-lesson-id="${lessonId}"]`);
            if (lessonItem) {
                lessonItem.classList.add('completed');
            }

            // Update progress bar
            if (response.progress !== undefined) {
                updateProgressBar(courseId, response.progress);
            }

            return true;
        }
    } catch (error) {
        console.error('Error marking lesson complete:', error);
    }
    return false;
}

// Update progress bar
function updateProgressBar(courseId, progress) {
    const progressBar = document.querySelector(`[data-course-progress="${courseId}"]`);
    if (progressBar) {
        progressBar.style.width = progress + '%';
    }

    const progressText = document.querySelector(`[data-progress-text="${courseId}"]`);
    if (progressText) {
        progressText.textContent = progress + '%';
    }
}

// Form validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
        } else {
            input.classList.remove('error');
        }
    });

    return isValid;
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Debounce function
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

// Search filter
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    input.addEventListener('input', debounce(function () {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }, 300));
}
