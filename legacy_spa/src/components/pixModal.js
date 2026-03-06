// src/components/pixModal.js
import { appState } from '../state.js'; // Importa se precisar de dados do estado

export function renderPixPaymentModal(appState) {
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.onclick = (e) => {
        if (e.target === modalOverlay) {
            window.AppHandlers.handleClosePixModal(); // Usa handler global
        }
    };

    let modalBodyHtml = '';
    const { content: pixModalContent, isOpen } = appState.pixModal;
    const schoolProfile = appState.schoolProfile;

    if (!isOpen) return modalOverlay; // Retorna vazio se não estiver aberto

    if (!pixModalContent) {
        modalBodyHtml = `
            <div class="pix-modal-body">
                <span>Gerando cobrança PIX...</span>
            </div>
        `;
    } else {
        const { qrCodeUrl, pixCode, totalAmount, coursesInfo } = pixModalContent;
        const receiverName = schoolProfile?.name || '';
        const receiverKey = schoolProfile?.pixKeyType && schoolProfile?.pixKey
                           ? `(${schoolProfile.pixKeyType}): ${schoolProfile.pixKey}`
                           : '(Chave não definida)';

        modalBodyHtml = `
            <div class="pix-modal-body">
                <p>Para pagar, aponte a câmera do seu celular para o QR Code ou utilize o código "Copia e Cola".</p>
                <div class="qr-code-placeholder">
                    <img src="${qrCodeUrl}" alt="PIX QR Code" />
                </div>
                <div class="pix-copy-paste">
                    <input type="text" id="pix-code" value="${pixCode}" readonly>
                    <button onclick="window.AppHandlers.handleCopyPixCode()">Copiar</button>
                </div>
                <div class="payment-summary">
                    <h4>Resumo do Pagamento</h4>
                    <p><strong>Valor Total:</strong> R$ ${totalAmount.toFixed(2).replace('.', ',')}</p>
                    <p><strong>Recebedor:</strong> ${receiverName}</p>
                    <p><strong>Chave PIX ${receiverKey}</strong></p>
                    <p><strong>Referente a:</strong> ${coursesInfo}</p>
                </div>
            </div>
        `;
    }

    modalOverlay.innerHTML = `
        <div class="modal-content">
            <button class="modal-close" onclick="window.AppHandlers.handleClosePixModal()">×</button>
            <h2>Pagamento via PIX</h2>
            ${modalBodyHtml}
        </div>
    `;

    return modalOverlay;
}