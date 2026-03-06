/**
 * Estado global da aplicação.
 */

// <<< ADICIONADO: Função para obter o mês atual no formato YYYY-MM >>>
function getCurrentMonthISO() {
    const now = new Date();
    // Formato YYYY-MM
    return `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}`;
}

export const appState = {
    currentUser: null,
    currentView: 'login', // Ex: 'login', 'dashboard', 'profile', 'myCertificates', etc.

    // Dados buscados da API
    users: [],
    courses: [],
    enrollments: [],
    attendance: [],
    payments: [],
    schoolProfile: null,
    systemSettings: null,

    // Estado da UI
    adminView: 'dashboard',
    viewingCourseId: null,
    viewingUserId: null,

    // Estado específico de cada view
    userFilters: {
        name: '',
        role: 'all',
        courseId: 'all',
        enrollmentStatus: 'all',
    },
    // <<< MODIFICADO: attendanceState agora inclui selectedMonth >>>
    attendanceState: {
        courseId: null, // Pode remover se não for usado diretamente aqui
        selectedDate: new Date().toISOString().split('T')[0], // Mantém data atual por padrão
        selectedMonth: getCurrentMonthISO(), // <<< NOVO: Guarda o mês selecionado (ex: '2025-10')
        students: [], // <<<< Você tinha isso aqui, mantido >>>>
        history: {} // <<< MODIFICADO: Será preenchido com registros do mês
    },
    financialState: {
        isDashboardVisible: false,
        isControlPanelVisible: false,
        isDefaultersReportVisible: false,
        selectedDate: new Date().toISOString().slice(0, 7),
        defaultersReportMonth: new Date().toISOString().slice(0, 7),
        defaultersReportCourseId: 'all',
        expandedStudentId: null,
    },
    pixModal: {
        isOpen: false,
        paymentIds: [],
        content: null,
    },
    documentTemplatesState: {
        isVisible: false,
        // isEditing: false, // Descomente se precisar
        // contractTemplate: '', // Descomente e inicialize se precisar
        // imageTermsTemplate: '', // Descomente e inicialize se precisar
        // certificateTemplate: '', // Descomente e inicialize se precisar
        // certificateBackground: null, // Descomente e inicialize se precisar
        // signatureImage: null, // Descomente e inicialize se precisar
        // siteUrl: '', // Descomente e inicialize se precisar
    },
    enrollmentModalState: {
        isOpen: false,
        data: null, // Guardará os dados recebidos da API getEnrollmentDocuments
        isReenrollment: false // Indica se é um fluxo de rematrícula
    }
    // isLoading: false, // Considere adicionar
    // errorMessage: null, // Considere adicionar
};