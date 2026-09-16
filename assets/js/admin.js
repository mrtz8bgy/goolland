/**
 * Admin Panel - Main JavaScript
 * Handles sidebar, navigation, and common functionality
 */

// Wait for DOM to be ready
function domReady(callback) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(callback, 1);
    } else {
        document.addEventListener('DOMContentLoaded', callback);
    }
}

// Initialize admin panel
domReady(function() {
    initSidebar();
    initBackToTop();
    initMobileMenu();
    initFormEnhancements();
    initTableSelection();
    initNotifications();
    initCharts();
});

/**
 * Sidebar Functionality
 */
function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const menuToggle = document.querySelector('.menu-toggle');
    
    if (!sidebar) return;

    // Toggle sidebar collapse
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
        });
    }
    
    // Mobile menu toggle
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Restore sidebar state
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === '1') {
        sidebar.classList.add('collapsed');
    }
    
    // Close sidebar when clicking outside on mobile
    if (window.innerWidth <= 992) {
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('active');
        }
    });
    
    // Active menu item highlighting
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Close mobile menu
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    });
}

/**
 * Back to Top Button
 */
function initBackToTop() {
    const backToTopBtn = document.querySelector('.back-to-top');
    
    if (!backToTopBtn) return;
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });
    
    // Scroll to top
    backToTopBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * Mobile Menu
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (!menuToggle || !sidebar) return;
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Close menu when clicking on a link
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    });
}

/**
 * Form Enhancements
 */
function initFormEnhancements() {
    // Input focus effects
    const inputs = document.querySelectorAll('.form-control, .input-wrapper input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // File upload previews
    const fileUploads = document.querySelectorAll('.file-upload-wrapper input[type="file"]');
    fileUploads.forEach(upload => {
        upload.addEventListener('change', function(e) {
            handleFileUpload(this, e.target.files);
        });
    });
    
    // Toggle password visibility
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input[type="password"]');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Simple validation - check required fields
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Show error message
                    let errorMsg = field.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.style.cssText = 'color: #f44336; font-size: 12px; margin-top: 4px;';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                    errorMsg.textContent = 'این فیلد اجباری است';
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
        
        // Clear error on input
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorMsg = this.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.remove();
                }
            });
        });
    });
    
    // Character counters for textareas
    const textareas = document.querySelectorAll('textarea[data-maxlength]');
    textareas.forEach(textarea => {
        const maxLength = parseInt(textarea.dataset.maxlength);
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'font-size: 12px; color: #999; text-align: right; margin-top: 4px;';
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        textarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            counter.textContent = remaining + ' کاراکتر باقی مانده';
            
            if (remaining < 0) {
                counter.style.color = '#f44336';
            } else if (remaining < 20) {
                counter.style.color = '#ff9800';
            } else {
                counter.style.color = '#999';
            }
        });
        
        // Trigger once
        textarea.dispatchEvent(new Event('input'));
    });
}

/**
 * File Upload Preview
 */
function handleFileUpload(input, files) {
    const wrapper = input.closest('.file-upload-wrapper');
    const previewContainer = wrapper.querySelector('.file-preview') || createFilePreview(wrapper);
    
    // Clear existing previews
    previewContainer.innerHTML = '';
    
    if (files.length === 0) return;
    
    // Create preview for each file
    Array.from(files).forEach((file, index) => {
        const previewItem = document.createElement('div');
        previewItem.className = 'file-preview-item';
        
        // Create remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-btn';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Remove file from input
            const newFiles = Array.from(input.files).filter((_, i) => i !== index);
            const dataTransfer = new DataTransfer();
            newFiles.forEach(f => dataTransfer.items.add(f));
            input.files = dataTransfer.files;
            
            // Update preview
            handleFileUpload(input, input.files);
        });
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            previewItem.appendChild(img);
        } else {
            const fileInfo = document.createElement('div');
            fileInfo.style.cssText = 'padding: 20px; text-align: center; color: #666;';
            fileInfo.innerHTML = '<i class="fas fa-file" style="font-size: 24px; margin-bottom: 8px; display: block;"></i><span>' + file.name + '</span>';
            previewItem.appendChild(fileInfo);
        }
        
        previewItem.appendChild(removeBtn);
        previewContainer.appendChild(previewItem);
    });
}

function createFilePreview(wrapper) {
    const preview = document.createElement('div');
    preview.className = 'file-preview';
    wrapper.appendChild(preview);
    return preview;
}

/**
 * Table Selection
 */
