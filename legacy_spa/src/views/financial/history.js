// src/views/financial/history.js
import { appState } from '../../state.js';

export function renderStudentFinancialHistory(studentId, studentPayments, isAdminView = false, allowActions = false) {
    // ALTERAÇÃO AQUI: Mudado para a.localeCompare(b) para ordem crescente
    studentPayments.sort((a, b) => a.referenceDate.localeCompare(b.referenceDate));

    const title = isAdminView ? "Histórico Financeiro" : "Meu Histórico Financeiro";

    const pendingPayments = studentPayments.filter(p => p.status === 'Pendente' || p.status === 'Atrasado');

    let bulkPayButton = '';
    if (!isAdminView && pendingPayments.length > 1) {
        const pendingIds = pendingPayments.map(p => p.id); // Array de IDs
        // Passa o array diretamente para a função
        bulkPayButton = `<div style="margin-bottom: 1rem;"><button class="action-button" onclick="window.AppHandlers.handleInitiatePixPayment([${pendingIds.join(',')}])">Pagar todas as pendências com PIX</button></div>`;
    }

    return `
        <h4 class="card-title">${title}</h4>
        ${bulkPayButton}
        ${studentPayments.length === 0 ? '<p>Nenhum histórico de pagamento encontrado.</p>' : `
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Mês de Referência</th>
                            <th>Curso</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                       ${studentPayments.map(p => {
                            const course = appState.courses.find(c => c.id === p.courseId);
                            const refDate = new Date(p.referenceDate + 'T00:00:00');
                            const dueDate = new Date(p.dueDate + 'T00:00:00');
                            let actionHtmlContent = ''; // Conteúdo interno da célula <td>

                            if (allowActions) { // É a visão do Admin com permissões
                                // 1. Adiciona o Dropdown SEMPRE
                                actionHtmlContent += `
                                    <select class="payment-status-select" onchange="window.AppHandlers.handlePaymentStatusChange(event, ${p.id})">
                                        <option value="Pendente" ${p.status === 'Pendente' ? 'selected' : ''}>Pendente</option>
                                        <option value="Pago" ${p.status === 'Pago' ? 'selected' : ''}>Pago</option>
                                        <option value="Atrasado" ${p.status === 'Atrasado' ? 'selected' : ''}>Atrasado</option>
                                        <option value="Cancelado" ${p.status === 'Cancelado' ? 'selected' : ''}>Cancelado</option>
                                    </select>
                                `;
                                
                                // 2. Adiciona o Botão de Recibo SE estiver Pago
                                if (p.status === 'Pago') {
                                    actionHtmlContent += `
                                        <button type="button" class="action-button" 
                                                onclick="window.AppHandlers.handleGenerateReceipt(${p.id})" 
                                                style="margin-top: 0.5rem; width: 100%;">
                                            Gerar Recibo
                                        </button>
                                    `;
                                }
                                
                            } else { // É a visão do Aluno
                                if (p.status === 'Pago') {
                                    actionHtmlContent = `<button type="button" class="action-button" onclick="window.AppHandlers.handleGenerateReceipt(${p.id})">Gerar Recibo</button>`;
                                } else if (!isAdminView && (p.status === 'Pendente' || p.status === 'Atrasado')) { // is student view
                                    actionHtmlContent = `<button class="action-button" onclick="window.AppHandlers.handleInitiatePixPayment([${p.id}])">Pagar com PIX</button>`;
                                } else {
                                    actionHtmlContent = '-'; // Sem ação
                                }
                            }

                            return `
                                <tr class="${p.status === 'Cancelado' ? 'text-strikethrough' : ''}">
                                    <td>${refDate.toLocaleString('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' })}</td>
                                    <td>${course?.name || 'N/A'}</td>
                                    <td>R$ ${Number(p.amount).toFixed(2).replace('.', ',')}</td>
                                    <td>${dueDate.toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                                    <td><span class="status-badge status-${p.status.toLowerCase()}">${p.status}</span></td>
                                    <td>${actionHtmlContent}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <p class="error-message" id="payment-status-error-${studentId}" style="margin-top: 1rem;"></p>
        `}
    `;
}