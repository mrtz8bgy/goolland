/**
 * Goolland Admin Panel
 * Main JavaScript File
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initSidebar();
    initDropdowns();
    initTabs();
    initFormValidation();
    initDataTables();
    initCharts();
    initImageUpload();
    initNotifications();
    initModal();
    initDatePicker();
    initSelect2();
    initEditor();
});

/**
 * Sidebar Toggle
 */
function initSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    
    if (!sidebarToggle || !sidebar) return;
    
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });
    
    // Restore state from localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
        main.classList.add('expanded');
    }
    
    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 1024) {
            sidebar.classList.remove('collapsed');
            main.classList.remove('expanded');
        }
    });
    
    // Close sidebar on mobile when clicking outside
    if (window.innerWidth <= 1024) {
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
}

/**
 * Dropdown Menus
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.admin-sidebar .has-submenu');
    
    dropdowns.forEach(dropdown => {
        const link = dropdown.querySelector('a');
        const submenu = dropdown.querySelector('.nav-submenu');
        
        if (link && submenu) {
            // Click to toggle on mobile
            link.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024) {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                }
            });
            
            // Hover on desktop
            dropdown.addEventListener('mouseenter', () => {
                if (window.innerWidth > 1024) {
                    dropdown.classList.add('active');
                }
            });
            
            dropdown.addEventListener('mouseleave', () => {
                if (window.innerWidth > 1024) {
                    dropdown.classList.remove('active');
                }
            });
        }
    });
}

/**
 * Tabs
 */
function initTabs() {
    // Settings tabs
    const settingsTabs = document.querySelectorAll('.settings-tabs .tab-btn');
    const settingsContents = document.querySelectorAll('.settings-tabs-content .tab-content');
    
    if (settingsTabs.length && settingsContents.length) {
        settingsTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active from all
                settingsTabs.forEach(t => t.classList.remove('active'));
                settingsContents.forEach(c => c.classList.remove('active'));
                
                // Add active to clicked
                tab.classList.add('active');
                const targetId = tab.dataset.tab;
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }
    
    // Product tabs
    const productTabs = document.querySelectorAll('.product-tabs .tab-btn');
    const productContents = document.querySelectorAll('.product-tabs-content .tab-content');
    
    if (productTabs.length && productContents.length) {
        productTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active from all
                productTabs.forEach(t => t.classList.remove('active'));
                productContents.forEach(c => c.classList.remove('active'));
                
                // Add active to clicked
                tab.classList.add('active');
                const targetId = tab.dataset.tab;
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }
}

/**
 * Form Validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            
            // Validate required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showFieldError(field, 'این فیلد الزامی است');
                } else {
                    clearFieldError(field);
                }
            });
            
            // Validate email fields
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    showFieldError(field, 'ایمیل معتبر نیست');
                }
            });
            
            // Validate password match
            const passwordField = form.querySelector('input[name="password"]');
            const confirmPasswordField = form.querySelector('input[name="confirm_password"]');
            
            if (passwordField && confirmPasswordField && passwordField.value) {
                if (passwordField.value !== confirmPasswordField.value) {
                    isValid = false;
                    showFieldError(confirmPasswordField, 'رمز عبور با تکرار آن مطابقت ندارد');
                }
            }
            
            // Validate numeric fields
            const numericFields = form.querySelectorAll('input[type="number"]');
            numericFields.forEach(field => {
                if (field.value && isNaN(parseFloat(field.value))) {
                    isValid = false;
                    showFieldError(field, 'مقدار باید عدد باشد');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Focus on first error
                const firstError = form.querySelector('.error-message');
                if (firstError) {
                    const field = firstError.previousElementSibling;
                    if (field && field.focus) {
                        field.focus();
                    }
                }
            }
        });
        
        // Clear error on input
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearFieldError(input);
            });
            
            input.addEventListener('blur', () => {
                if (input.required && !input.value.trim()) {
                    showFieldError(input, 'این فیلد الزامی است');
                } else if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
                    showFieldError(input, 'ایمیل معتبر نیست');
                } else {
                    clearFieldError(input);
                }
            });
        });
    });
    
    function showFieldError(field, message) {
        field.style.borderColor = 'var(--error-color)';
        
        let errorElement = field.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('small');
            errorElement.className = 'error-message';
            errorElement.style.cssText = 'color: var(--error-color); font-size: 0.75rem; margin-top: 0.25rem; display: block;';
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }
        errorElement.textContent = message;
    }
    
    function clearFieldError(field) {
        field.style.borderColor = '';
        const errorElement = field.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
        }
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
}

/**
 * Data Tables
 */
function initDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add checkbox select all functionality
        const selectAll = table.querySelector('.select-all');
        const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
        
        if (selectAll && checkboxes.length) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
            });
        }
        
        // Row selection
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
                if (selectAll) {
                    selectAll.checked = checkedCount === checkboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                }
            });
        });
        
        // Row actions
        const actionButtons = table.querySelectorAll('.action-btn');
        actionButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const action = button.dataset.action;
                const id = button.dataset.id;
                
                if (action === 'delete') {
                    if (confirm('آیا از حذف این آیتم اطمینان دارید؟')) {
                        // Submit delete form or AJAX request
                        const form = button.closest('form');
                        if (form) {
                            form.submit();
                        }
                    }
                }
            });
        });
        
        // Bulk actions
        const bulkActionSelect = table.querySelector('.bulk-action-select');
        const bulkActionForm = table.querySelector('.bulk-action-form');
        
        if (bulkActionSelect && bulkActionForm) {
            bulkActionSelect.addEventListener('change', () => {
                if (bulkActionSelect.value) {
                    bulkActionForm.submit();
                }
            });
        }
    });
}

/**
 * Charts (Simple implementation - replace with Chart.js or similar)
 */
function initCharts() {
    const chartElements = document.querySelectorAll('.chart');
    
    chartElements.forEach(chart => {
        // This is a placeholder - implement with actual charting library
        console.log('Initialize chart:', chart.dataset.type);
    });
}

/**
 * Image Upload
 */
function initImageUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const files = e.target.files;
            if (!files.length) return;
            
            const previewContainer = input.parentElement.querySelector('.image-preview');
            if (!previewContainer) return;
            
            // Clear previous previews
            previewContainer.innerHTML = '';
            
            // Show loading
            const loading = document.createElement('div');
            loading.className = 'image-loading';
            loading.innerHTML = '<div class="spinner"></div>';
            previewContainer.appendChild(loading);
            
            // Create preview for each file
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                
                reader.onload = (event) => {
                    loading.remove();
                    
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.style.maxWidth = '150px';
                    img.style.maxHeight = '150px';
                    img.style.margin = '5px';
                    img.style.borderRadius = '4px';
                    
                    previewContainer.appendChild(img);
                };
                
                reader.readAsDataURL(file);
            });
        });
    });
}

/**
 * Notifications
 */
function initNotifications() {
    // Close notification buttons
    const closeButtons = document.querySelectorAll('.alert .close');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.parentElement.remove();
        });
    });
    
    // Auto close notifications
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
}

/**
 * Show notification
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button class="close">&times;</button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    // Set background based on type
    switch(type) {
        case 'success':
            notification.style.background = '#4caf50';
            notification.style.color = 'white';
            break;
        case 'error':
            notification.style.background = '#f44336';
            notification.style.color = 'white';
            break;
        case 'warning':
            notification.style.background = '#ff9800';
            notification.style.color = 'white';
            break;
        default:
            notification.style.background = '#2196f3';
            notification.style.color = 'white';
    }
    
    document.body.appendChild(notification);
    
    // Close button
    const closeBtn = notification.querySelector('.close');
    closeBtn.addEventListener('click', () => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Auto close after 5 seconds
    setTimeout(() => {
        closeBtn.click();
    }, 5000);
}

/**
 * Modal
 */
function initModal() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.modal-close');
    const overlays = document.querySelectorAll('.modal-overlay');
    
    // Open modal
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Close modal
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close on overlay click
    overlays.forEach(overlay => {
        overlay.addEventListener('click', () => {
            const modal = overlay.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                if (modal.classList.contains('active')) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }
    });
}

/**
 * Date Picker (Simple implementation)
 */
function initDatePicker() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    // Convert to Persian date picker if library is available
    dateInputs.forEach(input => {
        // This is a placeholder - implement with actual date picker library
        console.log('Initialize date picker for:', input);
    });
}

/**
 * Select2 (Enhanced select boxes)
 */
function initSelect2() {
    const selectElements = document.querySelectorAll('select[data-select2]');
    
    // This is a placeholder - implement with actual Select2 library
    selectElements.forEach(select => {
        console.log('Initialize Select2 for:', select);
    });
}

/**
 * Rich Text Editor
 */
function initEditor() {
    const editors = document.querySelectorAll('.rich-text-editor');
    
    // This is a placeholder - implement with actual editor library
    editors.forEach(editor => {
        console.log('Initialize editor for:', editor);
    });
}