function initTableSelection() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
        const selectAll = table.querySelector('thead input[type="checkbox"]');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
                updateSelectedCount(table);
            });
        }
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectedCount(table);
                
                // Update select all checkbox
                if (selectAll) {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    selectAll.checked = allChecked;
                }
            });
        });
    });
}

function updateSelectedCount(table) {
    const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]:checked');
    const count = checkboxes.length;
    
    // Update toolbar or status
    const selectedCountEl = table.closest('.dashboard-card')?.querySelector('.selected-count');
    if (selectedCountEl) {
        selectedCountEl.textContent = toPersianNumbers(count) + ' آیتم انتخاب شده';
    }
}

/**
 * Notifications
 */
function initNotifications() {
    // Notification dropdown
    const notificationToggle = document.querySelector('.notification');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    
    if (notificationToggle && notificationDropdown) {
        notificationToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationToggle.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
    }
    
    // Show toast notifications
    window.showToast = function(message, type = 'success', duration = 5000) {
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto remove after duration
        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', function() {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        });
    };
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    container.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 3000;
        display: flex;
        flex-direction: column;
        gap: 12px;
    `;
    document.body.appendChild(container);
    return container;
}

/**
 * Charts Initialization
 */
function initCharts() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') return;
    
    // Set default Chart.js options for RTL
    Chart.defaults.font.family = "'Vazirmatn', sans-serif";
    Chart.defaults.plugins.legend.rtl = true;
    Chart.defaults.plugins.legend.position = 'bottom';
}

/**
 * Utility Functions
 */

// Convert numbers to Persian
function toPersianNumbers(num) {
    const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    return String(num).replace(/\d/g, function(digit) {
        return persianDigits[parseInt(digit)];
    });
}

// Format price
function formatPrice(price) {
    return toPersianNumbers(price.toLocaleString()) + ' تومان';
}

// Format date
function formatDate(dateString, format = 'YYYY/MM/DD') {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    const day = date.getDate();
    
    // Simple Persian date conversion (approximate)
    const persianYear = year - 621;
    
    return format
        .replace('YYYY', toPersianNumbers(persianYear))
        .replace('MM', toPersianNumbers(month.toString().padStart(2, '0')))
        .replace('DD', toPersianNumbers(day.toString().padStart(2, '0')));
}

// Get order status text
function getOrderStatusText(status) {
    const statuses = {
        'pending': 'در انتظار',
        'processing': 'در حال پردازش',
        'shipped': 'ارسال شده',
        'delivered': 'تحویل داده شده',
        'completed': 'تکمیل شده',
        'cancelled': 'کنسل شده'
    };
    return statuses[status] || status;
}

// Get payment status text
function getPaymentStatusText(status) {
    const statuses = {
        'pending': 'در انتظار پرداخت',
        'paid': 'پرداخت شده',
        'failed': 'پرداخت ناموفق',
        'refunded': 'عوض شده'
    };
    return statuses[status] || status;
}

// Get payment method text
function getPaymentMethodText(method) {
    const methods = {
        'cash': 'پرداخت در محل',
        'online': 'پرداخت آنلاین',
        'transfer': 'واریز بانکی',
        'wallet': 'کیف پول'
    };
    return methods[method] || method;
}

/**
 * AJAX Helper
 */
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        data: null,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        responseType: 'json'
    };
    
    options = { ...defaults, ...options };
    
    if (options.data && options.method.toUpperCase() !== 'GET') {
        if (options.data instanceof FormData) {
            delete options.headers['Content-Type'];
        } else if (typeof options.data === 'object') {
            options.body = new URLSearchParams(options.data).toString();
        }
    } else if (options.data && options.method.toUpperCase() === 'GET') {
        url += (url.includes('?') ? '&' : '?') + new URLSearchParams(options.data).toString();
    }
    
    return fetch(url, options)
        .then(response => {
            if (options.responseType === 'json') {
                return response.json();
            }
            return response.text();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            return { success: false, message: 'خطا در اتصال به سرور' };
        });
}

/**
 * Confirm Dialog
 */
function confirmDialog(message, title = 'تایید', confirmText = 'بله', cancelText = 'خیر') {
    return new Promise((resolve) => {
        const dialog = document.createElement('div');
        dialog.className = 'confirm-dialog-overlay';
        dialog.innerHTML = `
            <div class="confirm-dialog">
                <div class="confirm-dialog-header">
                    <h3>${title}</h3>
                    <button class="confirm-close">&times;</button>
                </div>
                <div class="confirm-dialog-body">
                    <p>${message}</p>
                </div>
                <div class="confirm-dialog-footer">
                    <button class="btn btn-secondary confirm-cancel">${cancelText}</button>
                    <button class="btn btn-danger confirm-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        
        dialog.style.cssText = `
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        `;
        
        dialog.querySelector('.confirm-dialog').style.cssText = `
            background: white;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        `;
        
        dialog.querySelector('.confirm-dialog-header').style.cssText = `
            padding: 16px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        
        dialog.querySelector('.confirm-dialog-header h3').style.cssText = `
            margin: 0;
            font-size: 16px;
        `;
        
        dialog.querySelector('.confirm-close').style.cssText = `
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        `;
        
        dialog.querySelector('.confirm-dialog-body').style.cssText = `
            padding: 20px;
            text-align: right;
        `;
        
        dialog.querySelector('.confirm-dialog-body p').style.cssText = `
            margin: 0;
            color: #666;
        `;
        
        dialog.querySelector('.confirm-dialog-footer').style.cssText = `
            padding: 16px 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        `;
        
        document.body.appendChild(dialog);
        
        const confirmBtn = dialog.querySelector('.confirm-confirm');
        const cancelBtn = dialog.querySelector('.confirm-cancel');
        const closeBtn = dialog.querySelector('.confirm-close');
        
        confirmBtn.addEventListener('click', () => {
            dialog.remove();
            resolve(true);
        });
        
        cancelBtn.addEventListener('click', () => {
            dialog.remove();
            resolve(false);
        });
        
        closeBtn.addEventListener('click', () => {
            dialog.remove();
            resolve(false);
        });
        
        // Close on escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                dialog.remove();
                resolve(false);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        
        document.addEventListener('keydown', handleEscape);
        
        // Auto focus confirm button
        confirmBtn.focus();
    });
}

/**
 * Show Loading
 */
function showLoading() {
    const overlay = document.querySelector('.loading-overlay') || createLoadingOverlay();
    overlay.style.display = 'flex';
}

/**
 * Hide Loading
 */
function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function createLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(overlay);
    return overlay;
}

/**
 * Tab System
 */
function initTabs() {
    const tabContainers = document.querySelectorAll('.tabs-container');
    
    tabContainers.forEach(container => {
        const tabLinks = container.querySelectorAll('.tab-link');
        const tabContents = container.querySelectorAll('.tab-content');
        
        tabLinks.forEach((link, index) => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and contents
                tabLinks.forEach(l => l.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                if (tabContents[index]) {
                    tabContents[index].classList.add('active');
                }
            });
        });
    });
}

// Initialize tabs on DOM ready
domReady(initTabs);

/**
 * Select2-like dropdown enhancement
 */
function initSelectEnhancements() {
    const selects = document.querySelectorAll('select.select-enhanced');
    
    selects.forEach(select => {
        const wrapper = document.createElement('div');
        wrapper.className = 'select-wrapper';
        
        const selected = document.createElement('div');
        selected.className = 'select-selected';
        selected.textContent = select.options[select.selectedIndex]?.text || '';
        
        const dropdown = document.createElement('div');
        dropdown.className = 'select-dropdown';
        dropdown.style.display = 'none';
        
        select.options.forEach(option => {
            const optionEl = document.createElement('div');
            optionEl.className = 'select-option';
            optionEl.textContent = option.text;
            optionEl.dataset.value = option.value;
            optionEl.addEventListener('click', () => {
                select.value = option.value;
                selected.textContent = option.text;
                dropdown.style.display = 'none';
                select.dispatchEvent(new Event('change'));
            });
            dropdown.appendChild(optionEl);
        });
        
        wrapper.appendChild(selected);
        wrapper.appendChild(dropdown);
        select.parentNode.insertBefore(wrapper, select);
        select.style.display = 'none';
        
        selected.addEventListener('click', () => {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    });
}

// Initialize select enhancements
domReady(initSelectEnhancements);

/**
 * Date Picker (Simple Persian)
 */
function initDatePickers() {
    const datePickers = document.querySelectorAll('.date-picker');
    
    datePickers.forEach(input => {
        input.addEventListener('focus', function() {
            // Simple date picker would go here
            // For now, just show a placeholder
        });
    });
}

// Initialize date pickers
domReady(initDatePickers);

/**
 * Export Functions for Global Use
 */
window.AdminPanel = {
    showToast,
    confirmDialog,
    showLoading,
    hideLoading,
    toPersianNumbers,
    formatPrice,
    formatDate,
    getOrderStatusText,
    getPaymentStatusText,
    getPaymentMethodText,
    ajaxRequest
};
