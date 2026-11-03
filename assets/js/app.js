/**
 * Project Management System - Main JavaScript
 * PT. Lampung Jasa Utama
 */

// ============================================================
// GLOBAL DELETE FUNCTIONS (Di Luar IIFE agar selalu terjangkau)
// ============================================================

/**
 * Delete Project
 */
window.deleteProject = function(projectId) {
    projectId = parseInt(projectId, 10);
    if (!projectId || isNaN(projectId)) {
        console.error('Invalid Project ID:', projectId);
        return;
    }
    
    Swal.fire({
        title: 'Hapus Project?',
        text: 'Project beserta seluruh task, lampiran, dan riwayatnya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Project',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true
    }).then(function(result) {
        if (result.isConfirmed) {
            PM.loading('Menghapus project...');
            
            PM.post(APP_CONFIG.BASE_URL + '/?page=api&action=delete_project', {
                id: projectId,
                project_id: projectId
            }).then(function(res) {
                PM.closeAlert();
                if (res && res.success) {
                    PM.toast('success', res.message || 'Project berhasil dihapus');
                    setTimeout(function() {
                        window.location.href = APP_CONFIG.BASE_URL + '/?page=projects&action=index';
                    }, 800);
                } else {
                    Swal.fire('Gagal Hapus', (res ? res.message : 'Akses ditolak atau kesalahan server'), 'error');
                }
            }).catch(function(err) {
                PM.closeAlert();
                console.error('Delete project error:', err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan/server', 'error');
            });
        }
    });
};

/**
 * Delete Task
 */
window.deleteTask = function(taskId) {
    taskId = parseInt(taskId, 10);
    if (!taskId || isNaN(taskId)) {
        console.error('Invalid Task ID:', taskId);
        return;
    }
    
    Swal.fire({
        title: 'Hapus Task?',
        text: 'Task dan seluruh lampirannya akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus Task',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true
    }).then(function(result) {
        if (result.isConfirmed) {
            PM.loading('Menghapus task...');
            
            PM.post(APP_CONFIG.BASE_URL + '/?page=api&action=delete_task', {
                id: taskId,
                task_id: taskId
            }).then(function(res) {
                PM.closeAlert();
                if (res && res.success) {
                    PM.toast('success', res.message || 'Task berhasil dihapus');
                    
                    // Tutup modal jika ada
                    var modalEl = document.getElementById('taskDetailModal');
                    if (modalEl) {
                        var instance = bootstrap.Modal.getInstance(modalEl);
                        if (instance) instance.hide();
                    }
                    
                    setTimeout(function() {
                        window.location.reload();
                    }, 700);
                } else {
                    Swal.fire('Gagal Hapus', (res ? res.message : 'Akses ditolak atau kesalahan server'), 'error');
                }
            }).catch(function(err) {
                PM.closeAlert();
                console.error('Delete task error:', err);
                Swal.fire('Error', 'Terjadi kesalahan jaringan/server', 'error');
            });
        }
    });
};

(function() {
    'use strict';
    
    // ============================================================
    // GLOBAL UTILITIES
    // ============================================================
    
    window.PM = {
        toast: function(icon, title, timer = 3000) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: title,
                showConfirmButton: false,
                timer: timer,
                timerProgressBar: true
            });
        },
        
        confirm: function(options) {
            return Swal.fire({
                title: options.title || 'Apakah Anda yakin?',
                text: options.text || '',
                icon: options.icon || 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: options.confirmText || 'Ya',
                cancelButtonText: options.cancelText || 'Batal',
                reverseButtons: true,
                focusCancel: true
            });
        },
        
        loading: function(text = 'Mohon tunggu...') {
            Swal.fire({
                title: text,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });
        },
        
        closeAlert: function() {
            Swal.close();
        },
        
        post: function(url, data) {
            const formData = new FormData();
            Object.keys(data).forEach(key => {
                if (Array.isArray(data[key])) {
                    data[key].forEach(v => formData.append(key + '[]', v));
                } else {
                    formData.append(key, data[key]);
                }
            });
            formData.append('csrf_token', APP_CONFIG.CSRF_TOKEN);
            
            return fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err)).catch(() => Promise.reject({message: 'HTTP Error ' + response.status}));
                }
                return response.json();
            });
        },
        
        formatDate: function(dateStr) {
            if (!dateStr) return '-';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const d = new Date(dateStr);
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
    };
    
    // ============================================================
    // DOM LOADED INITIALIZATION
    // ============================================================
    
    document.addEventListener('DOMContentLoaded', function() {
        // Pindahkan modal ke root <body> agar tidak terhalang layout sidebar
        document.querySelectorAll('.modal').forEach(function(modal) {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
        
        // Prevent form double submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    setTimeout(() => {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
                        
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 10000);
                    }, 10);
                }
            });
        });
    });
    
})();