/**
 * Status Badge Color
 */
function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'active':
        case 'completed':
        case 'published':
            return '#4caf50';
        case 'pending':
        case 'draft':
            return '#ff9800';
        case 'inactive':
        case 'cancelled':
        case 'deleted':
            return '#f44336';
        case 'processing':
        case 'shipped':
            return '#2196f3';
        default:
            return '#9e9e9e';
    }
}

/**
 * Copy to Clipboard
 */
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showNotification('با موفقیت کپی شد', 'success');
        }
    } catch (err) {
        showNotification('خطا در کپی کردن', 'error');
    }
    
    document.body.removeChild(textarea);
}

/**
 * Export to CSV
 */
function exportToCSV(data, filename) {
    let csv = '';
    
    // Add headers
    if (data.length > 0) {
        const headers = Object.keys(data[0]);
        csv += headers.join(',') + '\n';
    }
    
    // Add rows
    data.forEach(row => {
        const values = Object.values(row);
        csv += values.map(value => {
            if (typeof value === 'string' && value.includes(',')) {
                return `"${value}"`;
            }
            return value;
        }).join(',') + '\n';
    });
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'export.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

/**
 * Toggle Sidebar Submenu
 */
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    if (submenu && submenu.classList.contains('nav-submenu')) {
        submenu.classList.toggle('active');
        element.parentElement.classList.toggle('active');
    }
}

/**
 * Delete Confirmation
 */
function confirmDelete(message = 'آیا از حذف این آیتم اطمینان دارید؟', callback) {
    if (confirm(message)) {
        if (callback && typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
}

/**
 * Bulk Action Confirmation
 */
function confirmBulkAction(message = 'آیا از انجام این عمل روی آیتم‌های انتخابی اطمینان دارید؟') {
    const selected = document.querySelectorAll('input[type="checkbox"]:checked');
    if (selected.length === 0) {
        showNotification('هیچ آیتمی انتخاب نشده است', 'warning');
        return false;
    }
    return confirm(message);
}

/**
 * Show Loading
 */
function showLoading(element) {
    if (!element) return;
    
    const originalContent = element.innerHTML;
    element.innerHTML = '<div class="loading-spinner"></div>';
    element.disabled = true;
    element.dataset.originalContent = originalContent;
}

/**
 * Hide Loading
 */
function hideLoading(element) {
    if (!element || !element.dataset.originalContent) return;
    
    element.innerHTML = element.dataset.originalContent;
    element.disabled = false;
    delete element.dataset.originalContent;
}

/**
 * Format Number with Commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format Price
 */
function formatPrice(price) {
    return formatNumber(price) + ' تومان';
}

/**
 * Format Date
 */
function formatDate(dateString, format = 'YYYY/MM/DD') {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day);
}

/**
 * Convert to Persian Numbers
 */
function toPersianNumbers(num) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return num.toString().replace(/\d/g, digit => persianDigits[parseInt(digit)]);
}

/**
 * Convert Persian Numbers to English
 */
function toEnglishNumbers(str) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return str.replace(/[۰-۹]/g, digit => persianDigits.indexOf(digit));
}

/**
 * Debounce Function
 */
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

/**
 * Throttle Function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * AJAX Request Helper
 */
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        data: null,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: null,
        error: null,
        complete: null
    };
    
    options = { ...defaults, ...options };
    
    const xhr = new XMLHttpRequest();
    
    xhr.open(options.method, url);
    
    // Set headers
    Object.keys(options.headers).forEach(key => {
        xhr.setRequestHeader(key, options.headers[key]);
    });
    
    xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (options.success) {
                    options.success(response, xhr);
                }
            } catch (e) {
                if (options.success) {
                    options.success(xhr.responseText, xhr);
                }
            }
        } else {
            if (options.error) {
                options.error(xhr, xhr.status, xhr.statusText);
            }
        }
        
        if (options.complete) {
            options.complete(xhr, xhr.status);
        }
    };
    
    xhr.onerror = () => {
        if (options.error) {
            options.error(xhr, 0, 'Network Error');
        }
        if (options.complete) {
            options.complete(xhr, 0);
        }
    };
    
    xhr.send(options.data ? JSON.stringify(options.data) : null);
}

// Console welcome message
console.log('%c🌿 پنل ادمین گولند %c🌸', 'color: #2e7d32; font-size: 16px; font-weight: bold;', 'color: #ff9800; font-size: 16px;');
console.log('%cبه پنل مدیریت فروشگاه گل و گیاه خوش آمدید', 'color: #333;');
