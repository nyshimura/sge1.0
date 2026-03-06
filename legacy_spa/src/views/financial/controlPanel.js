// src/views/financial/controlPanel.js
import { apiCall } from '../../api.js';
import { appState } from '../../state.js';
import { renderStudentFinancialHistory } from './history.js';

export async function renderFinancialControlPanelView() {
    const data = await apiCall('getActiveStudents', {}, 'GET');
    const activeStudents = data.students;

    return `
        <div class="view-header">
            <h2>Gerenciar Pagamentos</h2>
            <button class="back-button" onclick="window.AppHandlers.handleNavigateToFinancialDashboard()">← Voltar ao Dashboard</button>
        </div>
        <div class="card full-width">
            <h3 class="card-title">Alunos com Matrículas (${activeStudents.length})</h3>
            ${activeStudents.length === 0 ? '<p>Nenhum aluno ativo para gerenciar.</p>' : `
            <ul class="list finance-student-list">
                ${activeStudents.map((student) => `
                    <li class="list-item clickable" onclick="window.AppHandlers.handleToggleFinanceStudent(${student.id})">
                        <div class="list-item-content">
                            <span class="list-item-title">${student.firstName} ${student.lastName || ''}</span>
                            <span class="list-item-subtitle">${student.email}</span>
                        </div>
                        <span class="expand-icon">${appState.financialState.expandedStudentId === student.id ? '▼' : '▶'}</span>
                    </li>
                    ${appState.financialState.expandedStudentId === student.id ? `
                        <li class="student-payment-details">
                            ${renderStudentFinancialHistory(student.id, appState.payments.filter(p => p.studentId === student.id), true, true)}
                        </li>
                    ` : ''}
                `).join('')}
            </ul>
            `}
        </div>
    `;
}