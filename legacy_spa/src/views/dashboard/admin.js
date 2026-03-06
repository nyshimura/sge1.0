// src/views/dashboard/admin.js
import { appState } from '../../state.js';

export function renderAdminView(adminId, data) {
    const admin = appState.currentUser;
    if (!admin) return '';

    const pendingEnrollments = data.enrollments.filter((e) => e.status === 'Pendente');
    const allCourses = data.courses || [];
    const openCourses = allCourses.filter((c) => c.status === 'Aberto');

    const cards = [
        {
          id: 'admin-finance',
          html: `
            <div class="card" id="admin-finance" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">💰 Controle Financeiro</h3>
                <p>Acesse o dashboard financeiro para visualizar a receita e a inadimplência.</p>
                <button class="action-button" onclick="window.AppHandlers.handleNavigateToFinancialDashboard()">Acessar Dashboard</button>
            </div>
          `
        },
        ...(admin.role === 'superadmin' ? [{
          id: 'admin-users',
          html: `
            <div class="card" id="admin-users" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">👥 Gerenciamento de Usuários</h3>
                <p>Filtre, visualize e altere as funções dos usuários do sistema.</p>
                <button class="action-button" onclick="window.AppHandlers.handleNavigateToUserManagement()">Acessar Gerenciamento</button>
            </div>
          `
        }] : []),
        {
          id: 'admin-create-course',
          html: `
            <div class="card" id="admin-create-course" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">➕ Criar Novo Curso</h3>
                <p>Adicione um novo curso ao catálogo da escola.</p>
                <button class="action-button" onclick="window.AppHandlers.handleNavigateToCreateCourse()">Criar Curso</button>
            </div>
          `
        },
        {
          id: 'admin-manage-courses',
          html: `
            <div class="card" id="admin-manage-courses" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">⚙️ Gerenciar Cursos</h3>
                <div class="list-wrapper">
                    <ul class="list">
                        ${allCourses.length === 0 ? '<li>Nenhum curso cadastrado.</li>' : allCourses.map((course) => `
                            <li class="list-item">
                                <div class="list-item-content">
                                    <span class="list-item-title">${course.name}</span>
                                    <span class="status-badge status-${course.status ? course.status.toLowerCase() : 'desconhecido'}">${course.status || 'Status?'}</span>
                                </div>
                                 <div class="list-item-actions">
                                    <button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToCourseDetails(${course.id})">Detalhes</button>
                                    <button class="action-button" onclick="window.AppHandlers.handleNavigateToEditCourse(${course.id})">Editar</button>
                                    ${course.status === 'Aberto' ? `<button class="action-button danger" onclick="window.AppHandlers.handleEndCourse(${course.id})">Encerrar</button>` : ''}
                                    ${course.status === 'Encerrado' && admin.role === 'superadmin' ? `<button class="action-button" onclick="window.AppHandlers.handleReopenCourse(${course.id})">Reabrir</button>` : ''}
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
          `
        },
        {
          id: 'admin-pending-enrollments',
          html: `
            <div class="card" id="admin-pending-enrollments" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">📬 Matrículas Pendentes (${pendingEnrollments.length})</h3>
                <div class="list-wrapper">
                    <ul class="list">
                        ${pendingEnrollments.length === 0 ? '<li>Nenhuma matrícula pendente.</li>' : pendingEnrollments.map((enrollment) => {
                            const student = data.users.find((s) => s.id === enrollment.studentId);
                            const course = allCourses.find((c) => c.id === enrollment.courseId);
                            if (!student || !course) return '';
                            const enrolledCount = data.enrollments.filter((e) => e.courseId === course.id && e.status === 'Aprovada').length;
                            const vacancies = course.totalSlots === null ? 'Ilimitadas' : Math.max(0, course.totalSlots - enrolledCount);
                            // Usa custom fee DA MATRICULA se existir, senão o padrão do curso
                            const feeValue = enrollment.customMonthlyFee !== null ? enrollment.customMonthlyFee : course.monthlyFee;

                            return `
                            <li class="list-item">
                                <div class="list-item-content">
                                    <span class="list-item-title">${student.firstName} ${student.lastName || ''} - ${course.name}</span>
                                    <span class="list-item-subtitle">Vagas restantes: ${vacancies}</span>
                                </div>
                                <form class="enrollment-approval-form" onsubmit="window.AppHandlers.handleApprove(event)" data-student-id="${student.id}" data-course-id="${course.id}">
                                    <div class="form-group-inline">
                                        <label for="overrideFee-${enrollment.studentId}-${enrollment.courseId}">Mensalidade (R$):</label>
                                        <input type="number" step="0.01" name="overrideFee" id="overrideFee-${enrollment.studentId}-${enrollment.courseId}" value="${feeValue}" required style="width: 100px;">
                                    </div>
                                    <div class="form-group-inline" style="margin-top: 0.5rem;">
                                        <select name="billingStart" required>
                                            <option value="this_month">Cobrar este mês</option>
                                            <option value="next_month">Cobrar próximo mês</option>
                                        </select>
                                        <button type="submit" class="action-button">Aprovar</button>
                                    </div>
                                </form>
                            </li>`
                        }).join('')}
                    </ul>
                </div>
            </div>
          `
        },
        {
          id: 'admin-attendance',
          html: `
            <div class="card" id="admin-attendance" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">📋 Controle de Frequência</h3>
                <div class="list-wrapper">
                    <ul class="list">
                        ${openCourses.length === 0 ? '<li>Nenhum curso aberto.</li>' : openCourses.map((course) => `
                            <li class="list-item">
                                 <div class="list-item-content">
                                    <span class="list-item-title">${course.name}</span>
                                </div>
                                <button class="action-button" onclick="window.AppHandlers.handleNavigateToAttendance(${course.id})">Lançar Frequência</button>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
          `
        }
    ];

    return `
        <div class="welcome-message">
            <h2>Painel do Administrador - ${admin.firstName}</h2>
            <p>Gerencie usuários, cursos, matrículas e relatórios.</p>
        </div>
        <div class="dashboard-grid" ondragover="window.AppHandlers.handleDragOver(event)" ondrop="window.AppHandlers.handleDrop(event)">
            ${cards.map(c => c.html).join('')}
        </div>
    `;
}