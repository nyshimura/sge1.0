// src/handlers/modalHandlers.js
import { apiCall } from '../api.js';
import { appState } from '../state.js';
import { render } from '../router.js';
import { generateBrCode } from '../utils/pix.js';

// --- Funções PIX ---
export function handleInitiatePixPayment(paymentIds) {
    if (!appState.schoolProfile || !appState.schoolProfile.pixKey) return alert("Chave PIX não configurada pela administração.");

    // Fecha modal de matrícula se estiver aberto
    window.AppHandlers.handleCloseEnrollmentModal();

    appState.pixModal.isOpen = true;
    appState.pixModal.paymentIds = Array.isArray(paymentIds) ? paymentIds : [paymentIds]; // Garante que seja array
    appState.pixModal.content = null; // Mostra "Gerando..."

    // Encontra os pagamentos correspondentes no estado atual
    let paymentsToProcess = appState.payments.filter(p => appState.pixModal.paymentIds.includes(p.id) && (p.status === 'Pendente' || p.status === 'Atrasado'));

    // Verifica se todos os IDs solicitados foram encontrados e estão pendentes/atrasados
    if (paymentsToProcess.length !== appState.pixModal.paymentIds.length) {
        console.warn("Alguns pagamentos PIX solicitados não estão pendentes/atrasados ou não foram encontrados localmente. Buscando da API...");
        render(); // Mostra o modal com "Gerando..."
        // Busca os pagamentos do aluno atualizados da API
        apiCall('getStudentPayments', { studentId: appState.currentUser.id }, 'GET')
            .then(data => {
                // Atualiza a lista de pagamentos no estado global
                appState.payments = [
                    ...appState.payments.filter(p => p.studentId !== appState.currentUser.id), // Remove antigos do aluno
                    ...(data.payments || []) // Adiciona os novos
                ];
                // Tenta encontrar os pagamentos novamente
                paymentsToProcess = appState.payments.filter(p => appState.pixModal.paymentIds.includes(p.id) && (p.status === 'Pendente' || p.status === 'Atrasado'));

                if (paymentsToProcess.length === 0) {
                    throw new Error("Nenhum pagamento pendente encontrado para os IDs solicitados.");
                }
                generateAndRenderPixContent(paymentsToProcess);
            })
            .catch(e => {
                console.error("Erro ao buscar pagamentos para PIX:", e);
                alert("Erro ao gerar PIX: " + e.message);
                handleClosePixModal(); // Fecha o modal em caso de erro
            });
    } else {
        // Todos os pagamentos foram encontrados localmente
        generateAndRenderPixContent(paymentsToProcess);
        render(); // Renderiza o modal com o conteúdo PIX
    }
}

// Função auxiliar para gerar conteúdo PIX e atualizar o estado
function generateAndRenderPixContent(paymentsToProcess) {
    const totalAmount = paymentsToProcess.reduce((sum, p) => sum + Number(p.amount), 0);
    const courses = [...new Set(paymentsToProcess.map(p => {
        const course = appState.courses.find(c => c.id === p.courseId);
        return course ? course.name : `Curso ID ${p.courseId}`;
    }))].filter(Boolean).join(', ');

    const txid = `SGE${Date.now()}${appState.currentUser.id}`.slice(0, 25); // Limita TXID
    const city = (appState.schoolProfile.city || 'SAO PAULO').substring(0, 15); // Usa cidade do perfil ou padrão
    const name = (appState.schoolProfile.name || 'ESCOLA').substring(0, 25); // Usa nome do perfil ou padrão

    const pixCode = generateBrCode(
        appState.schoolProfile.pixKey,
        totalAmount,
        name,
        city,
        txid,
        `Pag SGE: ${courses.substring(0, 72)}` // Limita descrição
    );
    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(pixCode)}`;

    appState.pixModal.content = { qrCodeUrl, pixCode, totalAmount, coursesInfo: courses };
    // A chamada render() será feita pela função handleInitiatePixPayment
}

export function handleClosePixModal() {
    const modal = document.querySelector('.modal-overlay:not(.enrollment-modal)');
    if (modal) modal.remove();
    appState.pixModal = { isOpen: false, paymentIds: [], content: null };
    // Não chama render() aqui diretamente para evitar re-render duplo se outra ação o fizer
}

export function handleCopyPixCode() {
    const input = document.getElementById('pix-code');
    if (input && navigator.clipboard) {
        navigator.clipboard.writeText(input.value)
            .then(() => alert('Código PIX copiado!'))
            .catch(err => {
                console.error('Falha ao copiar:', err);
                alert('Falha ao copiar. Tente manualmente.');
            });
    } else if (input) {
        // Fallback para navegadores sem clipboard API (menos comum)
        input.select();
        input.setSelectionRange(0, 99999); // Para mobile
        try {
            document.execCommand('copy');
            alert('Código PIX copiado!');
        } catch (err) {
            alert('Falha ao copiar.');
        }
    }
}

// --- Funções do Modal de Matrícula ---
// handleInitiateEnrollment e handleSubmitEnrollment estão em enrollmentHandlers.js
// handleCloseEnrollmentModal está aqui porque também é chamado por handleInitiatePixPayment
export function handleCloseEnrollmentModal() {
    const modal = document.querySelector('.enrollment-modal');
    if (modal) modal.remove();

    const wasReenrollment = appState.enrollmentModalState.isReenrollment;
    const courseIdBeingProcessed = appState.enrollmentModalState.data?.courseId;

    // Guarda o estado atual antes de resetar
    const previousModalState = { ...appState.enrollmentModalState };

    // Reseta o estado do modal
    appState.enrollmentModalState = { isOpen: false, data: null, isReenrollment: false };

    // Lógica de logout ao fechar modal de rematrícula obrigatória
    if (previousModalState.isReenrollment && previousModalState.data?.courseId && appState.currentUser) {
        let stillNeedsReenrollment = false; const now = new Date(); const enrollments = appState.enrollments || [];
        const enrollment = enrollments.find(e => e.studentId === appState.currentUser.id && e.courseId === previousModalState.data.courseId && e.status === 'Aprovada');
        if (enrollment && enrollment.contractAcceptedAt) {
            try { const acceptedDate = new Date(enrollment.contractAcceptedAt); if (!isNaN(acceptedDate.getTime())) { const checkDate = new Date(acceptedDate.getTime()); checkDate.setMonth(checkDate.getMonth() + 12); if (now >= checkDate) stillNeedsReenrollment = true; } } catch (e) { console.error("Erro data logout modal:", e); }
        } else if (!enrollment) stillNeedsReenrollment = false;

        if (stillNeedsReenrollment) { console.log("Modal rematrícula fechado. Deslogando..."); alert("Conclua a renovação para continuar."); setTimeout(window.AppHandlers.handleLogout, 50); }
        else { console.log("Rematrícula OK. Renderizando dashboard..."); setTimeout(render, 50); }
    } else if (!previousModalState.isReenrollment) {
        // Se fechou um modal de matrícula normal, apenas re-renderiza (com delay)
         setTimeout(render, 50);
    } else {
        console.log("Modal rematrícula fechado, user não logado.");
    }
}