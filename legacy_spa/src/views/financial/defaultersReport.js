// src/views/financial/defaultersReport.js
import { apiCall } from '../../api.js';
import { appState } from '../../state.js';

export async function renderDefaultersReportView() {
    const { defaultersReportMonth, defaultersReportCourseId } = appState.financialState;
    const data = await apiCall('getDefaultersReport', { month: defaultersReportMonth, courseId: defaultersReportCourseId }, 'GET');
    const defaulters = data.defaulters || [];
    const courses = data.courses || [];

    const totalDue = defaulters.reduce((sum, item) => sum + Number(item.amount), 0);

    const date = new Date(defaultersReportMonth + '-02T00:00:00');
    let formattedMonth = date.toLocaleString('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' });
    formattedMonth = formattedMonth.charAt(0).toUpperCase() + formattedMonth.slice(1);

    return `
        <div class="view-header">
            <h2>Relatório de Inadimplentes (${defaulters.length} pendências)</h2>
            <button class="back-button" onclick="window.AppHandlers.handleNavigateToFinancialDashboard()">← Voltar ao Dashboard</button>
        </div>

        <div class="card full-width">
            <h3 class="card-title">Filtros para <strong>${formattedMonth}</strong></h3>
            <div class="dashboard-controls" style="justify-content: space-between;">
                <div class="dashboard-controls">
                    <label for="defaulters-month-selector">Alterar Mês:</label>
                    <button class="month-nav-button" onclick="window.AppHandlers.handleDefaultersReportChangeMonth(-1)">‹</button>
                    <input type="month" id="defaulters-month-selector" name="month" value="${defaultersReportMonth}" onchange="window.AppHandlers.handleDefaultersReportDateChange(event)">
                    <button class="month-nav-button" onclick="window.AppHandlers.handleDefaultersReportChangeMonth(1)">›</button>
                </div>
                <div class="dashboard-controls">
                     <label for="defaulters-course-selector">Filtrar por Curso:</label>
                     <select id="defaulters-course-selector" onchange="window.AppHandlers.handleDefaultersReportCourseChange(event)">
                        <option value="all">Todos os Cursos</option>
                        ${courses.map(course => `<option value="${course.id}" ${defaultersReportCourseId == course.id ? 'selected' : ''}>${course.name}</option>`).join('')}
                     </select>
                </div>
            </div>
        </div>

        <div class="card full-width">
            <div class="financial-summary-grid">
                <div class="summary-card">
                    <h3>Valor Total Pendente (Filtro)</h3>
                    <p>R$ ${totalDue.toFixed(2).replace('.', ',')}</p>
                </div>
            </div>
        </div>

        <form class="card full-width" onsubmit="window.AppHandlers.handleBulkPay(event)">
            <h3 class="card-title">Lista de Pagamentos Pendentes e Atrasados</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Pago?</th>
                            <th>Aluno</th>
                            <th>Email</th>
                            <th>Curso</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${defaulters.length === 0 ? '<tr><td colspan="8" style="text-align: center;">Nenhuma pendência encontrada para este filtro.</td></tr>' : defaulters.map(item => {
                            const dueDate = new Date(item.dueDate + 'T00:00:00').toLocaleDateString('pt-BR', { timeZone: 'UTC' });
                            return `
                                <tr>
                                    <td><input type="checkbox" name="paymentIds" value="${item.paymentId}" class="payment-checkbox"></td>
                                    <td>${item.firstName} ${item.lastName || ''}</td>
                                    <td>${item.email}</td>
                                    <td>${item.courseName}</td>
                                    <td>${dueDate}</td>
                                    <td>R$ ${Number(item.amount).toFixed(2).replace('.', ',')}</td>
                                    <td><span class="status-badge status-${item.status.toLowerCase()}">${item.status}</span></td>
                                    <td>
                                        <button type="button" class="action-button secondary" onclick="window.AppHandlers.handleNavigateToProfile(${item.studentId})">Ver Perfil</button>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <p class="error-message" id="bulk-pay-error"></p>
            ${defaulters.length > 0 ? '<button type="submit" class="action-button" style="margin-top: 1rem; align-self: flex-start;">Dar Baixa nos Pagamentos Selecionados</button>' : ''}
        </form>
    `;
}