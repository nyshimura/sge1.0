// src/views/financial/dashboard.js
import { apiCall } from '../../api.js';
import { appState } from '../../state.js';
import { generateRevenueEvolutionChartSVG, generateRevenueByCourseChartSVG, generatePaidVsOutstandingBarChartSVG } from '../../components/charts.js';

export async function renderFinancialDashboardView() {
    const selectedDate = appState.financialState.selectedDate;
    const data = await apiCall('getFinancialDashboardData', { month: selectedDate }, 'GET');

    const { expectedRevenue, collectedRevenue, outstandingRevenue, evolutionData, revenueByCourseData } = data;
    const progress = (expectedRevenue > 0) ? (collectedRevenue / expectedRevenue) * 100 : (collectedRevenue > 0 ? 100 : 0);

    const monthlyStatusData = {
        'Pago': collectedRevenue,
        'Em Aberto': outstandingRevenue,
    };

    return `
        <div class="view-header">
            <h2>Dashboard Financeiro</h2>
            <div class="dashboard-controls">
                <label for="month-selector">Selecionar Mês:</label>
                <input type="month" id="month-selector" name="month" value="${selectedDate}" onchange="window.AppHandlers.handleDashboardDateChange(event)">
            </div>
            <button class="back-button" onclick="window.AppHandlers.handleNavigateBackToDashboard()">← Voltar</button>
        </div>

        <div class="card full-width">
            <div class="financial-summary-grid">
                <div class="summary-card">
                    <h3>Receita Prevista</h3>
                    <p>R$ ${Number(expectedRevenue).toFixed(2).replace('.', ',')}</p>
                </div>
                <div class="summary-card">
                    <h3>Receita Arrecadada</h3>
                    <p>R$ ${Number(collectedRevenue).toFixed(2).replace('.', ',')}</p>
                </div>
                <div class="summary-card">
                    <h3>Inadimplência</h3>
                    <p>R$ ${Number(outstandingRevenue).toFixed(2).replace('.', ',')}</p>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: ${progress}%"></div>
            </div>
            <p style="text-align: center; margin-top: 0.5rem; font-weight: 500;">${progress.toFixed(0)}% da meta arrecadada</p>
        </div>

         <div class="card full-width">
            <h3 class="card-title">Ações</h3>
            <div class="list-item-actions" style="justify-content: flex-start;">
                <button class="action-button" onclick="window.AppHandlers.handleNavigateToFinancialControlPanel()">Gerenciar Pagamentos</button>
                <button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToDefaultersReport()">Relatório de Inadimplentes</button>
            </div>
        </div>

        <div class="charts-grid three-cols">
            <div class="card chart-container">
                <h3 class="card-title">Pago vs. Em Aberto (Mês)</h3>
                ${generatePaidVsOutstandingBarChartSVG(monthlyStatusData)}
            </div>
            <div class="card chart-container">
                <h3 class="card-title">Receita por Curso (Mês)</h3>
                ${generateRevenueByCourseChartSVG(revenueByCourseData)}
            </div>
             <div class="card chart-container">
                <h3 class="card-title">Evolução da Receita (6 Meses)</h3>
                ${generateRevenueEvolutionChartSVG(evolutionData)}
            </div>
        </div>
    `;
}