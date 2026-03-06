// src/handlers/navigationHandlers.js
import { appState } from '../state.js';
import { render } from '../router.js';

// --- FUNÇÃO AUXILIAR PARA RESETAR VIEWS ---
function resetSubViews() {
    appState.viewingCourseId = null;
    appState.viewingUserId = null;
    appState.attendanceState = appState.attendanceState || {}; // Garante que attendanceState exista
    appState.attendanceState.courseId = null;
    appState.financialState.isDashboardVisible = false;
    appState.financialState.isControlPanelVisible = false;
    appState.financialState.isDefaultersReportVisible = false;
    appState.financialState.expandedStudentId = null;
    appState.documentTemplatesState = appState.documentTemplatesState || {}; // Garante que documentTemplatesState exista
    appState.documentTemplatesState.isVisible = false; // Usa isVisible se for o caso
    appState.documentTemplatesState.isEditing = false; // Adiciona reset para edição, se aplicável
    appState.adminView = null; // Reseta adminView

    // Limpa a hash da URL, exceto para rotas que dependem dela (como reset de senha)
    const resetPasswordPattern = /^#resetPassword(\?.*)?$/;
    const currentHash = window.location.hash;
    if (currentHash && !resetPasswordPattern.test(currentHash) && currentHash !== '#forgotPasswordRequest') {
        history.pushState("", document.title, window.location.pathname + window.location.search);
    }
}


// --- FUNÇÕES DE NAVEGAÇÃO PRINCIPAIS ---
export function navigateTo(viewName) {
    resetSubViews(); // Reseta estados antes de definir a nova view principal
    appState.currentView = viewName;
    render();
}

export function handleNavigateBackToDashboard() {
    resetSubViews(); // Limpa todos os sub-estados
    appState.currentView = 'dashboard'; // Define a view principal
    render();
}

export function handleNavigateToAttendance(courseId) {
    resetSubViews(); // Limpa outros estados primeiro
    appState.viewingCourseId = courseId;    // Define o curso que estamos visualizando
    appState.currentView = 'attendance'; // Define a VISÃO PRINCIPAL desejada
    render();                         // Chama o roteador para atualizar a tela
}

export function handleNavigateToEditCourse(courseId) {
    resetSubViews();
    appState.viewingCourseId = courseId;
    appState.adminView = 'editCourse'; // Define a sub-view de admin
    appState.currentView = 'adminDashboard'; // Ou a view base de admin
    render();
}

export function handleNavigateToCreateCourse() {
    resetSubViews();
    appState.adminView = 'createCourse';
    appState.currentView = 'adminDashboard'; // Ou a view base de admin
    render();
}

export function handleNavigateToCourseDetails(courseId) {
    resetSubViews();
    appState.viewingCourseId = courseId;
    appState.currentView = 'courseDetails'; // Define a view principal como detalhes do curso
    render();
}

export function handleNavigateToUserManagement() {
    resetSubViews();
    appState.adminView = 'userManagement';
    appState.currentView = 'adminDashboard'; // Ou a view base de admin
    render();
}

export function handleNavigateToSystemSettings() {
    resetSubViews();
    appState.adminView = 'systemSettings';
    appState.currentView = 'adminDashboard'; // Ou a view base de admin
    render();
}

export function handleNavigateToDocumentTemplates() {
    resetSubViews();
    appState.adminView = 'documentTemplates';
    appState.currentView = 'adminDashboard'; // Ou a view base de admin
    render();
}

export function handleNavigateToCertificateTemplate() {
    resetSubViews();
    appState.adminView = 'certificateTemplate';
    appState.currentView = 'adminDashboard'; // Ou a view base de admin
    render();
}

export function handleNavigateToProfile(userId) {
    resetSubViews();
    appState.viewingUserId = userId;
    appState.currentView = 'profile'; // Define a view principal como perfil
    render();
}

export function handleNavigateToSchoolProfile() {
    resetSubViews();
    appState.viewingUserId = -1; // Sinaliza perfil da escola
    appState.currentView = 'schoolProfile'; // Define a view principal
    render();
}


// --- FUNÇÕES DE NAVEGAÇÃO FINANCEIRA ---
export function handleNavigateToFinancialDashboard() {
    resetSubViews();
    appState.financialState.isDashboardVisible = true;
    appState.currentView = 'adminDashboard'; // Ou a view base de admin financeiro
    render();
}

export function handleNavigateToFinancialControlPanel() {
    resetSubViews();
    appState.financialState.isControlPanelVisible = true;
    appState.currentView = 'adminDashboard'; // Ou a view base
    render();
}

export function handleNavigateToDefaultersReport() {
    resetSubViews();
    appState.financialState.isDefaultersReportVisible = true;
    appState.currentView = 'adminDashboard'; // Ou a view base
    render();
}

// --- NOVA FUNÇÃO PARA MEUS CERTIFICADOS ---
export function handleNavigateToMyCertificates() {
    resetSubViews(); // Limpa outros estados
    appState.currentView = 'myCertificates'; // Define a view alvo
    render(); // Renderiza
}
// ------------------------------------

// Exporta todas as funções em um objeto para facilitar o registro
export const navigationHandlers = {
    navigateTo,
    handleNavigateBackToDashboard,
    handleNavigateToAttendance,
    handleNavigateToEditCourse,
    handleNavigateToCreateCourse,
    handleNavigateToCourseDetails,
    handleNavigateToUserManagement,
    handleNavigateToSystemSettings,
    handleNavigateToDocumentTemplates,
    handleNavigateToCertificateTemplate,
    handleNavigateToProfile,
    handleNavigateToSchoolProfile,
    handleNavigateToFinancialDashboard,
    handleNavigateToFinancialControlPanel,
    handleNavigateToDefaultersReport,
    handleNavigateToMyCertificates
};