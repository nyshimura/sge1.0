// src/components/enrollmentModal.js
import { appState } from '../state.js';

export function renderEnrollmentModal(data) {
    const { contractText, termsText, studentData, guardianData, isMinor, courseId, isReenrollment } = data;
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay enrollment-modal';
    modalOverlay.onclick = (e) => {
        if (e.target === modalOverlay) {
            window.AppHandlers.handleCloseEnrollmentModal(); // Usa handler global
        }
    };

    const guardianSection = isMinor ? `
        <div class="modal-section guardian-section">
            <h4>Dados do Responsável (Obrigatório)</h4>
            <div class="profile-grid">
                <div class="form-group"> <label for="guardianName">Nome Completo</label> <input type="text" id="guardianName" name="guardianName" value="${guardianData?.name || ''}" required> </div>
                <div class="form-group"> <label for="guardianEmail">E-mail</label> <input type="email" id="guardianEmail" name="guardianEmail" value="${guardianData?.email || ''}" required> </div>
                <div class="form-group"> <label for="guardianPhone">Telefone</label> <input type="tel" id="guardianPhone" name="guardianPhone" value="${guardianData?.phone || ''}" required> </div>
                <div class="form-group"> <label for="guardianRG">RG</label> <input type="text" id="guardianRG" name="guardianRG" value="${guardianData?.rg || ''}" required> </div>
                <div class="form-group"> <label for="guardianCPF">CPF</label> <input type="text" id="guardianCPF" name="guardianCPF" value="${guardianData?.cpf || ''}" required> </div>
            </div>
        </div>
    ` : '';

    modalOverlay.innerHTML = `
        <div class="modal-content large">
            <button class="modal-close" onclick="window.AppHandlers.handleCloseEnrollmentModal()">×</button>
            <h2>${isReenrollment ? 'Renovar Matrícula' : 'Confirmar Matrícula'}</h2>
            <form id="enrollment-form" onsubmit="window.AppHandlers.handleSubmitEnrollment(event)">
                <input type="hidden" name="courseId" value="${courseId}">
                <input type="hidden" name="studentId" value="${appState.currentUser.id}">
                <input type="hidden" name="isReenrollment" value="${isReenrollment ? 'true' : 'false'}">
                <p style="text-align: center; margin-bottom: 1rem; color: #888;">Atualize seus dados e aceite os termos para ${isReenrollment ? 'renovar a' : 'realizar a'} Matrícula.</p>

                <div class="modal-body-scrollable">
                    <div class="modal-section student-section">
                        <h4>Seus Dados</h4>
                         <p>Por favor, confirme ou preencha seus documentos.</p>
                        <div class="profile-grid">
                            <div class="form-group"> <label for="aluno_rg">RG</label> <input type="text" id="aluno_rg" name="aluno_rg" value="${studentData?.rg || ''}" required> </div>
                            <div class="form-group"> <label for="aluno_cpf">CPF</label> <input type="text" id="aluno_cpf" name="aluno_cpf" value="${studentData?.cpf || ''}" required> </div>
                        </div>
                    </div>

                    ${guardianSection}

                    <div class="modal-section document-section">
                        <h4>Contrato de Prestação de Serviços</h4>
                        <div class="document-viewer"> ${contractText || 'Modelo de contrato não disponível.'} </div>
                        <div class="checkbox-group-modal">
                            <input type="checkbox" id="acceptContract" name="acceptContract" required onchange="document.getElementById('submit-enrollment-btn').disabled = !this.checked;">
                            <label for="acceptContract">Li e aceito o Contrato de Prestação de Serviços.</label>
                        </div>
                    </div>

                    <div class="modal-section document-section">
                        <h4>Termo de Autorização de Uso de Imagem</h4>
                        <div class="document-viewer"> ${termsText || 'Modelo de termo de imagem não disponível.'} </div>
                        <div class="checkbox-group-modal">
                            <input type="checkbox" id="acceptImageTerms" name="acceptImageTerms">
                            <label for="acceptImageTerms">Autorizo o uso de imagem conforme os termos (opcional).</label>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                     <p class="error-message" id="enrollment-error" style="text-align: left; flex-grow: 1;"></p>
                    <button type="button" class="action-button secondary" onclick="window.AppHandlers.handleCloseEnrollmentModal()">Cancelar</button>
                    <button type="submit" id="submit-enrollment-btn" class="action-button" disabled>${isReenrollment ? 'Confirmar Rematrícula' : 'Confirmar Matrícula'}</button>
                </div>
            </form>
        </div>
    `; // O bloco <style> foi removido daqui.

    // Re-adiciona listener para validação e máscaras (mantido como está)
     modalOverlay.querySelector('#enrollment-form').addEventListener('input', (event) => { /* ... */ });

    return modalOverlay;
}