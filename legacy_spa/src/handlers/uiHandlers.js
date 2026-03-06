// src/handlers/uiHandlers.js
import { appState } from '../state.js';
import { render } from '../router.js';

export function handleUserFilterChange(event) {
    const input = event.target;
    const { name, value } = input;

    // Trata especificamente o courseId para converter para número se não for 'all'
    if (name === 'courseId') {
        appState.userFilters.courseId = value === 'all' ? 'all' : parseInt(value, 10);
        // Se 'Todos os Cursos' for selecionado, reseta o status da matrícula
        if (appState.userFilters.courseId === 'all') {
            appState.userFilters.enrollmentStatus = 'all';
            // Desabilita o select de status
            const statusSelect = document.getElementById('filter-status');
            if (statusSelect) statusSelect.disabled = true;
        } else {
             // Habilita o select de status se um curso específico for selecionado
            const statusSelect = document.getElementById('filter-status');
            if (statusSelect) statusSelect.disabled = false;
        }
    } else {
        // Para outros filtros (name, role, enrollmentStatus)
        appState.userFilters[name] = value;
    }

    render(); // Re-renderiza a view de gerenciamento de usuários com os novos filtros
}

export async function handleRoleChange(event, userId) {
    const selectElement = event.target;
    const newRole = selectElement.value;
    const originalRole = appState.users.find(u => u.id === userId)?.role; // Guarda role original

    try {
        await apiCall('updateUserRole', { userId, newRole });
        // Atualiza estado local APENAS se a API confirmar
        const userIndex = appState.users.findIndex(u => u.id === userId);
        if (userIndex > -1) {
            appState.users[userIndex].role = newRole;
        }
        // Não precisa re-renderizar, a mudança visual já ocorreu no select
    } catch(e) {
        alert(e.message || 'Erro ao alterar função.');
        // Reverte a seleção no dropdown em caso de erro
        selectElement.value = originalRole || 'unassigned';
    }
}

// Funções de Geração de Documentos (PDF) - São mais navegação/abrir URL
export function handleGenerateContractPdf(studentId, courseId) {
    window.open(`api/index.php?action=generateContractPdf&studentId=${studentId}&courseId=${courseId}`, '_blank');
}

export function handleGenerateImageTermsPdf(studentId, courseId) {
    window.open(`api/index.php?action=generateImageTermsPdf&studentId=${studentId}&courseId=${courseId}`, '_blank');
}