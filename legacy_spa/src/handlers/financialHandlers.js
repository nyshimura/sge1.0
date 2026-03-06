// src/handlers/financialHandlers.js
import { apiCall } from '../api.js';
import { appState } from '../state.js';
import { render } from '../router.js';

export function handleDashboardDateChange(event) {
    appState.financialState.selectedDate = event.target.value;
    render(); // Re-renderiza o dashboard financeiro
}

export function handleDefaultersReportDateChange(event) {
    appState.financialState.defaultersReportMonth = event.target.value;
    render(); // Re-renderiza o relatório de inadimplentes
}

export function handleDefaultersReportChangeMonth(direction) {
    const currentDate = new Date(appState.financialState.defaultersReportMonth + '-02'); // Usa dia 02 para evitar bugs de fuso
    currentDate.setUTCMonth(currentDate.getUTCMonth() + direction);
    appState.financialState.defaultersReportMonth = currentDate.toISOString().slice(0, 7);
    render(); // Re-renderiza o relatório
}

export function handleDefaultersReportCourseChange(event) {
    appState.financialState.defaultersReportCourseId = event.target.value;
    render(); // Re-renderiza o relatório
}

export async function handleBulkPay(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const paymentIds = formData.getAll('paymentIds').map(id => parseInt(id, 10));
    const submitButton = form.querySelector('button[type="submit"]');

    if (paymentIds.length === 0) {
        return alert('Nenhum pagamento foi selecionado.');
    }

    if (confirm(`Você tem certeza que deseja dar baixa em ${paymentIds.length} pagamento(s)?`)) {
        if(submitButton) submitButton.disabled = true;
        try {
            const result = await apiCall('bulkUpdatePaymentStatus', { paymentIds });
            alert(result.message || 'Pagamentos atualizados com sucesso!');
            // Atualiza o estado local
            appState.payments.forEach(p => {
                if (paymentIds.includes(p.id)) {
                    p.status = 'Pago';
                    p.paymentDate = new Date().toISOString().split('T')[0];
                }
            });
            render(); // Re-renderiza o relatório
        } catch (e) {
            alert(e.message || 'Erro ao dar baixa nos pagamentos.');
        } finally {
            if(submitButton) submitButton.disabled = false;
        }
    }
}

export async function handlePaymentStatusChange(event, paymentId) {
    const selectElement = event.target;
    const newStatus = selectElement.value;
    const originalStatus = appState.payments.find(p => p.id === paymentId)?.status; // Guarda o status original

    try {
        await apiCall('updatePaymentStatus', { paymentId, status: newStatus });
        // Atualiza estado local
        const paymentIndex = appState.payments.findIndex(p => p.id === paymentId);
        if (paymentIndex > -1) {
            appState.payments[paymentIndex].status = newStatus;
            appState.payments[paymentIndex].paymentDate = (newStatus === 'Pago') ? new Date().toISOString().split('T')[0] : null;
        }
        render(); // Re-renderiza a view atual (Control Panel ou Profile)
    } catch(e) {
        alert(e.message || 'Erro ao atualizar status do pagamento.');
        // Reverte a seleção no dropdown em caso de erro
        selectElement.value = originalStatus || 'Pendente';
    }
}

export async function handleToggleFinanceStudent(studentId) {
    const { financialState } = appState;
    if (financialState.expandedStudentId === studentId) {
        financialState.expandedStudentId = null;
        render(); // Apenas fecha
    } else {
        const previousExpandedId = financialState.expandedStudentId;
        financialState.expandedStudentId = studentId; // Expande o novo
        render(); // Renderiza com o novo expandido (mostra "Carregando..." implicitamente)
        try {
            const data = await apiCall('getStudentPayments', { studentId }, 'GET');
            // Remove pagamentos antigos do studentId anterior (se houver) e do atual, depois adiciona os novos
            appState.payments = [
                ...appState.payments.filter(p => p.studentId !== studentId && p.studentId !== previousExpandedId),
                ...(data.payments || [])
            ];
            // Re-renderiza APENAS SE o estudante ainda for o que está expandido (evita race condition se o user clicar rápido)
            if (appState.financialState.expandedStudentId === studentId) {
                render();
            }
        } catch (e) {
            console.error("Erro ao buscar pagamentos:", e);
            alert(e.message || 'Erro ao buscar histórico financeiro.');
            // Fecha a expansão em caso de erro
            if (appState.financialState.expandedStudentId === studentId) {
                financialState.expandedStudentId = null;
                render();
            }
        }
    }
}

// Função para gerar recibo (é mais navegação/abrir URL do que lógica financeira)
export function handleGenerateReceipt(paymentId) {
    window.open(`api/index.php?action=generateReceipt&paymentId=${paymentId}`, '_blank');
}