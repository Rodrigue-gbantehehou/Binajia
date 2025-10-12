/**
 * BINAJIA Admin Dashboard JavaScript
 * Gestion des interactions et fonctionnalités admin
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 BINAJIA Admin Dashboard loaded');
    
    // Initialize all admin functionalities
    initializeButtons();
    initializeModals();
    initializeForms();
    initializeNotifications();
    initializeDataTables();
    initializeCharts();
    initializeAnimations();
});

/**
 * Initialize button interactions
 */
function initializeButtons() {
    // Export buttons
    document.querySelectorAll('[data-action="export"]').forEach(btn => {
        btn.addEventListener('click', handleExport);
    });
    
    // Delete buttons
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', handleDelete);
    });
    
    // Bulk actions
    document.querySelectorAll('[data-action="bulk"]').forEach(btn => {
        btn.addEventListener('click', handleBulkAction);
    });
    
    // Cache management
    document.querySelectorAll('[data-action="clear-cache"]').forEach(btn => {
        btn.addEventListener('click', handleClearCache);
    });
    
    // Database optimization
    document.querySelectorAll('[data-action="optimize-db"]').forEach(btn => {
        btn.addEventListener('click', handleOptimizeDB);
    });
    
    // Backup
    document.querySelectorAll('[data-action="backup"]').forEach(btn => {
        btn.addEventListener('click', handleBackup);
    });
    
    // Maintenance mode
    document.querySelectorAll('[data-action="maintenance"]').forEach(btn => {
        btn.addEventListener('click', handleMaintenanceMode);
    });
    
    // Settings save
    document.querySelectorAll('[data-action="save-settings"]').forEach(btn => {
        btn.addEventListener('click', handleSaveSettings);
    });
    
    // Card actions
    document.querySelectorAll('[data-action="activate-card"]').forEach(btn => {
        btn.addEventListener('click', handleActivateCard);
    });
    
    document.querySelectorAll('[data-action="revoke-card"]').forEach(btn => {
        btn.addEventListener('click', handleRevokeCard);
    });
}

/**
 * Handle export functionality
 */
