// src/views/school/certificateTemplate.js
import { apiCall } from '../../api.js';
import { appState } from '../../state.js';

// As funções 'previewCertificateBackground' e 'removeCertificateBackground'
// estão em 'src/handlers/certificateHandlers.js'
// e são carregadas globalmente pelo index.js

export async function renderCertificateTemplateView() {
    // Busca as configurações se ainda não estiverem carregadas ou se estiverem incompletas
    if (!appState.systemSettings || appState.systemSettings.certificate_template_text === undefined) {
         try {
            const data = await apiCall('getSystemSettings', {}, 'GET');
            appState.systemSettings = data.settings;
        } catch(e) {
             console.error("Erro ao buscar settings para template cert:", e);
             return `<div class="view-header"><h2>Erro</h2><button class="back-button" onclick="window.AppHandlers.handleNavigateBackToDashboard()">← Voltar</button></div><p>Erro ao carregar.</p>`;
        }
    }
    const settings = appState.systemSettings || {};

    const placeholders = [
        { group: 'Aluno', items: ['{{aluno_nome_completo}}', '{{aluno_cpf}}', '{{aluno_rg}}'] },
        { group: 'Curso', items: ['{{curso_nome}}', '{{curso_carga_horaria}}', '{{professor_nome}}'] },
        { group: 'Data/Local', items: ['{{data_conclusao}}', '{{data_emissao_extenso}}', '{{escola_cidade}}'] },
        { group: 'Escola', items: ['{{escola_nome}}', '{{escola_cnpj}}'] },
        { group: 'Verificação', items: ['{{codigo_verificacao}}'] }
    ];

    const currentBgImage = settings.certificate_background_image;

    // Inputs hidden para todos os outros settings, para não serem zerados ao salvar este formulário
    const hiddenSettings = [
        'language', 'timeZone', 'currencySymbol', 'defaultDueDay', 'geminiApiKey',
        'geminiApiEndpoint', 'smtpServer', 'smtpPort', 'smtpUser', 'smtpPass',
        'enableTerminationFine', 'terminationFineMonths', 'site_url', 'email_approval_subject',
        'email_approval_body', 'email_reset_subject', 'email_reset_body',
        'enrollmentContractText', 'imageTermsText' // Inclui os templates de documento
    ];
    // Escapa aspas duplas nos valores dos campos hidden
    const hiddenInputs = hiddenSettings
        .map(key => {
             const value = settings[key] || '';
             const escapedValue = typeof value === 'string' ? value.replace(/"/g, '&quot;') : value;
             return `<input type="hidden" name="${key}" value="${escapedValue}">`;
         })
        .join('\n');


    return `
        <div class="view-header">
            <h2>Gerenciar Modelo de Certificado</h2>
            <button class="back-button" onclick="window.AppHandlers.handleNavigateBackToDashboard()">← Voltar</button>
        </div>


        <form id="certificate-template-form" class="card full-width" onsubmit="window.AppHandlers.handleUpdateSystemSettings(event)">

            ${hiddenInputs}

            <div class="document-editor-container">
                <div class="document-editor">
                    <h3 class="card-title">Texto do Certificado</h3>
                    <p style="font-size: 0.9rem; color: #aaa; margin-top: -1rem;">Use os placeholders ao lado. O texto será centralizado e sobreposto à imagem de fundo. Use tags <b>Negrito</b>, <i>Itálico</i> e quebras de linha (Enter) para formatar.</p>
                    <div class="form-group">
                        <textarea id="certificate_template_text" name="certificate_template_text" rows="20" placeholder="Ex: Certificamos que <b>{{aluno_nome_completo}}</b>...">${settings.certificate_template_text || ''}</textarea>
                    </div>

                    <h3 class="card-title" style="margin-top: 2rem;">Imagem de Fundo (Opcional)</h3>
                    <p style="font-size: 0.9rem; color: #aaa; margin-top: -1rem;">Recomendado: A4 Paisagem (aprox. 3508x2480 pixels para 300dpi).</p>
                    <div class="form-group">
                         <label for="certificateBackgroundImageInput">Enviar Nova Imagem (JPG/PNG)</label>
                         <input type="file" id="certificateBackgroundImageInput" name="certificateBackgroundImageInput_temp" accept="image/jpeg, image/png" onchange="window.AppHandlers.previewCertificateBackground(event)">
                         <input type="hidden" id="currentCertificateBackground" name="certificate_background_image" value="${currentBgImage || ''}">
                    </div>
                    <div style="margin-top: 1rem;">
                        <p>Pré-visualização (Imagem de fundo atual):</p>
                        <img id="certificate-bg-preview" src="${currentBgImage || '#'}" alt="Prévia Fundo" style="max-width: 100%; height: auto; border: 1px solid var(--border-color); display: ${currentBgImage ? 'block' : 'none'}; background-color: #f0f0f0;">
                        ${currentBgImage ? '<button type="button" class="action-button danger" style="margin-top: 0.5rem;" onclick="window.AppHandlers.removeCertificateBackground()">Remover Imagem</button>' : '<p>Nenhuma imagem de fundo definida.</p>'}
                    </div>
                </div>

                <div class="placeholders-sidebar">
                    <h3 class="card-title">Placeholders</h3>
                    <p>Clique para copiar.</p>
                    ${placeholders.map(group => `
                        <div class="placeholder-group">
                            <h4>${group.group}</h4>
                            ${group.items.map(ph => `<button type="button" class="placeholder-button" onclick="navigator.clipboard.writeText('${ph}')">${ph}</button>`).join('')}
                        </div>
                    `).join('')}
                     <div class="placeholder-group">
                        <h4>Dicas de Layout</h4>
                        <p style="font-size: 0.8rem; line-height: 1.4;">
                            - O PDF será gerado em A4 Paisagem (deitado).<br>
                            - A imagem de fundo cobrirá a página inteira.<br>
                            - O texto acima será centralizado (X: 25mm, Y: 70mm).<br>
                            - O QR Code e o Código de Verificação serão adicionados no canto inferior esquerdo.<br>
                        </p>
                    </div>
                </div>
            </div>

            <p class="error-message" id="certificate-template-error"></p>
            <button type="submit" class="action-button" style="margin-top: 1.5rem;">Salvar Modelo</button>
        </form>
    `;
}