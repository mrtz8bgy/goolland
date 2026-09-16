/**
 * Goolland Admin Panel - JavaScript
 * Version: 2.0.0
 * Description: Admin panel functionality
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌿 Admin panel loaded successfully');
    
    // Initialize all components
    initSidebar();
    initMenu();
    initFormValidation();
    initSelectAll();
    initBulkActions();
    initDeleteConfirmations();
    initTooltips();
    initToast();
    initModal();
    initSortableTables();
    initCharacterCounter();
    initImagePreview();
});

// ============================================
// Sidebar Toggle
// ============================================

function initSidebar() {
    const sidebarToggle = document.getElementById('admin-sidebar-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    const sidebarClose = document.getElementById('admin-sidebar-close');
    const mainContent = document.querySelector('.admin-main');
    
    if (!sidebarToggle || !sidebar) return;
    
    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
    
    // Close sidebar on close button click
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && 
            !sidebarToggle.contains(e.target) && 
            !e.target.closest('.admin-sidebar')) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// ============================================
// Menu Toggle (for nested menus)
// ============================================

function initMenu() {
    const menuItems = document.querySelectorAll('.admin-menu-item');
    
    menuItems.forEach(item => {
        const link = item.querySelector('.admin-menu-link');
        const submenu = item.querySelector('.admin-submenu');
        
        if (link && submenu) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 1200) {
                    // On mobile, prevent default and toggle submenu
                    e.preventDefault();
                    item.classList.toggle('expanded');
                }
            });
        }
    });
}

// ============================================
// Form Validation
// ============================================

function initFormValidation() {
    const forms = document.querySelectorAll('.admin-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredInputs = form.querySelectorAll('[required]');
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('لطفاً تمام فیلدهای مورد نیاز را پر کنید', 'error');
            }
        });
        
        // Remove error class on input
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
            });
            
            input.addEventListener('change', function() {
                this.classList.remove('error');
            });
        });
    });
}

// ============================================
// Select All Checkbox
// ============================================

function initSelectAll() {
    const selectAllCheckboxes = document.querySelectorAll('.select-all');
    
    selectAllCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const table = this.closest('.admin-table-wrapper').querySelector('.admin-table');
            const checkboxes = table.querySelectorAll('input[type="checkbox"]:not(.select-all)');
            
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    });
}

// ============================================
// Bulk Actions
// ============================================

function initBulkActions() {
    const bulkActionForms = document.querySelectorAll('.bulk-action-form');
    
    bulkActionForms.forEach(form => {
        const selectAll = form.querySelector('.select-all');
        const actionSelect = form.querySelector('select[name="action"]');
        const applyBtn = form.querySelector('button[type="submit"]');
        
        if (actionSelect && applyBtn) {
            actionSelect.addEventListener('change', function() {
                applyBtn.disabled = !this.value;
            });
        }
        
        // Check if any checkbox is checked
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:not(.select-all)');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (selectAll) {
                    selectAll.checked = anyChecked && Array.from(checkboxes).every(cb => cb.checked);
                }
            });
        });
    });
}

// ============================================
// Delete Confirmations
// ============================================

function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.delete-btn, .btn-danger');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirmMessage || 'آیا از حذف این آیتم مطمئن هستید؟';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    });
}

// ============================================
// Tooltips
// ============================================

function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        const tooltip = document.createElement('div');
        tooltip.className = 'admin-tooltip';
        tooltip.textContent = element.dataset.tooltip;
        tooltip.style.cssText = `
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            
            background: var(--admin-bg-dark);
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            
            padding: 8px 12px;
            
            color: var(--admin-text);
            font-size: 12px;
            white-space: nowrap;
            
            z-index: 10000;
            
            opacity: 0;
            visibility: hidden;
            
            transition: opacity 0.2s ease, visibility 0.2s ease;
        `;
        
        element.style.position = 'relative';
        element.appendChild(tooltip);
        
        element.addEventListener('mouseenter', function() {
            tooltip.style.opacity = '1';
            tooltip.style.visibility = 'visible';
        });
        
        element.addEventListener('mouseleave', function() {
            tooltip.style.opacity = '0';
            tooltip.style.visibility = 'hidden';
        });
    });
}

// ============================================
// Toast Notifications
// ============================================

function initToast() {
    // Toast container
    const toastContainer = document.createElement('div');
    toastContainer.className = 'admin-toast-container';
    toastContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10001;
        display: flex;
        flex-direction: column;
        gap: 10px;
    `;
    document.body.appendChild(toastContainer);
}

function showToast(message, type = 'success', duration = 5000) {
    const toastContainer = document.querySelector('.admin-toast-container');
    
    if (!toastContainer) {
        initToast();
    }
    
    const toast = document.createElement('div');
    toast.className = `admin-toast ${type}`;
    
    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-exclamation-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };
    
    toast.innerHTML = `
        <span class="icon">${icons[type] || icons.success}</span>
        <span class="message">${message}</span>
        <button class="close">&times;</button>
    `;
    
    document.querySelector('.admin-toast-container').appendChild(toast);
    
    // Auto remove
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duration);
    
    // Close button
    const closeBtn = toast.querySelector('.close');
    closeBtn.addEventListener('click', function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    });
}

// ============================================
// Modal
// ============================================

function initModal() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.admin-modal-overlay');
    
    // Open modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Close modal
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.admin-modal-close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Close on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Close on escape
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
}

// ============================================
// Sortable Tables
// ============================================

function initSortableTables() {
    const tableHeaders = document.querySelectorAll('.admin-table th[data-sort]');
    
    tableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('.admin-table');
            const column = this.dataset.sort;
            const direction = this.dataset.direction === 'asc' ? 'desc' : 'asc';
            
            // Reset all headers
            table.querySelectorAll('th[data-sort]').forEach(th => {
                th.dataset.direction = '';
                th.classList.remove('sorted-asc', 'sorted-desc');
            });
            
            // Set current header
            this.dataset.direction = direction;
            this.classList.add(`sorted-${direction}`);
            
            // Sort table (this is a simple implementation)
            sortTable(table, column, direction);
        });
    });
}

function sortTable(table, columnIndex, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try to parse as number
        const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // String comparison
        return direction === 'asc' 
            ? aValue.localeCompare(bValue, 'fa') 
            : bValue.localeCompare(aValue, 'fa');
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// ============================================
// Character Counter
// ============================================

function initCharacterCounter() {
    const textareas = document.querySelectorAll('textarea[data-max-length]');
    
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxLength);
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = `
            color: var(--admin-text-muted);
            font-size: 12px;
            text-align: right;
            margin-top: 5px;
        `;
        
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;
            
            counter.textContent = `${remaining} کاراکتر باقی مانده`;
            
            if (remaining < 0) {
                counter.style.color = 'var(--admin-danger)';
            } else if (remaining < maxLength * 0.2) {
                counter.style.color = 'var(--admin-warning)';
            } else {
                counter.style.color = 'var(--admin-text-muted)';
            }
        });
        
        // Trigger initial count
        textarea.dispatchEvent(new Event('input'));
    });
}

// ============================================
// Image Preview
// ============================================

function initImagePreview() {
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    
    fileInputs.forEach(input => {
        const previewId = input.dataset.preview;
        const previewElement = document.getElementById(previewId);
        
        if (previewElement) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }
    });
}

// ============================================
// AJAX Form Submission
// ============================================

function submitFormAJAX(form, callback) {
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method || 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'عملیات با موفقیت انجام شد', 'success');
            if (callback) callback(data);
        } else {
            showToast(data.message || 'خطا در انجام عملیات', 'error');
        }
    })
    .catch(error => {
        showToast('خطا در اتصال به سرور', 'error');
        console.error('AJAX Error:', error);
    });
}

// ============================================
// Copy to Clipboard
// ============================================

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('کپی شد!', 'success', 2000);
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
}

// ============================================
// Format Price
// ============================================

function formatPrice(price) {
    return new Intl.NumberFormat('fa-IR').format(price);
}

// ============================================
// Format Date
// ============================================

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fa-IR');
}

// ============================================
// Export Functions
// ============================================

window.showToast = showToast;
window.copyToClipboard = copyToClipboard;
window.formatPrice = formatPrice;
window.formatDate = formatDate;
window.submitFormAJAX = submitFormAJAX;

console.log('🌿 All admin JavaScript components initialized');