function handleExport(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const type = button.dataset.type || 'csv';
    const entity = button.dataset.entity || 'data';
    
    showLoadingState(button);
    showNotification('info', `Export ${entity} en cours...`);
    
    // Simulate export process
    setTimeout(() => {
        hideLoadingState(button);
        showNotification('success', `Export ${entity} terminé avec succès!`);
        
        // Trigger actual download
        const link = document.createElement('a');
        link.href = `/admin/export/${entity}?format=${type}`;
        link.download = `binajia_${entity}_${new Date().toISOString().split('T')[0]}.${type}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }, 2000);
}

/**
 * Handle delete functionality
 */
function handleDelete(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const itemId = button.dataset.id;
    const itemType = button.dataset.type || 'élément';
    
    showConfirmModal(
        'Confirmer la suppression',
        `Êtes-vous sûr de vouloir supprimer cet ${itemType} ? Cette action est irréversible.`,
        'Supprimer',
        'danger',
        () => {
            showLoadingState(button);
            
            // Simulate delete process
            setTimeout(() => {
                hideLoadingState(button);
                showNotification('success', `${itemType} supprimé avec succès!`);
                
                // Remove row from table
                const row = button.closest('tr');
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-100%)';
                    setTimeout(() => row.remove(), 300);
                }
            }, 1500);
        }
    );
}

/**
 * Handle bulk actions
 */
function handleBulkAction(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const action = button.dataset.bulkAction;
    const checkedItems = document.querySelectorAll('input[type="checkbox"]:checked');
    
    if (checkedItems.length === 0) {
        showNotification('warning', 'Veuillez sélectionner au moins un élément.');
        return;
    }
    
    const count = checkedItems.length;
    showConfirmModal(
        `Action en lot: ${action}`,
        `Appliquer l'action "${action}" à ${count} élément(s) sélectionné(s) ?`,
        'Confirmer',
        'primary',
        () => {
            showLoadingState(button);
            showNotification('info', `Application de l'action à ${count} élément(s)...`);
            
            setTimeout(() => {
                hideLoadingState(button);
                showNotification('success', `Action "${action}" appliquée avec succès!`);
                
                // Uncheck all checkboxes
                checkedItems.forEach(checkbox => checkbox.checked = false);
            }, 2000);
        }
    );
}

/**
 * Handle cache clearing
 */
function handleClearCache(event) {
    event.preventDefault();
    const button = event.currentTarget;
    
    showLoadingState(button);
    showNotification('info', 'Vidage du cache en cours...');
    
    // Simulate cache clearing
    setTimeout(() => {
        hideLoadingState(button);
        showNotification('success', 'Cache vidé avec succès!');
    }, 3000);
}

/**
 * Handle database optimization
 */
function handleOptimizeDB(event) {
    event.preventDefault();
    const button = event.currentTarget;
    
    showLoadingState(button);
    showNotification('info', 'Optimisation de la base de données...');
    
    // Simulate DB optimization
    setTimeout(() => {
        hideLoadingState(button);
        showNotification('success', 'Base de données optimisée!');
    }, 4000);
}

/**
 * Handle backup creation
 */
function handleBackup(event) {
    event.preventDefault();
    const button = event.currentTarget;
    
    showLoadingState(button);
    showNotification('info', 'Création du backup en cours...');
    
    // Simulate backup process
    setTimeout(() => {
        hideLoadingState(button);
        showNotification('success', 'Backup créé avec succès!');
    }, 5000);
}

/**
 * Handle maintenance mode toggle
 */
function handleMaintenanceMode(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const isActive = button.dataset.active === 'true';
    
    showConfirmModal(
        'Mode maintenance',
        isActive ? 
            'Désactiver le mode maintenance ? Le site redeviendra accessible au public.' :
            'Activer le mode maintenance ? Le site sera temporairement inaccessible au public.',
        isActive ? 'Désactiver' : 'Activer',
        isActive ? 'success' : 'warning',
        () => {
            showLoadingState(button);
            
            setTimeout(() => {
                hideLoadingState(button);
                button.dataset.active = !isActive;
                button.innerHTML = isActive ? 
                    '<i class="fas fa-exclamation-triangle mr-3"></i>Activer maintenance' :
                    '<i class="fas fa-check mr-3"></i>Désactiver maintenance';
                
                showNotification('success', 
                    isActive ? 'Mode maintenance désactivé!' : 'Mode maintenance activé!'
                );
            }, 2000);
        }
    );
}

/**
 * Handle settings save
 */
function handleSaveSettings(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const form = button.closest('form') || document.querySelector('form');
    
    if (!form) {
        showNotification('error', 'Formulaire non trouvé!');
        return;
    }
    
    // Validate form
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    if (!isValid) {
        showNotification('error', 'Veuillez remplir tous les champs obligatoires.');
        return;
    }
    
    showLoadingState(button);
    showNotification('info', 'Sauvegarde des paramètres...');
    
    // Simulate save process
    setTimeout(() => {
        hideLoadingState(button);
        showNotification('success', 'Paramètres sauvegardés avec succès!');
    }, 2000);
}

/**
 * Handle card activation
 */
function handleActivateCard(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const cardId = button.dataset.cardId;
    
    showLoadingState(button);
    showNotification('info', 'Activation de la carte...');
    
    setTimeout(() => {
        hideLoadingState(button);
        showNotification('success', 'Carte activée avec succès!');
        
        // Update button state
        button.classList.remove('bg-green-600', 'hover:bg-green-700');
        button.classList.add('bg-gray-600', 'hover:bg-gray-700');
        button.innerHTML = '<i class="fas fa-check mr-1"></i>Activée';
        button.disabled = true;
    }, 1500);
}

/**
 * Handle card revocation
 */
function handleRevokeCard(event) {
    event.preventDefault();
    const button = event.currentTarget;
    const cardId = button.dataset.cardId;
    
    showConfirmModal(
        'Révoquer la carte',
        'Êtes-vous sûr de vouloir révoquer cette carte ? L\'utilisateur ne pourra plus l\'utiliser.',
        'Révoquer',
        'danger',
        () => {
            showLoadingState(button);
            showNotification('info', 'Révocation de la carte...');
            
            setTimeout(() => {
                hideLoadingState(button);
                showNotification('success', 'Carte révoquée avec succès!');
                
                // Update button state
                button.classList.remove('bg-red-600', 'hover:bg-red-700');
                button.classList.add('bg-gray-600', 'hover:bg-gray-700');
                button.innerHTML = '<i class="fas fa-ban mr-1"></i>Révoquée';
                button.disabled = true;
            }, 1500);
        }
    );
}

/**
 * Show loading state on button
 */
function showLoadingState(button) {
    button.dataset.originalText = button.innerHTML;
    button.innerHTML = '<div class="admin-loading mr-2"></div>Chargement...';
    button.disabled = true;
    button.classList.add('opacity-75');
}

/**
 * Hide loading state on button
 */
function hideLoadingState(button) {
    button.innerHTML = button.dataset.originalText;
    button.disabled = false;
    button.classList.remove('opacity-75');
}

/**
 * Show notification
 */
function showNotification(type, message) {
    // Remove existing notifications
    document.querySelectorAll('.admin-notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `admin-notification fixed top-4 right-4 z-50 p-4 rounded-xl shadow-lg border-l-4 max-w-md transition-all duration-300 transform translate-x-full`;
    
    const colors = {
        success: 'bg-green-50 border-green-500 text-green-700',
        error: 'bg-red-50 border-red-500 text-red-700',
        warning: 'bg-yellow-50 border-yellow-500 text-yellow-700',
        info: 'bg-blue-50 border-blue-500 text-blue-700'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    notification.classList.add(...colors[type].split(' '));
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${icons[type]} mr-3"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

/**
 * Show confirmation modal
 */
function showConfirmModal(title, message, confirmText, type, onConfirm) {
    // Remove existing modals
    document.querySelectorAll('.admin-modal').forEach(m => m.remove());
    
    const modal = document.createElement('div');
    modal.className = 'admin-modal fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    
    const typeColors = {
        primary: 'bg-green-600 hover:bg-green-700',
        danger: 'bg-red-600 hover:bg-red-700',
        warning: 'bg-yellow-600 hover:bg-yellow-700',
        success: 'bg-green-600 hover:bg-green-700'
    };
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all duration-300 scale-95">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">${title}</h3>
            <p class="text-gray-600 mb-6">${message}</p>
            <div class="flex justify-end gap-3">
                <button onclick="this.closest('.admin-modal').remove()" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                    Annuler
                </button>
                <button class="confirm-btn px-6 py-2 text-white font-semibold rounded-lg transition-colors ${typeColors[type]}">
                    ${confirmText}
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Animate in
    setTimeout(() => {
        modal.querySelector('div').classList.remove('scale-95');
        modal.querySelector('div').classList.add('scale-100');
    }, 100);
    
    // Handle confirm
    modal.querySelector('.confirm-btn').addEventListener('click', () => {
        modal.remove();
        onConfirm();
    });
    
    // Handle backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

/**
 * Initialize modals
 */
function initializeModals() {
    // Close modals on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.admin-modal').forEach(m => m.remove());
        }
    });
}

/**
 * Initialize forms
 */
function initializeForms() {
    // Auto-save drafts
    document.querySelectorAll('form').forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', debounce(() => {
                saveDraft(form);
            }, 1000));
        });
    });
}

