/**
 * Kanban Board - Drag & Drop functionality
 * Uses SortableJS
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const board = document.getElementById('kanbanBoard');
        if (!board) return;
        
        const projectId = board.dataset.projectId;
        const canManage = window.CAN_MANAGE_TASKS !== false;
        
        if (!canManage) {
            console.log('Kanban: read-only mode');
            return;
        }
        
        // Initialize sortable for each column
        document.querySelectorAll('.kanban-column-body').forEach(colEl => {
            new Sortable(colEl, {
                group: 'kanban-tasks',
                animation: 200,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.kanban-card',
                filter: 'button, a, input, select',
                preventOnFilter: false,
                
                onEnd: function(evt) {
                    const taskEl = evt.item;
                    const taskId = taskEl.dataset.taskId;
                    const fromColumnId = evt.from.dataset.columnId;
                    const toColumnId = evt.to.dataset.columnId;
                    const newIndex = evt.newIndex;
                    
                    // Calculate positions
                    const positions = [];
                    evt.to.querySelectorAll('.kanban-card').forEach((el, idx) => {
                        positions.push({
                            id: el.dataset.taskId,
                            position: idx
                        });
                    });
                    
                    // Update column counts
                    updateColumnCounts();
                    
                    // Send to server
                    moveTaskAjax(taskId, fromColumnId, toColumnId, newIndex, positions);
                }
            });
        });
        
        function updateColumnCounts() {
            document.querySelectorAll('.kanban-column').forEach(col => {
                const countEl = col.querySelector('.kanban-column-count');
                const taskCount = col.querySelectorAll('.kanban-card').length;
                if (countEl) countEl.textContent = taskCount;
            });
        }
        
        function moveTaskAjax(taskId, fromColumnId, toColumnId, newIndex, positions) {
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('from_column_id', fromColumnId);
            formData.append('to_column_id', toColumnId);
            formData.append('new_position', newIndex);
            formData.append('positions', JSON.stringify(positions));
            formData.append('csrf_token', APP_CONFIG.CSRF_TOKEN);
            
            fetch(APP_CONFIG.BASE_URL + '/?page=api&action=move_task', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    // Show subtle notification
                    if (fromColumnId !== toColumnId) {
                        PM.toast('success', 'Task moved successfully', 1500);
                    }
                    
                    // Update project progress if provided
                    if (res.progress !== undefined) {
                        updateProjectProgress(res.progress);
                    }
                } else {
                    PM.toast('error', res.message || 'Failed to move task');
                    // Reload to revert
                    setTimeout(() => window.location.reload(), 1500);
                }
            })
            .catch(err => {
                console.error('Move task error:', err);
                PM.toast('error', 'Network error. Reloading...');
                setTimeout(() => window.location.reload(), 1500);
            });
        }
        
        function updateProjectProgress(progress) {
            // Update progress bar if exists on page
            document.querySelectorAll('.progress-bar').forEach(bar => {
                if (bar.closest('.info-card')) {
                    bar.style.width = progress + '%';
                    const label = bar.closest('.d-flex')?.querySelector('.text-primary');
                    if (label && label.textContent.includes('%')) {
                        label.textContent = Math.round(progress) + '%';
                    }
                }
            });
        }
        
        // Filter tasks on the board (client-side)
        window.filterKanbanTasks = function(searchTerm) {
            searchTerm = (searchTerm || '').toLowerCase().trim();
            document.querySelectorAll('.kanban-card').forEach(card => {
                const title = card.querySelector('.kanban-card-title')?.textContent.toLowerCase() || '';
                const desc = card.querySelector('.kanban-card-desc')?.textContent.toLowerCase() || '';
                
                if (!searchTerm || title.includes(searchTerm) || desc.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            updateColumnCounts();
        };
        
        // Attach filter to global search if present
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch && window.location.href.includes('page=projects&action=view')) {
            globalSearch.addEventListener('input', PM.debounce(function() {
                window.filterKanbanTasks(this.value);
            }, 300));
            globalSearch.placeholder = 'Filter tasks on board...';
        }
    });
    
})();