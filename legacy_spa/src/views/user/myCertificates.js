// src/views/user/myCertificates.js
import { apiCall } from '../../api.js'; // Ajuste o caminho se necessário
import { appState } from '../../state.js'; // Ajuste o caminho se necessário

/**
 * Renderiza a view "Meus Certificados" para o aluno logado.
 */
export async function renderMyCertificatesView() {
    // Garante que temos um usuário logado e que ele é um aluno
    if (!appState.currentUser || appState.currentUser.role !== 'student') {
        window.AppHandlers.handleNavigateBackToDashboard();
        return '<div class="loading-placeholder">Acesso não permitido. Redirecionando...</div>';
    }

    const studentId = appState.currentUser.id;
    let certificatesHtml = '<p>Nenhum certificado encontrado.</p>';
    let responseData = null; // Variável para armazenar os dados relevantes da resposta

    try {
        const response = await apiCall('getStudentCertificates', { studentId }, 'GET');

        // <<< MODIFICADO: Lógica de verificação da resposta >>>
        let certificates = null;
        let apiMessage = null;

        // Tenta acessar os certificados na estrutura esperada { success: true, data: { certificates: [...] } }
        if (response && response.success && response.data && response.data.certificates) {
            certificates = response.data.certificates;
            apiMessage = response.data.message; // Pega a mensagem se houver
            responseData = response.data; // Guarda para log se necessário
        }
        // Tenta acessar na estrutura alternativa { certificates: [...] } (se apiCall retornar só o 'data')
        else if (response && response.certificates) {
             console.warn("API retornou estrutura inesperada, mas certificados foram encontrados diretamente.");
             certificates = response.certificates;
             apiMessage = response.message;
             responseData = response; // Guarda para log
        }
        // Se response existe, mas success é false ou data não está no formato esperado
        else if (response) {
            apiMessage = response.data?.message || response.message || 'Erro desconhecido na resposta da API.';
            responseData = response; // Guarda para log
            console.error("Erro ao buscar certificados (resposta API):", responseData);
        }
         // Se a própria chamada apiCall falhou (erro de rede, etc.)
        else {
             throw new Error("Falha na chamada da API (resposta nula ou indefinida)");
        }


        // Agora processa os certificados ou a mensagem
        if (certificates && certificates.length > 0) {
            certificatesHtml = `
                <ul class="certificates-list">
                    ${certificates.map(cert => `
                        <li class="certificate-item card">
                            <h3>${cert.courseName || 'Nome do Curso Indisponível'}</h3>
                            <p>Data de Conclusão: ${cert.completion_date_formatted || cert.completion_date}</p>
                            
                            <button class="action-button primary"
                                    onclick="window.AppHandlers.handleViewCertificate(${studentId}, ${cert.courseId}, '${cert.completion_date}', '${cert.verification_hash}')">
                                Visualizar Certificado
                            </button>
                            ${cert.verification_hash ? `<p class="certificate-hash">Código: ${cert.verification_hash}</p>` : ''}
                        </li>
                    `).join('')}
                </ul>
            `;
        } else if (apiMessage) {
            // Se veio uma mensagem da API (mesmo que success=true e array vazio, ou success=false)
            certificatesHtml = `<p>${apiMessage}</p>`;
        }
        // Se certificates for null e não houver apiMessage (erro inesperado)
        else if (!certificates) {
             certificatesHtml = `<p>Ocorreu um erro ao processar a resposta dos certificados.</p>`;
             // O erro já foi logado acima
        }


    } catch (error) {
        // Captura erros da chamada apiCall (rede) ou erros lançados manualmente
        console.error("Erro na chamada/processamento da API getStudentCertificates:", error);
        certificatesHtml = `<p>Ocorreu um erro de conexão ao buscar seus certificados. Verifique sua internet e tente novamente.</p>`;
    }

    // Retorna o HTML completo da view
    return `
        <div class="view-container my-certificates-view">
            <h2>Meus Certificados</h2>
            <div class="certificates-container">
                ${certificatesHtml}
            </div>
             <button class="action-button secondary back-button" onclick="window.AppHandlers.handleNavigateBackToDashboard()">Voltar ao início</button>
        </div>
    `;
}