/**
 * Initialize notifications
 */
function initializeNotifications() {
    // Check for flash messages and convert them
    document.querySelectorAll('.admin-flash').forEach(flash => {
        const type = flash.classList.contains('success') ? 'success' :
                    flash.classList.contains('error') ? 'error' :
                    flash.classList.contains('warning') ? 'warning' : 'info';
        
        showNotification(type, flash.textContent.trim());
        flash.remove();
    });
}

/**
 * Initialize data tables
 */
function initializeDataTables() {
    // Add sorting functionality
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', () => sortTable(th));
    });
    
    // Add row selection
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
}

/**
 * Initialize charts (placeholder)
 */
function initializeCharts() {
    // Chart initialization would go here
    console.log('📊 Charts initialized');
}

/**
 * Initialize animations
 */
function initializeAnimations() {
    // Animate cards on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    });
    
    document.querySelectorAll('.admin-card, .admin-stats-card').forEach(card => {
        observer.observe(card);
    });
}

/**
 * Utility functions
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

function saveDraft(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    localStorage.setItem(`draft_${form.id || 'form'}`, JSON.stringify(data));
}

function sortTable(th) {
    // Table sorting implementation
    console.log('🔄 Sorting table by:', th.dataset.sort);
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('input[type="checkbox"]:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
}

// Export functions for global access
window.AdminDashboard = {
    showNotification,
    showConfirmModal,
    showLoadingState,
    hideLoadingState
};
